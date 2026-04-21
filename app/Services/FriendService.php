<?php

namespace App\Services;

use App\Notifications\FriendRequestNotification;
use App\Models\Friendship;
use App\Models\FriendRequestList;
use App\Models\Learner;
use App\Models\Block;
use Illuminate\Support\Facades\DB;

class FriendService
{
    protected $maxFriends = 299; // Legacy limit maintained

    /**
     * Get Incoming Friend Requests
     */
    public function getIncomingRequests($userId, $page = 1, $limit = 20)
    {
        $query = FriendRequestList::where('receiver_id', $userId)
            ->with('sender:user_id,learner_name,learner_phone,learner_image')
            ->orderBy('created_at', 'desc');

        $total = $query->count();
        $requests = $query->skip(($page - 1) * $limit)->take($limit)->get();
        $hasMore = (($page - 1) * $limit + $requests->count()) < $total;

        $list = $requests->map(function ($request) {
            $user = $request->sender;
            return [
                'userName' => $user ? $user->learner_name : 'Unknown',
                'userImage' => $user ? $user->learner_image : '',
                'phone' => $user ? (string)$user->learner_phone : '',
                'userId' => $user ? (string)$user->user_id : '',
                'requestId' => $request->id,
                'createdAt' => $request->created_at->toDateTimeString(),
            ];
        });

        return [
            'data' => $list,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'hasMore' => $hasMore,
            ]
        ];
    }

    /**
     * Get Friend List
     */
    public function getFriendList($userId, $page = 1, $limit = 20)
    {
        // Using new Friendship table
        // We need to eager load 'friend' which is a Learner
        $query = Friendship::where('user_id', $userId)
            ->with('friend:user_id,learner_name,learner_phone,learner_image') 
            ->orderBy('created_at', 'desc');

        $total = $query->count();
        $friends = $query->skip(($page - 1) * $limit)->take($limit)->get();
        $hasMore = (($page - 1) * $limit + $friends->count()) < $total;

        $list = $friends->map(function ($friendship) {
            $user = $friendship->friend;
            return [
                'userName' => $user ? $user->learner_name : 'Unknown',
                'userImage' => $user ? $user->learner_image : '',
                'phone' => $user ? (string)$user->learner_phone : '',
                'userId' => $user ? (string)$user->user_id : '',
            ];
        });

        return [
            'data' => $list,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'hasMore' => $hasMore,
            ]
        ];
    }

    /**
     * Send or Unsend Friend Request
     */
    public function addFriend($myId, $otherId)
    {
        if ($myId == $otherId) {
            throw new \Exception('Cannot send request to yourself');
        }

        // Check My Friend Limit
        $myFriCount = Friendship::where('user_id', $myId)->count();
        if ($myFriCount > $this->maxFriends) {
            return ['success' => false, 'code' => 'err53', 'error' => 'Friend limit reached'];
        }

        // Check if already friends
        $isFriend = Friendship::where('user_id', $myId)->where('friend_id', $otherId)->exists();
        if ($isFriend) {
            return ['success' => false, 'error' => 'Already friends'];
        }

        // Check if request already exists (Unsend logic)
        $existingRequest = FriendRequestList::where('sender_id', $myId)
            ->where('receiver_id', $otherId)
            ->first();

        if ($existingRequest) {
            // Unsend
            $existingRequest->delete();
            return ['success' => true, 'action' => 'unsent request'];
        } else {
            // Check if they already sent me a request (Accept logic should be used instead, but legacy handled this differently)
            // Ideally, we just create a request
            FriendRequestList::create([
                'sender_id' => $myId,
                'receiver_id' => $otherId
            ]);

            // Notify receiver
            $receiver = Learner::where('user_id', $otherId)->first();
            $sender = Learner::where('user_id', $myId)->first();
            if ($receiver && $sender) {
                $receiver->notify(new FriendRequestNotification(
                    senderId: (int) $myId,
                    senderName: $sender->learner_name ?? 'Someone',
                    senderImage: $sender->learner_image ?? '',
                    type: 'request'
                ));
            }

            return ['success' => true, 'action' => 'requested'];
        }
    }

    /**
     * Accept Friend Request
     */
    public function confirmFriend($myId, $otherId)
    {
        // Check Limits
        $myFriCount = Friendship::where('user_id', $myId)->count();
        if ($myFriCount > $this->maxFriends) {
            return ['success' => false, 'code' => 'err53', 'error' => 'Your friend limit reached'];
        }

        $otherFriCount = Friendship::where('user_id', $otherId)->count();
        if ($otherFriCount > $this->maxFriends) {
            return ['success' => false, 'code' => 'err54', 'error' => 'Their friend limit reached'];
        }

        // Check for request
        $friendRequest = FriendRequestList::where('sender_id', $otherId)
            ->where('receiver_id', $myId)
            ->first();

        if ($friendRequest) {
            $friendRequest->delete();
        }

        // Create Friendship (Bi-directional)
        DB::transaction(function () use ($myId, $otherId) {
            Friendship::firstOrCreate(['user_id' => $myId, 'friend_id' => $otherId]);
            Friendship::firstOrCreate(['user_id' => $otherId, 'friend_id' => $myId]);
        });

        // Notify other user
        $otherUser = Learner::where('user_id', $otherId)->first();
        $me = Learner::where('user_id', $myId)->first();
        if ($otherUser && $me) {
            $otherUser->notify(new FriendRequestNotification(
                senderId: (int) $myId,
                senderName: $me->learner_name ?? 'Someone',
                senderImage: $me->learner_image ?? '',
                type: 'accept'
            ));
        }

        return ['success' => true, 'action' => 'accepted'];
    }

    /**
     * Unfriend
     */
    public function unfriend($myId, $otherId)
    {
        // Remove Friendship (Bi-directional)
        DB::transaction(function () use ($myId, $otherId) {
            Friendship::where('user_id', $myId)->where('friend_id', $otherId)->delete();
            Friendship::where('user_id', $otherId)->where('friend_id', $myId)->delete();
        });

        return ['success' => true, 'action' => 'unfriend'];
    }

    /**
     * Get Status
     */
    public function getStatus($myId, $otherId)
    {
        // Check if friends
        $isFriend = Friendship::where('user_id', $myId)->where('friend_id', $otherId)->exists();
        if ($isFriend) {
            return ['success' => true, 'status' => 'friend'];
        }

        // Check pending sent
        $isSent = FriendRequestList::where('sender_id', $myId)->where('receiver_id', $otherId)->exists();
        if ($isSent) {
            return ['success' => true, 'status' => 'pending_sent'];
        }

        // Check pending received
        $isReceived = FriendRequestList::where('sender_id', $otherId)->where('receiver_id', $myId)->exists();
        if ($isReceived) {
            return ['success' => true, 'status' => 'pending_received'];
        }

        return ['success' => true, 'status' => 'none'];
    }

    /**
     * Block User
     */
    public function blockUser($myId, $otherId)
    {
        if ($myId == $otherId) {
            throw new \Exception('Cannot block yourself');
        }

        // Check existing
        $exists = Block::where('user_id', $myId)->where('blocked_user_id', $otherId)->exists();
        if ($exists) {
            return ['success' => true, 'action' => 'already_blocked', 'message' => 'User is already blocked'];
        }

        // Create block
        Block::create([
            'user_id' => $myId,
            'blocked_user_id' => $otherId
        ]);

        // Unfriend if friends
        $this->unfriend($myId, $otherId);

        return ['success' => true, 'action' => 'blocked'];
    }

    /**
     * Unblock User
     */
    public function unblockUser($myId, $otherId)
    {
        $deleted = Block::where('user_id', $myId)->where('blocked_user_id', $otherId)->delete();

        if ($deleted) {
            return ['success' => true, 'action' => 'unblocked'];
        } else {
            return ['success' => false, 'error' => 'User was not blocked'];
        }
    }

    /**
     * Get Blocked Users
     */
    public function getBlockedUsers($myId)
    {
        $blockedIds = Block::where('user_id', $myId)->pluck('blocked_user_id');
        
        $users = [];
        if ($blockedIds->isNotEmpty()) {
            $blockedUsers = Learner::whereIn('user_id', $blockedIds)->get(); 
            $users = $blockedUsers->map(function($u) {
                return [
                    'userId' => (string)$u->user_id,
                    'name' => $u->learner_name,
                    'image' => $u->learner_image 
                ];
            });
        }

        return ['success' => true, 'data' => $users];
    }

    /**
     * Check if blocked
     */
    public function checkBlockStatus($myId, $otherId)
    {
        $blockedByMe = Block::where('user_id', $myId)->where('blocked_user_id', $otherId)->exists();
        $blockedByOther = Block::where('user_id', $otherId)->where('blocked_user_id', $myId)->exists();

        return [
            'success' => true,
            'blocked_by_me' => $blockedByMe,
            'blocked_by_other' => $blockedByOther,
            'is_blocked' => $blockedByMe || $blockedByOther
        ];
    }

    /**
     * Get People You May Know
     * Fetched adaptively based on:
     * 1. Mutual Friends Strategy: Friends of current friends.
     * 2. Shared Learning Interests: Users who study the same lessons/courses.
     * 3. Random/Recent Fallback: If not enough matches, suggest active users.
     */
    public function getPeopleYouMayKnow($userId, $page = 1, $limit = 20)
    {
        // 1. Get IDs of current friends, pending requests, and blocked users to exclude
        $friendIds = Friendship::where('user_id', $userId)->pluck('friend_id')->toArray();
        $sentRequestIds = FriendRequestList::where('sender_id', $userId)->pluck('receiver_id')->toArray();
        $receivedRequestIds = FriendRequestList::where('receiver_id', $userId)->pluck('sender_id')->toArray();
        
        // Use subquery for blocked users to avoid large array
        $blockedByMeIds = Block::where('user_id', $userId)->pluck('blocked_user_id')->toArray();
        $blockedByOtherIds = Block::where('blocked_user_id', $userId)->pluck('user_id')->toArray();

        $excludeIds = array_unique(array_merge(
            [$userId],
            $friendIds,
            $sentRequestIds,
            $receivedRequestIds,
            $blockedByMeIds,
            $blockedByOtherIds
        ));

        // 2. Adaptive Fetching Strategy:
        
        // A. Mutual Friends Strategy: Find friends of friends
        $mutualFriends = DB::table('friendships')
            ->whereIn('user_id', $friendIds)
            ->whereNotIn('friend_id', $excludeIds)
            ->select('friend_id', DB::raw('count(*) as mutual_count'))
            ->groupBy('friend_id')
            ->orderBy('mutual_count', 'desc')
            ->limit(50)
            ->get();

        // B. Common Studies Strategy: Find users who study same lessons
        // Get user's recent studies
        $myLessonIds = DB::table('studies')
            ->where('user_id', $userId)
            ->limit(100)
            ->pluck('lesson_id')
            ->toArray();

        $commonStudies = DB::table('studies')
            ->whereIn('lesson_id', $myLessonIds)
            ->whereNotIn('user_id', $excludeIds)
            ->select('user_id', DB::raw('count(*) as study_count'))
            ->groupBy('user_id')
            ->orderBy('study_count', 'desc')
            ->limit(50)
            ->get();

        // 3. Scoring and Ranking
        $scores = [];
        foreach ($mutualFriends as $mf) {
            $scores[$mf->friend_id] = ($scores[$mf->friend_id] ?? 0) + ($mf->mutual_count * 5); // Mutual friend weight
        }
        foreach ($commonStudies as $cs) {
            $scores[$cs->user_id] = ($scores[$cs->user_id] ?? 0) + ($cs->study_count * 2); // Shared study weight
        }

        arsort($scores);
        $candidateIds = array_keys($scores);

        // 4. Fallback Strategy: If not enough candidates, add random active users
        if (count($candidateIds) < $limit) {
            $fallbackIds = Learner::whereNotIn('user_id', array_merge($excludeIds, $candidateIds))
                ->limit($limit * 2)
                ->pluck('user_id')
                ->toArray();
            shuffle($fallbackIds);
            $candidateIds = array_merge($candidateIds, array_slice($fallbackIds, 0, $limit));
        }

        // Pagination
        $total = count($candidateIds);
        $pagedIds = array_slice($candidateIds, ($page - 1) * $limit, $limit);

        // 5. Fetch Final Results
        if (empty($pagedIds)) {
            return [
                'data' => [],
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => 0,
                    'hasMore' => false,
                ]
            ];
        }

        $users = Learner::whereIn('user_id', $pagedIds)
            ->get()
            ->keyBy('user_id');

        // Map back to maintain order
        $list = [];
        foreach ($pagedIds as $id) {
            if (isset($users[$id])) {
                $u = $users[$id];
                
                // Count mutual friends for display
                $mutualCount = DB::table('friendships')
                    ->whereIn('user_id', $friendIds)
                    ->where('friend_id', $u->user_id)
                    ->count();

                $list[] = [
                    'userName' => $u->learner_name,
                    'userImage' => $u->learner_image,
                    'phone' => (string)$u->learner_phone,
                    'userId' => (string)$u->user_id,
                    'mutualFriends' => $mutualCount,
                ];
            }
        }

        return [
            'data' => $list,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'hasMore' => (($page - 1) * $limit + count($list)) < $total,
            ]
        ];
    }

    /**
     * Search Users by Name, Email, or Phone
     */
    public function searchUsers($userId, $query, $page = 1, $limit = 20)
    {
        $searchTerm = trim($query);
        if (empty($searchTerm)) {
            return [
                'data' => [],
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => 0,
                    'hasMore' => false,
                ]
            ];
        }

        $queryBuilder = Learner::where('user_id', '!=', $userId)
            ->where(function ($q) use ($searchTerm) {
                $q->where('learner_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('learner_email', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('learner_phone', 'LIKE', "%{$searchTerm}%");
            });

        $total = $queryBuilder->count();
        $users = $queryBuilder->skip(($page - 1) * $limit)->take($limit)->get();

        // Get relationship statuses in bulk
        $targetIds = $users->pluck('user_id')->toArray();
        
        $friendships = Friendship::where('user_id', $userId)
            ->whereIn('friend_id', $targetIds)
            ->pluck('friend_id')
            ->toArray();

        $sentRequests = FriendRequestList::where('sender_id', $userId)
            ->whereIn('receiver_id', $targetIds)
            ->pluck('receiver_id')
            ->toArray();

        $receivedRequests = FriendRequestList::where('receiver_id', $userId)
            ->whereIn('sender_id', $targetIds)
            ->pluck('sender_id')
            ->toArray();

        $list = $users->map(function ($u) use ($friendships, $sentRequests, $receivedRequests) {
            $status = 'none';
            if (in_array($u->user_id, $friendships)) {
                $status = 'friend';
            } elseif (in_array($u->user_id, $sentRequests)) {
                $status = 'pending_sent';
            } elseif (in_array($u->user_id, $receivedRequests)) {
                $status = 'pending_received';
            }

            return [
                'userName' => $u->learner_name,
                'userImage' => $u->learner_image,
                'phone' => (string)$u->learner_phone,
                'email' => $u->learner_email,
                'userId' => (string)$u->user_id,
                'status' => $status,
            ];
        });

        return [
            'data' => $list,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'hasMore' => (($page - 1) * $limit + $users->count()) < $total,
            ]
        ];
    }
}
