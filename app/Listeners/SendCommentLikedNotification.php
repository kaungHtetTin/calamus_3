<?php

namespace App\Listeners;

use App\Events\CommentLiked;
use App\Models\Learner;
use App\Models\Post;
use App\Models\Lesson;
use App\Notifications\CommentLikeNotification;
use App\Services\NotificationDispatchService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SendCommentLikedNotification
{
    private const ADMIN_USER_ID = 10000;

    private NotificationDispatchService $dispatch;

    public function __construct(NotificationDispatchService $dispatch)
    {
        $this->dispatch = $dispatch;
    }

    public function handle(CommentLiked $event): void
    {
        $comment = $event->comment;
        $liker = $event->liker;
        $ownerUserId = $comment->writer_id;

        if ((string) $ownerUserId === (string) $liker->user_id) {
            return;
        }

        $targetType = $comment->target_type ?: 'post';
        $targetId = (string) ($comment->target_id ?: $comment->post_id);
        $targetTitle = '';
        $targetImage = '';

        $major = 'english';
        if ($targetType === 'post') {
            $post = Post::where('post_id', $targetId)->first();
            $targetTitle = $post?->body ?? '';
            $targetImage = $this->formatPostImage($post?->image ?? '');
            $major = $post?->major ?? 'english';
        } else {
            $lesson = Lesson::find($targetId);
            $targetTitle = $lesson?->title ?? '';
            $targetImage = $this->formatPostImage($lesson?->image ?? '');
            $major = $lesson?->major ?? 'english';
        }

        $writerImage = $this->formatImageUrl($liker->learner_image ?? '', 'users');

        if (trim((string) $ownerUserId) === (string) self::ADMIN_USER_ID) {
            $courseId = 0;
            if ($targetType === 'lesson' && isset($lesson) && $lesson) {
                $categoryId = (int) ($lesson->category_id ?? 0);
                if ($categoryId > 0) {
                    $cat = DB::table('lessons_categories')->where('id', $categoryId)->first(['course_id']);
                    $courseId = $cat ? (int) ($cat->course_id ?? 0) : 0;
                }
            }

            $this->dispatch->notifyAdminDatabase([
                'type' => 'comment.like',
                'actor' => [
                    'userId' => (string) ($liker->user_id ?? ''),
                    'name' => (string) ($liker->learner_name ?? 'Unknown'),
                    'image' => (string) $writerImage,
                ],
                'target' => [
                    'targetType' => (string) $targetType,
                    'targetId' => (string) $targetId,
                    'courseId' => (int) $courseId,
                    'commentId' => (int) ($comment->time ?? 0),
                ],
                'navigation' => [
                    'routeName' => $targetType === 'post' ? 'PostDetail' : 'LessonDetail',
                    'params' => $targetType === 'post'
                        ? ['postId' => (string) $targetId, 'commentId' => (string) ($comment->time ?? '')]
                        : ['id' => (string) $targetId, 'courseId' => (string) $courseId, 'commentId' => (string) ($comment->time ?? '')],
                ],
            ], 'App\\Notifications\\CommentLikeNotification');

            $this->dispatch->pushToAdminTopicByMajor(
                $major,
                'Comment Liked',
                ($liker->learner_name ?? 'Someone') . ' liked your comment.',
                [
                    'type' => 'comment.like',
                    'targetType' => (string) $targetType,
                    'targetId' => (string) $targetId,
                    'courseId' => (string) $courseId,
                    'commentId' => (string) ($comment->time ?? ''),
                    'major' => strtolower(trim($major)),
                ],
                $writerImage !== '' ? $writerImage : null
            );
            return;
        }

        $owner = Learner::where('user_id', $ownerUserId)->first();
        if (!$owner) {
            return;
        }

        $owner->notify(new CommentLikeNotification(
            writerId: (string) ($liker->user_id ?? ''),
            writerName: $liker->learner_name ?? 'Unknown',
            writerImage: $writerImage,
            targetType: $targetType,
            targetId: $targetId,
            targetTitle: $targetTitle,
            targetImage: $targetImage,
            commentId: $comment->time,
            major: $major
        ));
    }

    private function formatImageUrl(string $path, string $prefix): string
    {
        if (empty($path) || Str::startsWith($path, 'http')) {
            return $path;
        }
        return rtrim(config('app.url'), '/') . '/' . \Illuminate\Support\Facades\Storage::disk('uploads')->url($prefix . '/' . ltrim($path, '/'));
    }

    private function formatPostImage(?string $path): string
    {
        if (empty($path)) {
            return '';
        }
        if (Str::startsWith($path, 'http')) {
            return $path;
        }
        if (Str::startsWith($path, 'uploads/')) {
            return rtrim(config('app.url'), '/') . '/' . \Illuminate\Support\Facades\Storage::disk('uploads')->url(Str::after($path, 'uploads/'));
        }
        return rtrim(config('app.url'), '/') . '/' . \Illuminate\Support\Facades\Storage::disk('uploads')->url($path);
    }
}
