<?php

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CommentLikeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $writerId,
        public string $writerName,
        public string $writerImage,
        public string $targetType,
        public string $targetId,
        public string $targetTitle,
        public string $targetImage,
        public int $commentId,
        public string $major
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toFcm(object $notifiable): array
    {
        $params = ['postId' => $this->targetId, 'commentId' => $this->commentId];
        $courseId = null;
        if ($this->targetType === 'lesson') {
            $courseId = $this->resolveCourseId((int) $this->targetId);
            $params = ['id' => $this->targetId, 'courseId' => (string) $courseId, 'commentId' => $this->commentId];
        }

        return [
            'major' => $this->major,
            'title' => 'Comment Liked',
            'body' => "{$this->writerName} liked your comment on " . ($this->targetType === 'post' ? 'a post' : 'a lesson'),
            'image' => $this->writerImage,
            'data' => [
                'type' => 'comment.like',
                'navigation' => [
                    'routeName' => $this->targetType === 'post' ? 'PostDetail' : 'LessonDetail',
                    'params' => $params,
                ],
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        $params = ['postId' => $this->targetId, 'commentId' => $this->commentId];
        $courseId = null;
        if ($this->targetType === 'lesson') {
            $courseId = $this->resolveCourseId((int) $this->targetId);
            $params = ['id' => $this->targetId, 'courseId' => (string) $courseId, 'commentId' => $this->commentId];
        }

        return [
            'type' => 'comment.like',
            'actor' => [
                'id' => $this->writerId,
                'name' => $this->writerName,
                'image' => $this->writerImage,
            ],
            'target' => [
                'type' => $this->targetType,
                'id' => $this->targetId,
                'courseId' => $this->targetType === 'lesson' ? (string) $courseId : null,
                'title' => $this->targetTitle,
                'image' => $this->targetImage,
            ],
            'navigation' => [
                'routeName' => $this->targetType === 'post' ? 'PostDetail' : 'LessonDetail',
                'params' => $params,
            ],
            'metadata' => [
                'commentId' => $this->commentId,
            ],
        ];
    }

    private function resolveCourseId(int $lessonId): int
    {
        if ($lessonId <= 0 || !Schema::hasTable('lessons') || !Schema::hasTable('lessons_categories')) {
            return 0;
        }
        $lesson = DB::table('lessons')->where('id', $lessonId)->first(['id', 'category_id']);
        if (!$lesson) {
            return 0;
        }
        $categoryId = (int) ($lesson->category_id ?? 0);
        if ($categoryId <= 0) {
            return 0;
        }
        $cat = DB::table('lessons_categories')->where('id', $categoryId)->first(['id', 'course_id']);
        return $cat ? (int) ($cat->course_id ?? 0) : 0;
    }
}
