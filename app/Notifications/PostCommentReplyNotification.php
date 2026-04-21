<?php

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PostCommentReplyNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $writerId,
        public string $writerName,
        public string $writerImage,
        public int $postId,
        public string $postBody,
        public string $postImage,
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
        return [
            'major' => $this->major,
            'title' => 'New Reply',
            'body' => "{$this->writerName} replied to your comment on: " . \Illuminate\Support\Str::limit($this->postBody, 50),
            'image' => $this->writerImage,
            'data' => [
                'type' => 'post.comment_reply',
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
            'type' => 'post.comment_reply',
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
