<?php

namespace App\Notifications\Channels;

use App\Jobs\SendFcmToTokens;
use App\Models\UserData;
use Illuminate\Notifications\Notification;

class FcmChannel
{
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
        $tokens = [];
        foreach ($userDatas as $userData) {
            $rowTokens = is_array($userData->token) ? $userData->token : [];
            foreach ($rowTokens as $platform => $token) {
                $token = trim((string) $token);
                if ($token !== '') {
                    $tokens[] = $token;
                }
            }
        }

        $tokens = array_values(array_unique($tokens));
        if ($tokens === []) {
            return;
        }

        dispatch(new SendFcmToTokens(
            tokens: $tokens,
            title: (string) ($fcmData['title'] ?? ''),
            body: (string) ($fcmData['body'] ?? ''),
            data: is_array($fcmData['data'] ?? null) ? $fcmData['data'] : [],
            image: isset($fcmData['image']) && is_string($fcmData['image']) ? $fcmData['image'] : null
        ));
    }
}
