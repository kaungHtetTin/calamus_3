<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Learner;
use App\Models\Message;
use App\Services\NotificationDispatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SupportChatController extends Controller
{
    private const SUPPORT_ADMIN_USER_ID = 10000;

    public function index(Request $request): Response
    {
        return Inertia::render('Admin/SupportChat', [
            'supportAdminUserId' => self::SUPPORT_ADMIN_USER_ID,
            'selectedConversationId' => (int) $request->query('conversationId', 0) ?: null,
            'conversations' => [
                'data' => [],
            ],
            'messages' => [],
        ]);
    }

    public function send(Request $request, NotificationDispatchService $dispatch): JsonResponse
    {
        $data = $request->validate([
            'conversationId' => ['required', 'integer', 'min:1'],
            'messageText' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'file', 'image', 'max:10240'],
        ]);

        $conversationId = (int) $data['conversationId'];
        $messageText = trim((string) ($data['messageText'] ?? ''));
        $file = $request->file('image');
        if (!$file instanceof UploadedFile && $messageText === '') {
            return response()->json(['message' => 'messageText or image is required.'], 422);
        }

        $conversation = Conversation::query()->find($conversationId);
        if (!$conversation) {
            return response()->json(['message' => 'Conversation not found.'], 404);
        }

        $isSupportConversation = (trim((string) ($conversation->user1_id ?? '')) === (string) self::SUPPORT_ADMIN_USER_ID)
            || (trim((string) ($conversation->user2_id ?? '')) === (string) self::SUPPORT_ADMIN_USER_ID);
        if (!$isSupportConversation) {
            return response()->json(['message' => 'Not a support conversation.'], 403);
        }

        $major = (string) ($conversation->major ?? 'english');

        $messageType = 'text';
        $fileUrl = '';
        $fileSize = 0;

        if ($file instanceof UploadedFile) {
            [$fileUrl, $fileSize] = $this->storeChatImage($file);
            $messageType = 'image';
        }

        $msg = new Message();
        $msg->conversation_id = $conversationId;
        $msg->sender_id = self::SUPPORT_ADMIN_USER_ID;
        $msg->major = $major;
        $msg->message_type = $messageType;
        $msg->message_text = $messageType === 'image' ? $messageText : $messageText;
        $msg->file_path = $fileUrl;
        $msg->file_size = $fileSize;
        $msg->is_read = 0;
        $msg->save();

        $conversation->last_message_at = now();
        $conversation->save();

        $receiverId = trim((string) ($conversation->user1_id ?? '')) === (string) self::SUPPORT_ADMIN_USER_ID
            ? trim((string) ($conversation->user2_id ?? ''))
            : trim((string) ($conversation->user1_id ?? ''));

        if ($receiverId !== '' && $receiverId !== '0' && ctype_digit($receiverId)) {
            $pushBody = $messageText !== '' ? $messageText : ($messageType === 'image' ? 'Sent an image' : '');
            $payload = [
                'type' => 'chat.message',
                'conversationId' => (string) $conversationId,
                'friendId' => (string) self::SUPPORT_ADMIN_USER_ID,
            ];

            if ($fileUrl !== '') {
                $dispatch->queuePushToUserTokens($receiverId, 'Support', $pushBody, $payload, $fileUrl);
            } else {
                $dispatch->queuePushToUserTokens($receiverId, 'Support', $pushBody, $payload);
            }
        }

        return response()->json([
            'message' => $this->convertTimestamps([
                'id' => (int) $msg->id,
                'conversation_id' => (int) $msg->conversation_id,
                'sender_id' => (int) $msg->sender_id,
                'major' => (string) ($msg->major ?? ''),
                'message_type' => (string) ($msg->message_type ?? 'text'),
                'message_text' => (string) ($msg->message_text ?? ''),
                'file_path' => (string) ($msg->file_path ?? ''),
                'file_size' => (int) ($msg->file_size ?? 0),
                'is_read' => (int) ($msg->is_read ?? 0),
                'created_at' => $msg->created_at,
                'updated_at' => $msg->updated_at,
            ]),
            'conversation' => $this->convertTimestamps([
                'id' => (int) $conversation->id,
                'last_message_at' => $conversation->last_message_at,
            ]),
        ]);
    }

    public function sendImage(Request $request, NotificationDispatchService $dispatch): JsonResponse
    {
        return $this->send($request, $dispatch);
    }

    public function conversations(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('perPage', 25);
        if ($perPage < 10) {
            $perPage = 10;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        $conversationQuery = Conversation::query()
            ->where(function ($q) {
                $q->where('user1_id', self::SUPPORT_ADMIN_USER_ID)
                    ->orWhere('user2_id', self::SUPPORT_ADMIN_USER_ID);
            });

        $conversationsPaginator = $conversationQuery
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $conversations = $conversationsPaginator->getCollection();

        $conversationIds = $conversations->pluck('id')->map(fn ($v) => (int) $v)->all();
        $otherUserIds = $conversations
            ->map(function ($c) {
                $user1 = trim((string) ($c->user1_id ?? ''));
                $user2 = trim((string) ($c->user2_id ?? ''));
                return $user1 === (string) self::SUPPORT_ADMIN_USER_ID ? $user2 : $user1;
            })
            ->filter(fn ($id) => $id !== '' && $id !== '0' && ctype_digit($id))
            ->unique()
            ->values()
            ->all();

        $learnersById = [];
        if (!empty($otherUserIds)) {
            $learnersById = Learner::query()
                ->whereIn('user_id', $otherUserIds)
                ->get(['user_id', 'learner_name', 'learner_image', 'learner_phone', 'learner_email'])
                ->keyBy('user_id')
                ->all();
        }

        $unreadCounts = [];
        if (!empty($conversationIds)) {
            $unreadCounts = DB::table('messages')
                ->select('conversation_id', DB::raw('COUNT(*) as unread_count'))
                ->whereIn('conversation_id', $conversationIds)
                ->where('sender_id', '!=', self::SUPPORT_ADMIN_USER_ID)
                ->where('is_read', 0)
                ->groupBy('conversation_id')
                ->pluck('unread_count', 'conversation_id')
                ->toArray();
        }

        $lastMessages = [];
        if (!empty($conversationIds)) {
            $latestMsgIds = DB::table('messages')
                ->select(DB::raw('MAX(id) as max_id'))
                ->whereIn('conversation_id', $conversationIds)
                ->groupBy('conversation_id');

            $lastMessages = DB::table('messages')
                ->joinSub($latestMsgIds, 'latest', function ($join) {
                    $join->on('messages.id', '=', 'latest.max_id');
                })
                ->select('messages.conversation_id', 'messages.message_text', 'messages.message_type', 'messages.file_path')
                ->get()
                ->keyBy('conversation_id')
                ->all();
        }

        $conversationsForUi = $conversations->map(function ($c) use ($learnersById, $unreadCounts, $lastMessages) {
            $user1 = trim((string) ($c->user1_id ?? ''));
            $user2 = trim((string) ($c->user2_id ?? ''));
            $otherUserId = $user1 === (string) self::SUPPORT_ADMIN_USER_ID ? $user2 : $user1;
            $learner = $learnersById[$otherUserId] ?? null;
            $last = $lastMessages[(int) $c->id] ?? null;

            $data = [
                'id' => (int) $c->id,
                'major' => (string) ($c->major ?? ''),
                'other_user_id' => (string) $otherUserId,
                'unread_count' => (int) ($unreadCounts[(int) $c->id] ?? 0),
                'last_message_text' => $last ? (string) ($last->message_text ?? '') : '',
                'last_message_type' => $last ? (string) ($last->message_type ?? '') : '',
                'last_message_file_path' => $last ? (string) ($last->file_path ?? '') : '',
                'last_message_at' => $c->last_message_at,
                'created_at' => $c->created_at,
                'updated_at' => $c->updated_at,
                'friend' => $learner ? [
                    'id' => (string) ($learner->user_id ?? ''),
                    'name' => (string) ($learner->learner_name ?? ''),
                    'image' => (string) ($learner->learner_image ?? ''),
                    'phone' => (string) ($learner->learner_phone ?? ''),
                    'email' => (string) ($learner->learner_email ?? ''),
                ] : null,
            ];

            return $this->convertTimestamps($data);
        })->values();

        $conversationsPaginator->setCollection($conversationsForUi);

        return response()->json($conversationsPaginator);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        if (!Schema::hasTable('messages') || !Schema::hasTable('conversations')) {
            return response()->json(['unread' => 0]);
        }

        $unread = (int) DB::table('messages as m')
            ->join('conversations as c', 'c.id', '=', 'm.conversation_id')
            ->where('m.is_read', 0)
            ->where('m.sender_id', '!=', self::SUPPORT_ADMIN_USER_ID)
            ->where(function ($q) {
                $q->where('c.user1_id', self::SUPPORT_ADMIN_USER_ID)->orWhere('c.user2_id', self::SUPPORT_ADMIN_USER_ID);
            })
            ->count();

        return response()->json(['unread' => $unread]);
    }

    public function conversation(Request $request): JsonResponse
    {
        $otherUserId = trim((string) $request->query('otherUserId', ''));
        $major = 'english';

        if ($otherUserId === '' || $otherUserId === '0' || !ctype_digit($otherUserId)) {
            return response()->json(['message' => 'otherUserId is required'], 422);
        }

       
        $query = Conversation::query()
            ->where(function ($q) use ($otherUserId) {
                $q->where(function ($q2) use ($otherUserId) {
                    $q2->where('user1_id', self::SUPPORT_ADMIN_USER_ID)->where('user2_id', $otherUserId);
                })->orWhere(function ($q2) use ($otherUserId) {
                    $q2->where('user2_id', self::SUPPORT_ADMIN_USER_ID)->where('user1_id', $otherUserId);
                });
            });

        if ($major !== '') {
            $query->whereRaw("LOWER(TRIM(COALESCE(major, ''))) = ?", [$major]);
        }

        $conversation = $query->orderByDesc('last_message_at')->orderByDesc('created_at')->first();

        if (!$conversation) {
            $conversation = Conversation::query()
                ->where(function ($q) use ($otherUserId) {
                    $q->where(function ($q2) use ($otherUserId) {
                        $q2->where('user1_id', self::SUPPORT_ADMIN_USER_ID)->where('user2_id', $otherUserId);
                    })->orWhere(function ($q2) use ($otherUserId) {
                        $q2->where('user2_id', self::SUPPORT_ADMIN_USER_ID)->where('user1_id', $otherUserId);
                    });
                })
                ->orderByDesc('last_message_at')
                ->orderByDesc('created_at')
                ->first();
        }

        if ($conversation) {
            $existingMajor = strtolower(trim((string) ($conversation->major ?? '')));
            if ($existingMajor !== $major) {
                $conversation->major = $major;
                $conversation->save();
            }
        } else {
            try {
                $conversation = Conversation::query()->create([
                    'user1_id' => self::SUPPORT_ADMIN_USER_ID,
                    'user2_id' => $otherUserId,
                    'major' => $major,
                    'last_message_at' => null,
                ]);
            } catch (\Throwable $e) {
                $conversation = Conversation::query()
                    ->where(function ($q) use ($otherUserId) {
                        $q->where(function ($q2) use ($otherUserId) {
                            $q2->where('user1_id', self::SUPPORT_ADMIN_USER_ID)->where('user2_id', $otherUserId);
                        })->orWhere(function ($q2) use ($otherUserId) {
                            $q2->where('user2_id', self::SUPPORT_ADMIN_USER_ID)->where('user1_id', $otherUserId);
                        });
                    })
                    ->orderByDesc('last_message_at')
                    ->orderByDesc('created_at')
                    ->first();

                if ($conversation) {
                    $existingMajor = strtolower(trim((string) ($conversation->major ?? '')));
                    if ($existingMajor !== $major) {
                        $conversation->major = $major;
                        $conversation->save();
                    }
                }
            }
        }

        if (!$conversation) {
            return response()->json(['message' => 'Conversation not found.'], 404);
        }

        $conversationId = (int) $conversation->id;

        $learner = Learner::query()
            ->where('user_id', $otherUserId)
            ->first(['user_id', 'learner_name', 'learner_image', 'learner_phone', 'learner_email']);

        $unread = 0;
        if (Schema::hasTable('messages')) {
            $unread = (int) DB::table('messages')
                ->where('conversation_id', $conversationId)
                ->where('sender_id', '!=', self::SUPPORT_ADMIN_USER_ID)
                ->where('is_read', 0)
                ->count();
        }

        $last = null;
        if (Schema::hasTable('messages')) {
            $last = DB::table('messages')
                ->where('conversation_id', $conversationId)
                ->orderByDesc('id')
                ->first(['message_text', 'message_type', 'file_path']);
        }

        $payload = [
            'id' => $conversationId,
            'major' => (string) ($conversation->major ?? ''),
            'other_user_id' => (string) $otherUserId,
            'unread_count' => $unread,
            'last_message_text' => $last ? (string) ($last->message_text ?? '') : '',
            'last_message_type' => $last ? (string) ($last->message_type ?? '') : '',
            'last_message_file_path' => $last ? (string) ($last->file_path ?? '') : '',
            'last_message_at' => $conversation->last_message_at,
            'created_at' => $conversation->created_at,
            'updated_at' => $conversation->updated_at,
            'friend' => $learner ? [
                'id' => (string) ($learner->user_id ?? ''),
                'name' => (string) ($learner->learner_name ?? ''),
                'image' => (string) ($learner->learner_image ?? ''),
                'phone' => (string) ($learner->learner_phone ?? ''),
                'email' => (string) ($learner->learner_email ?? ''),
            ] : null,
        ];

        return response()->json($this->convertTimestamps($payload));
    }

    public function messages(Request $request): JsonResponse
    {
        $conversationId = (int) $request->query('conversationId', 0);
        $beforeId = (int) $request->query('beforeId', 0);
        $afterId = (int) $request->query('afterId', 0);
        $limit = (int) $request->query('limit', 50);
        if ($limit < 1) {
            $limit = 50;
        }
        if ($limit > 100) {
            $limit = 100;
        }

        if ($conversationId <= 0) {
            return response()->json(['message' => 'conversationId is required'], 422);
        }

        $conversation = Conversation::query()->find($conversationId);
        if (!$conversation) {
            return response()->json(['message' => 'Conversation not found.'], 404);
        }

        $isSupportConversation = (trim((string) ($conversation->user1_id ?? '')) === (string) self::SUPPORT_ADMIN_USER_ID)
            || (trim((string) ($conversation->user2_id ?? '')) === (string) self::SUPPORT_ADMIN_USER_ID);
        if (!$isSupportConversation) {
            return response()->json(['message' => 'Not a support conversation.'], 403);
        }

        Message::query()
            ->where('conversation_id', $conversationId)
            ->where('sender_id', '!=', self::SUPPORT_ADMIN_USER_ID)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        $query = Message::query()->where('conversation_id', $conversationId);
        if ($afterId > 0) {
            $query->where('id', '>', $afterId);
        } elseif ($beforeId > 0) {
            $query->where('id', '<', $beforeId);
        }

        $order = $afterId > 0 ? 'asc' : 'desc';

        $msgs = $query
            ->orderBy('id', $order)
            ->limit($limit)
            ->get([
                'id',
                'conversation_id',
                'sender_id',
                'major',
                'message_type',
                'message_text',
                'file_path',
                'file_size',
                'is_read',
                'created_at',
                'updated_at',
            ]);

        if ($afterId <= 0) {
            $msgs = $msgs->reverse()->values();
        }

        $data = $msgs->map(function ($m) {
            return $this->convertTimestamps([
                'id' => (int) $m->id,
                'conversation_id' => (int) $m->conversation_id,
                'sender_id' => (int) $m->sender_id,
                'major' => (string) ($m->major ?? ''),
                'message_type' => (string) ($m->message_type ?? 'text'),
                'message_text' => (string) ($m->message_text ?? ''),
                'file_path' => (string) ($m->file_path ?? ''),
                'file_size' => (int) ($m->file_size ?? 0),
                'is_read' => (int) ($m->is_read ?? 0),
                'created_at' => $m->created_at,
                'updated_at' => $m->updated_at,
            ]);
        })->all();

        $oldestId = $msgs->isNotEmpty() ? (int) $msgs->first()->id : null;
        $newestId = $msgs->isNotEmpty() ? (int) $msgs->last()->id : null;

        return response()->json([
            'data' => $data,
            'meta' => [
                'nextBeforeId' => $oldestId,
                'newestId' => $newestId,
            ],
        ]);
    }

    private function convertTimestamps(array $data): array
    {
        foreach (['created_at', 'updated_at', 'last_message_at'] as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === null) {
                continue;
            }

            $val = $data[$field];
            if (is_numeric($val)) {
                $data[$field] = (int) $val;
                continue;
            }

            $timestamp = strtotime((string) $val);
            if ($timestamp !== false) {
                $data[$field] = $timestamp * 1000;
            }
        }

        return $data;
    }

    private function storeChatImage(UploadedFile $file): array
    {
        $filename = Str::random(15) . '.' . $file->getClientOriginalExtension();
        $path = 'chat/images';
        $storedPath = Storage::disk('uploads')->putFileAs($path, $file, $filename);
        $urlPath = Storage::disk('uploads')->url($storedPath);
        $baseUrl = (string) (config('app.url') ?: env('APP_URL') ?: '');
        $baseUrl = rtrim($baseUrl, '/');
        $fileUrl = $baseUrl !== '' ? $baseUrl . '/' . ltrim($urlPath, '/') : $urlPath;
        $fileSize = (int) ($file->getSize() ?? 0);

        return [$fileUrl, $fileSize];
    }
}
