<?php

namespace App\Listeners;

use App\Events\CommentCreated;
use App\Models\Learner;
use App\Notifications\LessonCommentReplyNotification;
use App\Notifications\PostCommentNotification;
use App\Notifications\PostCommentReplyNotification;
use App\Services\NotificationDispatchService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SendCommentCreatedNotification
{
    private const ADMIN_USER_ID = 10000;

    private NotificationDispatchService $dispatch;

    public function __construct(NotificationDispatchService $dispatch)
    {
        $this->dispatch = $dispatch;
    }

    public function handle(CommentCreated $event): void
    {
        if ($event->targetType === 'post' && $event->post) {
            $this->handlePostComment($event);
        } elseif ($event->targetType === 'lesson' && $event->lesson) {
            $this->handleLessonComment($event);
        }
    }

    private function handlePostComment(CommentCreated $event): void
    {

        $comment = $event->comment;
        $post = $event->post;
        $writerId = $comment->writer_id;

        $ownerUserId = $post->user_id ?? $post->learner_id ?? null;

        $writer = DB::table('learners')
            ->where('user_id', $writerId)
            ->select('learner_name', 'learner_image')
            ->first();

        $writerName = $writer?->learner_name ?? ((string) $writerId === '10000' ? 'Admin' : 'Unknown');
        $writerImage = $this->formatImageUrl($writer?->learner_image ?? '', 'users');
        $postImage = $this->formatPostImage($post->image ?? '');
        $major = (string) ($post->major ?? 'english');

        if ($event->parentComment) {
            $parentWriterId = $event->parentComment->writer_id ?? null;
            if (!$parentWriterId || (string) $parentWriterId === (string) $writerId) {
                return;
            }

            if (trim((string) $parentWriterId) === (string) self::ADMIN_USER_ID) {
                $this->dispatch->notifyAdminDatabase([
                    'type' => 'comment.reply',
                    'actor' => [
                        'userId' => (string) $writerId,
                        'name' => (string) $writerName,
                        'image' => (string) $writerImage,
                    ],
                    'target' => [
                        'postId' => (int) ($post->post_id ?? 0),
                        'commentId' => (int) ($comment->time ?? 0),
                        'parentId' => (int) ($comment->parent ?? 0),
                    ],
                    'navigation' => [
                        'routeName' => 'PostDetail',
                        'params' => [
                            'postId' => (string) ($post->post_id ?? ''),
                            'commentId' => (string) ($comment->time ?? ''),
                        ],
                    ],
                ], 'App\\Notifications\\PostCommentReplyNotification');

                $this->dispatch->pushToAdminTopicByMajor(
                    $major,
                    'New Reply',
                    $writerName . ' replied to your comment on a post.',
                    [
                        'type' => 'comment.reply',
                        'postId' => (string) ($post->post_id ?? ''),
                        'commentId' => (string) ($comment->time ?? ''),
                        'major' => strtolower(trim($major)),
                    ],
                    $writerImage !== '' ? $writerImage : null
                );
            } else {
                $parentOwner = Learner::where('user_id', $parentWriterId)->first();
                if ($parentOwner) {
                    $parentOwner->notify(new PostCommentReplyNotification(
                        writerId: (string) $writerId,
                        writerName: $writerName,
                        writerImage: $writerImage,
                        postId: (int) $post->post_id,
                        postBody: $post->body ?? '',
                        postImage: $postImage,
                        commentId: $comment->time,
                        major: $major,
                        parentId: $comment->parent
                    ));
                }
            }

            if ($ownerUserId && trim((string) $ownerUserId) === (string) self::ADMIN_USER_ID && trim((string) $parentWriterId) !== (string) self::ADMIN_USER_ID) {
                $this->dispatch->notifyAdminDatabase([
                    'type' => 'comment.reply',
                    'actor' => [
                        'userId' => (string) $writerId,
                        'name' => (string) $writerName,
                        'image' => (string) $writerImage,
                    ],
                    'target' => [
                        'postId' => (int) ($post->post_id ?? 0),
                        'commentId' => (int) ($comment->time ?? 0),
                        'parentId' => (int) ($comment->parent ?? 0),
                    ],
                    'navigation' => [
                        'routeName' => 'PostDetail',
                        'params' => [
                            'postId' => (string) ($post->post_id ?? ''),
                            'commentId' => (string) ($comment->time ?? ''),
                        ],
                    ],
                ], 'App\\Notifications\\PostCommentReplyNotification');

                $this->dispatch->pushToAdminTopicByMajor(
                    $major,
                    'New Reply',
                    $writerName . ' replied to a comment on your post.',
                    [
                        'type' => 'comment.reply',
                        'postId' => (string) ($post->post_id ?? ''),
                        'commentId' => (string) ($comment->time ?? ''),
                        'major' => strtolower(trim($major)),
                    ],
                    $writerImage !== '' ? $writerImage : null
                );
            }

            return;
        }

        if (!$ownerUserId || (string) $ownerUserId === (string) $writerId) {
            return;
        }

        if (trim((string) $ownerUserId) === (string) self::ADMIN_USER_ID) {
            $this->dispatch->notifyAdminDatabase([
                'type' => 'comment.created',
                'actor' => [
                    'userId' => (string) $writerId,
                    'name' => (string) $writerName,
                    'image' => (string) $writerImage,
                ],
                'target' => [
                    'postId' => (int) ($post->post_id ?? 0),
                    'commentId' => (int) ($comment->time ?? 0),
                    'parentId' => 0,
                ],
                'navigation' => [
                    'routeName' => 'PostDetail',
                    'params' => [
                        'postId' => (string) ($post->post_id ?? ''),
                        'commentId' => (string) ($comment->time ?? ''),
                    ],
                ],
            ], 'App\\Notifications\\PostCommentNotification');

            $this->dispatch->pushToAdminTopicByMajor(
                $major,
                'New Comment',
                $writerName . ' commented on your post.',
                [
                    'type' => 'comment.created',
                    'postId' => (string) ($post->post_id ?? ''),
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

        $owner->notify(new PostCommentNotification(
            writerId: (string) $writerId,
            writerName: $writerName,
            writerImage: $writerImage,
            postId: (int) $post->post_id,
            postBody: $post->body ?? '',
            postImage: $postImage,
            commentId: $comment->time,
            major: $major,
            parentId: $comment->parent
        ));
    }
    
    private function handleLessonComment(CommentCreated $event): void
    {
        $comment = $event->comment;
        $lesson = $event->lesson;
        $writerId = $comment->writer_id;
        if (!$lesson) {
            return;
        }

        $courseId = 0;
        $categoryId = (int) ($lesson->category_id ?? 0);
        if ($categoryId > 0) {
            $cat = DB::table('lessons_categories')->where('id', $categoryId)->first(['course_id']);
            $courseId = $cat ? (int) ($cat->course_id ?? 0) : 0;
        }

        $writer = DB::table('learners')
            ->where('user_id', $writerId)
            ->select('learner_name', 'learner_image')
            ->first();

        $writerName = $writer?->learner_name ?? ((string) $writerId === '10000' ? 'Admin' : 'Unknown');
        $writerImage = $this->formatImageUrl($writer?->learner_image ?? '', 'users');
        $major = (string) ($lesson->major ?? 'english');

        if (!$event->parentComment) {
            $this->dispatch->notifyAdminDatabase([
                'type' => 'lesson.comment',
                'actor' => [
                    'userId' => (string) $writerId,
                    'name' => (string) $writerName,
                    'image' => (string) $writerImage,
                ],
                'target' => [
                    'lessonId' => (int) ($lesson->id ?? 0),
                    'courseId' => (int) $courseId,
                    'commentId' => (int) ($comment->time ?? 0),
                ],
                'navigation' => [
                    'routeName' => 'LessonDetail',
                    'params' => [
                        'id' => (string) ($lesson->id ?? ''),
                        'courseId' => (string) $courseId,
                        'commentId' => (string) ($comment->time ?? ''),
                    ],
                ],
            ], 'App\\Notifications\\LessonCommentNotification');

            $this->dispatch->pushToAdminTopicByMajor(
                $major,
                'New Lesson Comment',
                $writerName . ' commented on a lesson.',
                [
                    'type' => 'lesson.comment',
                    'lessonId' => (string) ($lesson->id ?? ''),
                    'courseId' => (string) $courseId,
                    'commentId' => (string) ($comment->time ?? ''),
                    'major' => strtolower(trim($major)),
                ],
                $writerImage !== '' ? $writerImage : null
            );
            return;
        }

        $parentWriterId = $event->parentComment->writer_id;

        if (!$parentWriterId || (string) $parentWriterId === (string) $writerId) {
            return;
        }

        if (trim((string) $parentWriterId) === (string) self::ADMIN_USER_ID) {
            $this->dispatch->notifyAdminDatabase([
                'type' => 'comment.reply',
                'actor' => [
                    'userId' => (string) $writerId,
                    'name' => (string) $writerName,
                    'image' => (string) $writerImage,
                ],
                'target' => [
                    'lessonId' => (int) ($lesson->id ?? 0),
                    'courseId' => (int) $courseId,
                    'commentId' => (int) ($comment->time ?? 0),
                    'parentId' => (int) ($comment->parent ?? 0),
                ],
                'navigation' => [
                    'routeName' => 'LessonDetail',
                    'params' => [
                        'id' => (string) ($lesson->id ?? ''),
                        'courseId' => (string) $courseId,
                        'commentId' => (string) ($comment->time ?? ''),
                    ],
                ],
            ], 'App\\Notifications\\LessonCommentReplyNotification');

            $this->dispatch->pushToAdminTopicByMajor(
                $major,
                'New Reply',
                $writerName . ' replied to your lesson comment.',
                [
                    'type' => 'comment.reply',
                    'lessonId' => (string) ($lesson->id ?? ''),
                    'courseId' => (string) $courseId,
                    'commentId' => (string) ($comment->time ?? ''),
                    'major' => strtolower(trim($major)),
                ],
                $writerImage !== '' ? $writerImage : null
            );
            return;
        }

        $parentOwner = Learner::where('user_id', $parentWriterId)->first();
        if (!$parentOwner) {
            return;
        }

        $parentOwner->notify(new LessonCommentReplyNotification(
            writerId: (string) $writerId,
            writerName: $writerName,
            writerImage: $writerImage,
            lessonId: (int) $lesson->id,
            lessonTitle: $lesson->title ?? '',
            commentId: (int) $comment->time,
            major: $major,
            parentId: (int) $comment->parent
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
