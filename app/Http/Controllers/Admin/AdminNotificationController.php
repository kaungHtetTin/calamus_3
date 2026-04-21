<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;

class AdminNotificationController extends Controller
{
    private const ADMIN_NOTIFICATION_USER_ID = 10000;
    private const NOTIFIABLE_TYPE = 'App\\Models\\Learner';

    public function index(Request $request): Response
    {
        $limit = min(100, max(10, (int) $request->query('limit', 20)));
        $page = max(1, (int) $request->query('page', 1));

        $query = DatabaseNotification::query()
            ->where('notifiable_type', self::NOTIFIABLE_TYPE)
            ->where('notifiable_id', self::ADMIN_NOTIFICATION_USER_ID)
            ->orderByDesc('created_at');

        $paginator = $query->paginate($limit, ['*'], 'page', $page)->withQueryString();

        $items = $paginator->getCollection()->map(function (DatabaseNotification $n) {
            $data = is_array($n->data) ? $n->data : [];
            $adminLink = $this->buildAdminLink($data);

            return [
                'id' => (string) $n->id,
                'type' => (string) ($data['type'] ?? 'unknown'),
                'rawType' => (string) ($n->type ?? ''),
                'actor' => $data['actor'] ?? null,
                'target' => $data['target'] ?? null,
                'navigation' => $data['navigation'] ?? null,
                'metadata' => $data['metadata'] ?? [],
                'readAt' => $n->read_at?->toIso8601String(),
                'createdAt' => $n->created_at?->toIso8601String(),
                'adminLink' => $adminLink,
            ];
        })->values();

        $paginator->setCollection($items);

        $unreadCount = DatabaseNotification::query()
            ->where('notifiable_type', self::NOTIFIABLE_TYPE)
            ->where('notifiable_id', self::ADMIN_NOTIFICATION_USER_ID)
            ->whereNull('read_at')
            ->count();

        return Inertia::render('Admin/Notifications', [
            'notifications' => $paginator,
            'unreadCount' => (int) $unreadCount,
        ]);
    }

    public function markOneRead(Request $request): RedirectResponse
    {
        $id = trim((string) ($request->input('notificationId') ?? $request->input('id') ?? ''));
        if ($id === '') {
            return redirect()->back()->with('error', 'notificationId is required');
        }

        $notification = DatabaseNotification::query()
            ->where('notifiable_type', self::NOTIFIABLE_TYPE)
            ->where('notifiable_id', self::ADMIN_NOTIFICATION_USER_ID)
            ->find($id);

        if ($notification) {
            $notification->markAsRead();
        }

        return redirect()->back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        DatabaseNotification::query()
            ->where('notifiable_type', self::NOTIFIABLE_TYPE)
            ->where('notifiable_id', self::ADMIN_NOTIFICATION_USER_ID)
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'updated_at' => now()]);

        return redirect()->back();
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $unread = (int) DatabaseNotification::query()
            ->where('notifiable_type', self::NOTIFIABLE_TYPE)
            ->where('notifiable_id', self::ADMIN_NOTIFICATION_USER_ID)
            ->whereNull('read_at')
            ->count();

        return response()->json(['unread' => $unread]);
    }

    private function buildAdminLink(array $data): ?string
    {
        $type = strtolower(trim((string) ($data['type'] ?? '')));
        $target = is_array($data['target'] ?? null) ? $data['target'] : [];
        $navigation = is_array($data['navigation'] ?? null) ? $data['navigation'] : [];
        $params = is_array($navigation['params'] ?? null) ? $navigation['params'] : [];
        $routeName = (string) ($navigation['routeName'] ?? '');

        if ($type === 'chat.support' || $type === 'chat.message') {
            $conversationId = (int) ($target['conversationId'] ?? $params['conversationId'] ?? 0);
            if ($conversationId > 0) {
                return '/support-chat?conversationId=' . $conversationId;
            }
        }

        if ($type === 'payment.created') {
            return '/users/enroll-course';
        }

        if ($routeName === 'PostDetail') {
            $postId = (int) ($params['postId'] ?? $target['postId'] ?? $target['targetId'] ?? 0);
            if ($postId > 0) {
                return '/discussions/' . $postId;
            }
        }

        if (isset($target['postId'])) {
            $postId = (int) $target['postId'];
            if ($postId > 0) {
                return '/discussions/' . $postId;
            }
        }

        $lessonId = (int) ($target['lessonId'] ?? $params['lessonId'] ?? $params['id'] ?? $target['id'] ?? $target['lesson_id'] ?? 0);
        if ($lessonId > 0 && Schema::hasTable('lessons')) {
            $lesson = DB::table('lessons')->where('id', $lessonId)->first(['id', 'category_id', 'isVideo']);
            if ($lesson) {
                $categoryId = (int) ($lesson->category_id ?? 0);
                if ($categoryId > 0 && Schema::hasTable('lessons_categories')) {
                    $cat = DB::table('lessons_categories')->where('id', $categoryId)->first(['id', 'course_id']);
                    $courseId = $cat ? (int) ($cat->course_id ?? 0) : 0;
                    if ($courseId > 0) {
                        if ((int) ($lesson->isVideo ?? 0) === 1) {
                            return "/courses/{$courseId}/categories/{$categoryId}/lessons/{$lessonId}/video-detail";
                        }
                        return "/courses/{$courseId}/categories/{$categoryId}/lessons/{$lessonId}/html";
                    }
                }
            }
        }

        return null;
    }
}
