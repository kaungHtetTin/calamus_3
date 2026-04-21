<?php

namespace App\Listeners;

use App\Events\PostLiked;
use App\Models\Learner;
use App\Notifications\PostLikeNotification;
use App\Services\NotificationDispatchService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SendPostLikedNotification
{
    private const ADMIN_USER_ID = 10000;

    private NotificationDispatchService $dispatch;

    public function __construct(NotificationDispatchService $dispatch)
    {
        $this->dispatch = $dispatch;
    }

    public function handle(PostLiked $event): void
    {
        $post = $event->post;
        $liker = $event->liker;
        $ownerUserId = $post->user_id ?? $post->learner_id ?? null;

        if (!$ownerUserId || (string) $ownerUserId === (string) $liker->user_id) {
            return;
        }

        if ((int) $ownerUserId === self::ADMIN_USER_ID) {
            $major = (string) ($post->major ?? 'english');
            $writerImage = $this->formatImageUrl($liker->learner_image ?? '', 'users');
            $this->dispatch->notifyAdminDatabase([
                'type' => 'post.like',
                'actor' => [
                    'userId' => (int) $liker->user_id,
                    'name' => (string) ($liker->learner_name ?? 'Unknown'),
                    'image' => (string) $writerImage,
                ],
                'target' => [
                    'postId' => (int) ($post->post_id ?? 0),
                ],
                'navigation' => [
                    'routeName' => 'PostDetail',
                    'params' => [
                        'postId' => (string) ($post->post_id ?? ''),
                    ],
                ],
            ], 'App\\Notifications\\PostLikeNotification');

            $this->dispatch->pushToAdminTopicByMajor(
                $major,
                'Post Liked',
                ($liker->learner_name ?? 'Someone') . ' liked your post.',
                [
                    'type' => 'post.like',
                    'postId' => (string) ($post->post_id ?? ''),
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

        $postImage = $this->formatPostImage($post->image ?? '');
        $writerImage = $this->formatImageUrl($liker->learner_image ?? '', 'users');

        $owner->notify(new PostLikeNotification(
            writerId: (int) $liker->user_id,
            writerName: $liker->learner_name ?? 'Unknown',
            writerImage: $writerImage,
            postId: (int) $post->post_id,
            postBody: $post->body ?? '',
            postImage: $postImage,
            major: $post->major ?? 'english'
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
