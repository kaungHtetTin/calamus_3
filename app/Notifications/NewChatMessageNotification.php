<?php

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewChatMessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $senderId,
        public string $senderName,
        public string $senderImage,
        public string $messageText,
        public int $conversationId
    ) {}

    public function via(object $notifiable): array
    {
        return [FcmChannel::class]; // Only FCM for real-time chat
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'title' => $this->senderName,
            'body' => $this->messageText ?: 'Sent a file',
            'image' => $this->senderImage,
            'data' => [
                'type' => 'chat.message',
                'navigation' => [
                    'routeName' => 'ChatDetail',
                    'params' => [
                        'conversationId' => (string) $this->conversationId,
                        'friendId' => (string) $this->senderId,
                    ],
                ],
            ],
        ];
    }
}
