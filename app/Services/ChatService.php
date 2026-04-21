<?php

namespace App\Services;

use App\Notifications\NewChatMessageNotification;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\Learner;
use App\Models\Block;
use App\Models\UserData;
use App\Services\NotificationDispatchService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChatService
{
    private const SUPPORT_ADMIN_USER_ID = 10000;

    private NotificationDispatchService $dispatch;

    public function __construct(NotificationDispatchService $dispatch)
    {
        $this->dispatch = $dispatch;
    }

    public function getMessageById($messageId)
    {
        $query = Message::where('id', $messageId);
        $message = $query->first();

        if (!$message) {
            return null;
        }
        
        return $this->convertTimestamps($message->toArray());
    }

    public function getConversationMessages($conversationId, $limit = 50, $oldestMessageId = 0)
    {
        $query = Message::where('conversation_id', $conversationId);

        if ($oldestMessageId > 0) {
            $query->where('id', '<', $oldestMessageId);
        }

        $messages = $query->orderBy('id', 'desc')
            ->limit($limit)
            ->get();

        return $messages->map(function ($msg) {
            return $this->convertTimestamps($msg->toArray());
        });
    }

    public function sendMessage($conversationId, $senderId, $major, $messageType, $messageText, $filePath, $fileSize)
    {
        // Validate conversation exists
        $conversation = Conversation::where('id', $conversationId)->first();
        if (!$conversation) {
            throw new \Exception('Conversation not found');
        }

        // Determine receiver
        $receiverId = ($conversation->user1_id == $senderId) ? $conversation->user2_id : $conversation->user1_id;

        // Create Message
        $message = new Message();
        $message->conversation_id = $conversationId;
        $message->sender_id = $senderId;
        $message->major = $major;
        $message->message_type = $messageType;
        $message->message_text = $messageText; // Can be empty if file
        $message->file_path = $filePath;
        $message->file_size = $fileSize;
        $message->is_read = 0;
        
        if ($message->save()) {
            // Update conversation last_message_at
            $conversation->last_message_at = now();
            $conversation->save();

            // Send Push Notification to receiver
            $receiver = Learner::where('user_id', $receiverId)->first();
            $sender = Learner::where('user_id', $senderId)->first();

            if ((int) $receiverId === self::SUPPORT_ADMIN_USER_ID && $sender) {
                $senderName = $sender->learner_name ?? 'Someone';
                $senderImage = $sender->learner_image ?? '';
                $preview = $messageText ?: ($messageType === 'image' ? 'Sent an image' : 'Sent a file');

                $this->dispatch->notifyAdminDatabase([
                    'type' => 'chat.message',
                    'actor' => [
                        'userId' => (int) $senderId,
                        'name' => (string) $senderName,
                        'image' => (string) $senderImage,
                    ],
                    'target' => [
                        'conversationId' => (int) $conversationId,
                    ],
                    'navigation' => [
                        'routeName' => 'SupportChat',
                        'params' => [
                            'conversationId' => (string) $conversationId,
                        ],
                    ],
                ], 'App\\Notifications\\SupportChatMessage');

                $this->dispatch->pushToAdminTopicByMajor(
                    (string) $major,
                    (string) $senderName,
                    (string) $preview,
                    [
                        'type' => 'chat.support',
                        'conversationId' => (string) $conversationId,
                        'senderId' => (string) $senderId,
                    ],
                    $senderImage !== '' ? $senderImage : null
                );
            } elseif ($receiver && $sender) {
                $receiver->notify(new NewChatMessageNotification(
                    senderId: (int) $senderId,
                    senderName: $sender->learner_name ?? 'Someone',
                    senderImage: $sender->learner_image ?? '',
                    messageText: $messageText ?: ($messageType === 'image' ? 'Sent an image' : 'Sent a file'),
                    conversationId: (int) $conversationId
                ));
            }

            return $this->convertTimestamps($message->toArray());
        } else {
            throw new \Exception('Failed to send message');
        }
    }

    public function uploadImage($file)
    {
        $fileSize = $file->getSize();

        // Generate filename
        $filename = Str::random(15) . '.' . $file->getClientOriginalExtension();
        
        // Save
        $path = 'chat/images';
        $fullPath = \Illuminate\Support\Facades\Storage::disk('uploads')->putFileAs($path, $file, $filename);
        
        return [
            'file_path' => env('APP_URL') .'/'. \Illuminate\Support\Facades\Storage::disk('uploads')->url($fullPath),
            'url' => env('APP_URL') .'/'. \Illuminate\Support\Facades\Storage::disk('uploads')->url($fullPath),
            'file_size' => $fileSize
        ];
    }

    public function markMessageRead($messageId)
    {
        $query = Message::where('id', $messageId);
        $updated = $query->update(['is_read' => 1]);
        
        if (!$updated) {
            throw new \Exception('Failed to mark message as read');
        }
        
        return ['message_id' => $messageId, 'is_read' => true];
    }

    public function markConversationRead($conversationId, $userId)
    {
        $query = Message::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $userId)
            ->where('is_read', 0);

        $updated = $query->update(['is_read' => 1]);
        
        $countQuery = Message::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $userId)
            ->where('is_read', 1);
        
        $count = $countQuery->count();
        
        return [
            'conversation_id' => $conversationId,
            'marked_count' => $count
        ];
    }

    public function getConversationById($conversationId, $userId)
    {
        $query = Conversation::where('id', $conversationId);
        $conversation = $query->first();

        if (!$conversation) {
            return null;
        }

        $convData = $conversation->toArray();
        $convData['other_user_id'] = ($convData['user1_id'] == $userId) ? $convData['user2_id'] : $convData['user1_id'];

        // Fetch friend profile
        $friend = Learner::where('user_id', $convData['other_user_id'])->first();
        
        $friendProfile = null;
        if ($friend) {
            $friendProfile = $this->buildFriendProfile($friend, $userId);
        }
        $convData['friend'] = $friendProfile;
        
        return $this->convertTimestamps($convData);
    }

    public function getUserConversations($userId)
    {
        // Get all conversations for user
        $query = Conversation::where(function($q) use ($userId) {
                $q->where('user1_id', $userId)
                  ->orWhere('user2_id', $userId);
            });

        $conversations = $query->orderBy('last_message_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($conversations->isEmpty()) {
            return [];
        }

        // Collect IDs
        $conversationIds = $conversations->pluck('id')->toArray();
        $friendIds = [];
        foreach ($conversations as $conv) {
            $friendId = ($conv->user1_id == $userId) ? $conv->user2_id : $conv->user1_id;
            if ($friendId > 0) $friendIds[] = $friendId;
        }
        $friendIds = array_unique($friendIds);

        // Batch fetch unread counts
        $unreadQuery = DB::table('messages')
            ->select('conversation_id', DB::raw('COUNT(*) as unread_count'))
            ->whereIn('conversation_id', $conversationIds)
            ->where('sender_id', '!=', $userId)
            ->where('is_read', 0);
        
        $unreadCounts = $unreadQuery->groupBy('conversation_id')
            ->pluck('unread_count', 'conversation_id');

        // Batch fetch last messages
        $latestMsgIdsQuery = DB::table('messages')
            ->select(DB::raw('MAX(id) as max_id'))
            ->whereIn('conversation_id', $conversationIds);
        
        $latestMsgIds = $latestMsgIdsQuery->groupBy('conversation_id');
        
        $lastMessages = DB::table('messages')
            ->joinSub($latestMsgIds, 'latest', function($join) {
                $join->on('messages.id', '=', 'latest.max_id');
            })
            ->select('messages.conversation_id', 'messages.message_text', 'messages.message_type')
            ->get()
            ->keyBy('conversation_id');

        // Batch fetch learner profiles
        $learners = Learner::whereIn('user_id', $friendIds)->get()->keyBy('user_id');

        // Batch fetch FCM tokens
        $fcmTokens = $this->getFcmTokensBatch($friendIds);

        // Batch fetch block statuses
        $blockStatuses = $this->getBlockStatusesBatch($userId, $friendIds);

        // Combine data
        return $conversations->map(function ($conv) use ($userId, $unreadCounts, $lastMessages, $learners, $fcmTokens, $blockStatuses) {
            $convData = $conv->toArray();
            $otherUserId = ($conv->user1_id == $userId) ? $conv->user2_id : $conv->user1_id;
            $convData['other_user_id'] = $otherUserId;

            $convData['unread_count'] = $unreadCounts[$conv->id] ?? 0;
            
            $lastMsg = $lastMessages[$conv->id] ?? null;
            $convData['last_message_text'] = $lastMsg ? $lastMsg->message_text : null;
            $convData['last_message_type'] = $lastMsg ? $lastMsg->message_type : null;

            $friendProfile = null;
            if ($otherUserId > 0 && isset($learners[$otherUserId])) {
                $l = $learners[$otherUserId];
                $isBlockedByMe = $blockStatuses[$otherUserId]['blocked_by_me'] ?? false;
                $isBlockedByOther = $blockStatuses[$otherUserId]['blocked_by_other'] ?? false;

                $friendProfile = [
                    'phone' => $l->learner_phone,
                    'name' => $l->learner_name,
                    'image' => $l->learner_image,
                    'fcm_token' => $fcmTokens[$otherUserId] ?? null,
                    'blocked' => $isBlockedByMe || $isBlockedByOther,
                    'blocked_by_me' => $isBlockedByMe,
                    'blocked_by_other' => $isBlockedByOther,
                ];
            }
            $convData['friend'] = $friendProfile;

            return $this->convertTimestamps($convData);
        });
    }

    public function createConversation($user1Id, $user2Id, $major)
    {
        $originalCallerId = $user1Id;
        if ($user1Id > $user2Id) {
            $temp = $user1Id;
            $user1Id = $user2Id;
            $user2Id = $temp;
        }

        $friendId = ($originalCallerId === $user1Id) ? $user2Id : $user1Id;

        // Check existing
        $existing = Conversation::where('user1_id', $user1Id)
            ->where('user2_id', $user2Id)
            ->first();

        if ($existing) {
            return $this->formatConversationResponse($existing, $friendId, $originalCallerId);
        }

        // Create new
        try {
            $conversation = new Conversation();
            $conversation->user1_id = $user1Id;
            $conversation->user2_id = $user2Id;
            $conversation->major = $major;
            $conversation->save();

            return $this->formatConversationResponse($conversation, $friendId, $originalCallerId);

        } catch (\Exception $e) {
            // Check for duplicate entry race condition
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $existing = Conversation::where('user1_id', $user1Id)
                    ->where('user2_id', $user2Id)
                    ->first();
                if ($existing) {
                    return $this->formatConversationResponse($existing, $friendId, $originalCallerId);
                }
            }
            throw new \Exception('Failed to create conversation');
        }
    }

    public function updateConversation($id, $data)
    {
        $query = Conversation::where('id', $id);
        $conversation = $query->first();
        if (!$conversation) {
            return null;
        }

        if (isset($data['last_message_at'])) {
            $conversation->last_message_at = $data['last_message_at'];
            $conversation->save();
        }

        return $this->convertTimestamps($conversation->toArray());
    }

    public function deleteConversation($id)
    {
        $query = Conversation::where('id', $id);
        return $query->delete();
    }

    // Helpers

    private function formatConversationResponse($conversation, $friendId, $currentUserId)
    {
        $convData = $conversation->toArray();
        
        $friend = Learner::where('user_id', $friendId)->first();
        $friendProfile = null;
        if ($friend) {
            $friendProfile = $this->buildFriendProfile($friend, $currentUserId);
        }
        $convData['friend'] = $friendProfile;

        return $this->convertTimestamps($convData);
    }

    private function buildFriendProfile($friend, $currentUserId)
    {
        $fcmToken = $this->getFcmToken($friend->user_id);
        $blockStatus = $this->getBlockStatus($currentUserId, $friend->user_id);

        return [
            'phone' => $friend->learner_phone,
            'name' => $friend->learner_name,
            'image' => $friend->learner_image,
            'fcm_token' => $fcmToken,
            'blocked' => $blockStatus['blocked_by_me'] || $blockStatus['blocked_by_other'],
            'blocked_by_me' => $blockStatus['blocked_by_me'],
            'blocked_by_other' => $blockStatus['blocked_by_other'],
        ];
    }

    private function getFcmToken($userId)
    {
        $token = UserData::where('user_id', $userId)
            ->whereNotNull('token')
            ->where('token', '!=', '')
            ->orderBy('updated_at', 'desc')
            ->value('token');

        return $this->pickFcmToken($token);
    }

    private function getFcmTokensBatch($userIds)
    {
        if (empty($userIds)) return [];

        $userDataList = UserData::whereIn('user_id', $userIds)
            ->whereNotNull('token')
            ->where('token', '!=', '')
            ->orderBy('updated_at', 'desc')
            ->get(['user_id', 'token']);

        $tokens = [];
        foreach ($userDataList as $ud) {
            if (!isset($tokens[$ud->user_id])) {
                $tokens[$ud->user_id] = $ud->token;
            }
        }

        return array_map(function ($token) {
            return $this->pickFcmToken($token);
        }, $tokens);
    }

    private function pickFcmToken($token): ?string
    {
        if (empty($token)) {
            return null;
        }

        if (is_array($token)) {
            return $token['android'] ?? $token['ios'] ?? array_values($token)[0] ?? null;
        }

        if (is_string($token)) {
            $decoded = json_decode($token, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded['android'] ?? $decoded['ios'] ?? array_values($decoded)[0] ?? null;
            }
            return $token;
        }

        return null;
    }

    private function getBlockStatus($userId, $otherUserId)
    {
        $blockedByMe = Block::where('user_id', $userId)->where('blocked_user_id', $otherUserId)->exists();
        $blockedByOther = Block::where('user_id', $otherUserId)->where('blocked_user_id', $userId)->exists();

        return ['blocked_by_me' => $blockedByMe, 'blocked_by_other' => $blockedByOther];
    }

    private function getBlockStatusesBatch($userId, $otherUserIds)
    {
        if (empty($otherUserIds)) return [];

        $blockedByMe = Block::where('user_id', $userId)
            ->whereIn('blocked_user_id', $otherUserIds)
            ->pluck('blocked_user_id')
            ->toArray();

        $blockedByOther = Block::whereIn('user_id', $otherUserIds)
            ->where('blocked_user_id', $userId)
            ->pluck('user_id')
            ->toArray();

        $statuses = [];
        foreach ($otherUserIds as $id) {
            $statuses[$id] = [
                'blocked_by_me' => in_array($id, $blockedByMe),
                'blocked_by_other' => in_array($id, $blockedByOther),
            ];
        }
        return $statuses;
    }

    private function convertTimestamps($data)
    {
        $fields = ['created_at', 'updated_at', 'last_message_at'];
        foreach ($fields as $field) {
            if (isset($data[$field]) && $data[$field] !== null) {
                $val = $data[$field];
                if (is_numeric($val)) {
                    continue; 
                }
                
                $timestamp = strtotime($val);
                if ($timestamp !== false) {
                    $data[$field] = $timestamp * 1000;
                }
            }
        }
        return $data;
    }
}
