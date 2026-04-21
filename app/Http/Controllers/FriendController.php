<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FriendService;
use App\Traits\ApiResponse;
use App\Models\Learner;

class FriendController extends Controller
{
    use ApiResponse;

    protected $friendService;

    public function __construct(FriendService $friendService)
    {
        $this->friendService = $friendService;
    }

    /**
     * Get Incoming Friend Requests
     */
    public function getIncomingRequests(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return $this->errorResponse('Not authenticated', 401);
            }
            $userId = $user->user_id;
            $page = max(1, (int)$request->query('page', 1));
            $limit = min(50, max(1, (int)$request->query('limit', 20)));

            $result = $this->friendService->getIncomingRequests($userId, $page, $limit);

            return $this->successResponse(
                $result['data'],
                200,
                $this->paginate($result['pagination']['total'], $page, $limit)
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Server error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get Friend List
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return $this->errorResponse('Not authenticated', 401);
            }
            $userId = $user->user_id;
            $page = max(1, (int)$request->query('page', 1));
            $limit = min(50, max(1, (int)$request->query('limit', 20)));

            $result = $this->friendService->getFriendList($userId, $page, $limit);

            return $this->successResponse(
                $result['data'],
                200,
                $this->paginate($result['pagination']['total'], $page, $limit)
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Server error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Send or Unsend Friend Request
     */
    public function add(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return $this->errorResponse('Not authenticated', 401);
            }

            $myId = $user->user_id;
            $otherId = $request->input('otherId');

            if (!$otherId) {
                return $this->errorResponse('otherId required', 400);
            }

            $target = Learner::where('user_id', $otherId)
                ->orWhere('learner_phone', $otherId)
                ->first();
            if (!$target) {
                return $this->errorResponse('User not found', 404);
            }

            $result = $this->friendService->addFriend($myId, $otherId);
            return $this->successResponse($result);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Accept Friend Request
     */
    public function confirm(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return $this->errorResponse('Not authenticated', 401);
            }

            $myId = $user->user_id;
            $otherId = $request->input('otherId');

            if (!$otherId) {
                return $this->errorResponse('otherId required', 400);
            }

            $target = Learner::where('user_id', $otherId)
                ->orWhere('learner_phone', $otherId)
                ->first();
            if (!$target) {
                return $this->errorResponse('User not found', 404);
            }

            $result = $this->friendService->confirmFriend($myId, $otherId);
            return $this->successResponse($result);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Unfriend
     */
    public function unfriend(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return $this->errorResponse('Not authenticated', 401);
            }

            $myId = $user->user_id;
            $otherId = $request->input('otherId');

            if (!$otherId) {
                return $this->errorResponse('otherId required', 400);
            }

            $target = Learner::where('user_id', $otherId)
                ->orWhere('learner_phone', $otherId)
                ->first();
            if (!$target) {
                return $this->errorResponse('User not found', 404);
            }

            $result = $this->friendService->unfriend($myId, $otherId);
            return $this->successResponse($result);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get Status
     */
    public function getStatus(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return $this->errorResponse('Not authenticated', 401);
            }

            $myId = $user->user_id;
            $otherId = $request->query('otherId');

            if (!$otherId) {
                return $this->errorResponse('otherId required', 400);
            }

            $target = Learner::where('user_id', $otherId)
                ->orWhere('learner_phone', $otherId)
                ->first();
            if (!$target) {
                return $this->errorResponse('User not found', 404);
            }

            $result = $this->friendService->getStatus($myId, $otherId);
            return $this->successResponse($result);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Block User
     */
    public function block(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return $this->errorResponse('Not authenticated', 401);
            }

            $myId = $user->user_id;
            $otherId = $request->input('otherId');

            if (!$otherId) {
                return $this->errorResponse('otherId required', 400);
            }

            $target = Learner::where('user_id', $otherId)
                ->orWhere('learner_phone', $otherId)
                ->first();
            if (!$target) {
                return $this->errorResponse('User not found', 404);
            }

            $result = $this->friendService->blockUser($myId, $otherId);
            return $this->successResponse($result);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Unblock User
     */
    public function unblock(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return $this->errorResponse('Not authenticated', 401);
            }

            $myId = $user->user_id;
            $otherId = $request->input('otherId');

            if (!$otherId) {
                return $this->errorResponse('otherId required', 400);
            }

            $target = Learner::where('user_id', $otherId)
                ->orWhere('learner_phone', $otherId)
                ->first();
            if (!$target) {
                return $this->errorResponse('User not found', 404);
            }

            $result = $this->friendService->unblockUser($myId, $otherId);
            return $this->successResponse($result);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get Blocked Users
     */
    public function getBlocked(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return $this->errorResponse('Not authenticated', 401);
            }

            $myId = $user->user_id;
            $result = $this->friendService->getBlockedUsers($myId);
            return $this->successResponse($result);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Check if blocked
     */
    public function checkBlock(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return $this->errorResponse('Not authenticated', 401);
            }

            $myId = $user->user_id;
            $otherId = $request->query('otherId');

            if (!$otherId) {
                return $this->errorResponse('otherId required', 400);
            }

            $target = Learner::where('user_id', $otherId)
                ->orWhere('learner_phone', $otherId)
                ->first();
            if (!$target) {
                return $this->errorResponse('User not found', 404);
            }

            $result = $this->friendService->checkBlockStatus($myId, $otherId);
            return $this->successResponse($result);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get People You May Know
     */
    public function peopleYouMayKnow(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return $this->errorResponse('Not authenticated', 401);
            }
            $userId = $user->user_id;
            $page = max(1, (int)$request->query('page', 1));
            $limit = min(50, max(1, (int)$request->query('limit', 20)));

            $result = $this->friendService->getPeopleYouMayKnow($userId, $page, $limit);

            return $this->successResponse(
                $result['data'],
                200,
                $this->paginate($result['pagination']['total'], $page, $limit)
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Server error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Search Users
     */
    public function search(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return $this->errorResponse('Not authenticated', 401);
            }

            $query = $request->query('query');
            if (empty($query)) {
                return $this->errorResponse('Search query required', 400);
            }

            $userId = $user->user_id;
            $page = max(1, (int)$request->query('page', 1));
            $limit = min(50, max(1, (int)$request->query('limit', 20)));

            $result = $this->friendService->searchUsers($userId, $query, $page, $limit);

            return $this->successResponse(
                $result['data'],
                200,
                $this->paginate($result['pagination']['total'], $page, $limit)
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Server error: ' . $e->getMessage(), 500);
        }
    }
}
