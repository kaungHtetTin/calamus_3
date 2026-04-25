<?php

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PostCommentNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $writerId,
        public string $writerName,
        public string $writerImage,
        public int $postId,
        public string $postBody,
        public string $postImage,
        public int $commentId,
        public string $major,
        public int $parentId = 0
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'major' => $this->major,
            'title' => 'New Comment',
            'body' => "{$this->writerName} commented on your post: " . \Illuminate\Support\Str::limit($this->postBody, 50),
            'data' => [
                'type' => 'post.comment',
                'navigation' => [
                    'routeName' => 'PostDetail',
                    'params' => [
                        'postId' => (string) $this->postId,
                        'commentId' => $this->commentId,
                    ],
                ],
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'post.comment',
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
                    'commentId' => $this->commentId,
                ],
            ],
            'metadata' => [
                'commentId' => $this->commentId,
                'parentId' => $this->parentId,
            ],
        ];
    }
}
