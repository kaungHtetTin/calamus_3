<?php

namespace App\Notifications\Channels;

use App\Models\UserData;
use App\Services\FcmService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class FcmChannel
{
    public function __construct(
        protected FcmService $fcmService
    ) {}

    public function send($notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toFcm')) {
            return;
        }

        $fcmData = $notification->toFcm($notifiable);
        if (!$fcmData) {
            return;
        }

        $userId = $notifiable->user_id;
        $userDatas = UserData::where('user_id', $userId)->get();
        foreach ($userDatas as $userData) {
            if (!empty($userData->token)) {
                $this->sendToTokens($userData->token, $fcmData);
            }
        }
    }

    protected function sendToTokens(array $tokens, array $fcmData): void
    {
        foreach ($tokens as $platform => $token) {
            if (empty($token)) continue;

            $this->fcmService->sendPush(
                token: $token,
                title: $fcmData['title'],
                body: $fcmData['body'],
                data: $fcmData['data'] ?? [],
                image: $fcmData['image'] ?? null
            );
        }
    }
}
