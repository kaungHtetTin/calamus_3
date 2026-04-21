<?php

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PostLikeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $writerId,
        public string $writerName,
        public string $writerImage,
        public int $postId,
        public string $postBody,
        public string $postImage,
        public string $major
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'major' => $this->major,
            'title' => 'Post Liked',
            'body' => "{$this->writerName} liked your post: " . \Illuminate\Support\Str::limit($this->postBody, 50),
            'image' => $this->writerImage,
            'data' => [
                'type' => 'post.like',
                'navigation' => [
                    'routeName' => 'PostDetail',
                    'params' => [
                        'postId' => (string) $this->postId,
                    ],
                ],
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'post.like',
            'actor' => [
                'id' => $this->writerId,
                'name' => $this->writerName,
                'image' => $this->writerImage,
            ],
            'target' => [
                'type' => 'post',
                'id' => (string) $this->postId,
                'title' => $this->postBody,
                'image' => $this->postImage,
            ],
            'navigation' => [
                'routeName' => 'PostDetail',
                'params' => [
                    'postId' => (string) $this->postId,
                ],
            ],
            'metadata' => [],
        ];
    }
}
