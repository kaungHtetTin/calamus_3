<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NotificationController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            $user = auth('sanctum')->user();
            if (!$user) {
                return $this->errorResponse('Not authenticated', 401);
            }

            $limit = min(100, max(1, (int) $request->query('limit', 20)));
            $page = max(1, (int) $request->query('page', 1));

            $notifications = $user->notifications()
                ->orderBy('created_at', 'desc')
                ->paginate($limit, ['*'], 'page', $page);

            $courseIdsByLessonId = $this->prefetchCourseIdsByLessonId($notifications->items());
            $items = $notifications->map(fn (DatabaseNotification $n) => $this->formatNotification($n, $courseIdsByLessonId));

            $unreadCount = $user->unreadNotifications()->count();

            return $this->successResponse(
                $items,
                200,
                array_merge(
                    $this->paginate($notifications->total(), $page, $limit),
                    ['unreadCount' => $unreadCount]
                )
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Server error: ' . $e->getMessage(), 500);
        }
    }

    public function markOneRead(Request $request)
    {
        try {
            $user = auth('sanctum')->user();
            if (!$user) {
                return $this->errorResponse('Not authenticated', 401);
            }

            $id = $request->input('notificationId') ?? $request->input('id');
            if (!$id) {
                return $this->errorResponse('notificationId is required', 400);
            }

            $notification = $user->notifications()->find($id);
            if (!$notification) {
                return $this->errorResponse('Notification not found', 404);
            }

            $notification->markAsRead();

            return $this->successResponse(['success' => true]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to mark notification as read', 500);
        }
    }

    public function markSeen(Request $request)
    {
        try {
            $user = auth('sanctum')->user();
            if (!$user) {
                return $this->errorResponse('Not authenticated', 401);
            }

            $id = $request->input('id') ?? $request->input('notificationId');
            if ($id) {
                $user->notifications()->where('id', $id)->get()->each->markAsRead();
            } else {
                $user->unreadNotifications->each->markAsRead();
            }

            return $this->successResponse(['success' => true]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to mark as seen', 500);
        }
    }

    private function formatNotification(DatabaseNotification $n, array $courseIdsByLessonId = []): array
    {
        $data = $n->data;

        if (is_array($data) && isset($data['navigation']) && is_array($data['navigation'])) {
            $data = $this->ensureLessonCourseId($data, $courseIdsByLessonId);
        }

        return [
            'id' => $n->id,
            'type' => $data['type'] ?? 'unknown',
            'rawType' => (string) ($n->type ?? ''),
            'actor' => $data['actor'] ?? null,
            'target' => $data['target'] ?? null,
            'navigation' => $data['navigation'] ?? null,
            'metadata' => $data['metadata'] ?? [],
            'readAt' => $n->read_at?->toIso8601String(),
            'createdAt' => $n->created_at->toIso8601String(),
        ];
    }

    private function ensureLessonCourseId(array $data, array $courseIdsByLessonId): array
    {
        $routeName = (string) ($data['navigation']['routeName'] ?? '');
        if ($routeName !== 'LessonDetail') {
            return $data;
        }

        $params = is_array($data['navigation']['params'] ?? null) ? $data['navigation']['params'] : [];
        $target = is_array($data['target'] ?? null) ? $data['target'] : [];

        $lessonId = (int) ($params['lessonId'] ?? $params['id'] ?? $target['lessonId'] ?? $target['id'] ?? 0);
        if ($lessonId <= 0) {
            return $data;
        }

        if (!isset($params['courseId']) || (string) $params['courseId'] === '') {
            $courseId = (int) ($courseIdsByLessonId[$lessonId] ?? 0);
            $params['courseId'] = (string) $courseId;
            $data['navigation']['params'] = $params;
        }

        if (isset($target['type']) && (string) $target['type'] === 'lesson') {
            if (!isset($target['courseId']) || (string) $target['courseId'] === '') {
                $target['courseId'] = (string) ($params['courseId'] ?? '0');
                $data['target'] = $target;
            }
        }

        return $data;
    }

    private function prefetchCourseIdsByLessonId(array $notifications): array
    {
        if (!Schema::hasTable('lessons') || !Schema::hasTable('lessons_categories')) {
            return [];
        }

        $lessonIds = [];
        foreach ($notifications as $n) {
            if (!$n instanceof DatabaseNotification) {
                continue;
            }
            $data = $n->data;
            if (!is_array($data)) {
                continue;
            }
            $nav = is_array($data['navigation'] ?? null) ? $data['navigation'] : null;
            if (!$nav || (string) ($nav['routeName'] ?? '') !== 'LessonDetail') {
                continue;
            }
            $params = is_array($nav['params'] ?? null) ? $nav['params'] : [];
            $target = is_array($data['target'] ?? null) ? $data['target'] : [];
            $lessonId = (int) ($params['lessonId'] ?? $params['id'] ?? $target['lessonId'] ?? $target['id'] ?? 0);
            if ($lessonId > 0) {
                $lessonIds[] = $lessonId;
            }
        }

        $lessonIds = array_values(array_unique($lessonIds));
        if ($lessonIds === []) {
            return [];
        }

        return DB::table('lessons as l')
            ->join('lessons_categories as lc', 'lc.id', '=', 'l.category_id')
            ->whereIn('l.id', $lessonIds)
            ->pluck('lc.course_id', 'l.id')
            ->map(fn ($v) => (int) $v)
            ->toArray();
    }
}
