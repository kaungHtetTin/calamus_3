<?php

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LessonCommentReplyNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $writerId,
        public string $writerName,
        public string $writerImage,
        public int $lessonId,
        public string $lessonTitle,
        public int $commentId,
        public string $major,
        public int $parentId
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toFcm(object $notifiable): array
    {
        $courseId = $this->resolveCourseId();
        return [
            'major' => $this->major,
            'title' => 'New Reply',
            'body' => "{$this->writerName} replied to your comment on: " . \Illuminate\Support\Str::limit($this->lessonTitle, 50),
            'image' => $this->writerImage,
            'data' => [
                'type' => 'lesson.comment_reply',
                'navigation' => [
                    'routeName' => 'LessonDetail',
                    'params' => [
                        'lessonId' => (string) $this->lessonId,
                        'courseId' => (string) $courseId,
                        'commentId' => $this->commentId,
                    ],
                ],
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        $courseId = $this->resolveCourseId();
        return [
            'type' => 'lesson.comment_reply',
            'actor' => [
                'id' => $this->writerId,
                'name' => $this->writerName,
                'image' => $this->writerImage,
            ],
            'target' => [
                'type' => 'lesson',
                'id' => (string) $this->lessonId,
                'courseId' => (string) $courseId,
                'title' => $this->lessonTitle,
                'image' => '',
            ],
            'navigation' => [
                'routeName' => 'LessonDetail',
                'params' => [
                    'lessonId' => (string) $this->lessonId,
                    'courseId' => (string) $courseId,
                    'commentId' => $this->commentId,
                ],
            ],
            'metadata' => [
                'commentId' => $this->commentId,
                'parentId' => $this->parentId,
            ],
        ];
    }

    private function resolveCourseId(): int
    {
        if (!Schema::hasTable('lessons') || !Schema::hasTable('lessons_categories')) {
            return 0;
        }
        $lesson = DB::table('lessons')->where('id', $this->lessonId)->first(['id', 'category_id']);
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
