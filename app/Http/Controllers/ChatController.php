<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ChatService;
use App\Traits\ApiResponse;

class ChatController extends Controller
{
    use ApiResponse;

    protected $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * Get messages for a conversation or single message
     */
    public function getMessages(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->errorResponse('Not authenticated', 401);
        }
        $messageId = (int)$request->input('id');
        $conversationId = (int)$request->input('conversationId');
        $major = $request->input('major'); // Default to null if not provided
        $limit = (int)$request->input('limit', 50);
        $oldestMessageId = (int)$request->input('oldestMessageId');

        if ($limit > 100) $limit = 100;
        if ($limit < 1) $limit = 50;

        try {
            if ($messageId > 0) {
                $data = $this->chatService->getMessageById($messageId);
                if (!$data) {
                    return $this->errorResponse('Message not found', 404);
                }
                return $this->successResponse($data);
            }

            if ($conversationId > 0) {
                $data = $this->chatService->getConversationMessages($conversationId, $limit, $oldestMessageId);
                return $this->successResponse($data, 200, ['limit' => $limit, 'oldest_message_id' => $oldestMessageId]);
            }

            return $this->errorResponse('Invalid parameters', 400);
        } catch (\Exception $e) {
            return $this->errorResponse('Server error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Send Message
     */
    public function sendMessage(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->errorResponse('Not authenticated', 401);
        }
        $conversationId = (int)$request->input('conversationId');
        $senderId = (int)$user->user_id;
        $major = $request->input('major', 'english');
        $messageType = $request->input('messageType', 'text');
        $messageText = $request->input('messageText', '');
        $filePath = $request->input('filePath', '');
        $fileSize = $request->input('fileSize', 0);

        if ($conversationId <= 0) {
            return $this->errorResponse('conversationId is required', 400);
        }

        try {
            $data = $this->chatService->sendMessage($conversationId, $senderId, $major, $messageType, $messageText, $filePath, $fileSize);
            return $this->successResponse($data);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if ($msg == 'Conversation not found') {
                return $this->errorResponse($msg, 404);
            }
            return $this->errorResponse('Failed to send message', 500);
        }
    }

    /**
     * Upload Image
     */
    public function uploadImage(Request $request)
    {
        if (!$request->hasFile('image')) {
            return $this->errorResponse('No image file uploaded', 400);
        }

        $file = $request->file('image');
        
        if (!$file->isValid()) {
             return $this->errorResponse('Upload failed', 400);
        }

        // Validate type
        $allowedTypes = ['jpeg', 'jpg', 'png', 'gif', 'webp'];
        if (!in_array(strtolower($file->getClientOriginalExtension()), $allowedTypes)) {
             return $this->errorResponse('Invalid image type. Allowed: JPEG, PNG, GIF, WebP', 400);
        }

        // Validate size (5MB)
        if ($file->getSize() > 5 * 1024 * 1024) {
             return $this->errorResponse('Image must not be larger than 5MB', 400);
        }

        try {
            $result = $this->chatService->uploadImage($file);
            return $this->successResponse($result);
        } catch (\Exception $e) {
            return $this->errorResponse('Upload failed: ' . $e->getMessage(), 500);
        }
    }

    public function markRead(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return $this->errorResponse('Not authenticated', 401);
            }
            $messageId = (int)$request->input('messageId');
            $conversationId = (int)$request->input('conversationId');
            $userId = (int)$user->user_id;
            $major = $request->input('major'); // Optional

            if ($messageId > 0) {
                $data = $this->chatService->markMessageRead($messageId);
                return $this->successResponse($data);
            } elseif ($conversationId > 0 && $userId > 0) {
                $data = $this->chatService->markConversationRead($conversationId, $userId);
                return $this->successResponse($data);
            } else {
                return $this->errorResponse('Either messageId or (conversationId and userId) is required', 400);
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Server error: ' . $e->getMessage(), 500);
        }
    }

    public function getConversations(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->errorResponse('Not authenticated', 401);
        }
        $conversationId = (int)$request->query('id');
        $userId = (int)$user->user_id;
        $major = $request->query('major'); // Optional

        try {
            if ($conversationId > 0) {
                // Get single conversation
                $data = $this->chatService->getConversationById($conversationId, $userId);
                if (!$data) {
                    return $this->errorResponse('Conversation not found', 404);
                }
                
                return $this->successResponse($data);

            } else {
                // Get all conversations for user
                $data = $this->chatService->getUserConversations($userId);
                return $this->successResponse($data);
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Server error: ' . $e->getMessage(), 500);
        }
    }

    public function createConversation(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->errorResponse('Not authenticated', 401);
        }
        $user1Id = (int)$user->user_id;
        $user2Id = (int)$request->input('user2Id');
        $major = $request->input('major', 'english');

        if ($user2Id <= 0) {
            return $this->errorResponse('user2Id is required', 400);
        }
        if ($user1Id === $user2Id) {
            return $this->errorResponse('Cannot create conversation with yourself'.$user1Id.' '.$user2Id, 400);
        }

        try {
            $data = $this->chatService->createConversation($user1Id, $user2Id, $major);
            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create conversation', 500);
        }
    }

    public function updateConversation(Request $request)
    {
        $id = (int)$request->input('id');
        $major = $request->input('major'); // Optional
        
        if ($id <= 0) {
            return $this->errorResponse('id is required', 400);
        }

        if (!$request->has('last_message_at')) {
            return $this->errorResponse('No valid fields to update', 400);
        }

        try {
            $data = $this->chatService->updateConversation($id, $request->all());
            if (!$data) {
                return $this->errorResponse('Conversation not found', 404);
            }
            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse('Server error: ' . $e->getMessage(), 500);
        }
    }

    public function deleteConversation(Request $request)
    {
        $id = (int)($request->input('id') ?? $request->query('id'));
        $major = $request->input('major'); // Optional

        if ($id <= 0) {
            return $this->errorResponse('id is required', 400);
        }

        try {
            $deleted = $this->chatService->deleteConversation($id);
            if ($deleted) {
                return $this->successResponse(['id' => $id, 'deleted' => true]);
            } else {
                return $this->errorResponse('Failed to delete conversation or not found', 404);
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Server error: ' . $e->getMessage(), 500);
        }
    }
}
