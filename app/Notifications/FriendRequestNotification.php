<?php

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FriendRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $senderId,
        public string $senderName,
        public string $senderImage,
        public string $type // 'request' or 'accept'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'friend.' . $this->type,
            'actor' => [
                'id' => $this->senderId,
                'name' => $this->senderName,
                'image' => $this->senderImage,
            ],
            'navigation' => [
                'routeName' => $this->type === 'request' ? 'FriendRequests' : 'UserProfile',
                'params' => [
                    'userId' => (string) $this->senderId,
                ],
            ],
        ];
    }

    public function toFcm(object $notifiable): array
    {
        $title = $this->type === 'request' ? 'New Friend Request' : 'Friend Request Accepted';
        $body = $this->type === 'request' 
            ? "{$this->senderName} sent you a friend request." 
            : "{$this->senderName} accepted your friend request.";

        return [
            'title' => $title,
            'body' => $body,
            'image' => $this->senderImage,
            'data' => [
                'type' => 'friend.' . $this->type,
                'navigation' => [
                    'routeName' => $this->type === 'request' ? 'FriendRequests' : 'UserProfile',
                    'params' => [
                        'userId' => (string) $this->senderId,
                    ],
                ],
            ],
        ];
    }
}
