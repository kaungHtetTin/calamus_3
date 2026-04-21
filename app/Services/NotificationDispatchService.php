<?php

namespace App\Services;

use App\Models\Language;
use App\Models\UserData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class NotificationDispatchService
{
    private const ADMIN_USER_ID = 10000;

    private FcmService $fcm;

    public function __construct(FcmService $fcm)
    {
        $this->fcm = $fcm;
    }

    public function pushToUserTokens(string $userId, string $title, string $body, array $data = [], ?string $image = null): array
    {
        $userId = trim($userId);
        if ($userId === '' || $userId === '0' || !ctype_digit($userId)) {
            return ['sent' => 0, 'failed' => 0];
        }

        $userDatas = UserData::query()
            ->where('user_id', $userId)
            ->get(['token']);

        $sent = 0;
        $failed = 0;

        foreach ($userDatas as $ud) {
            $tokens = is_array($ud->token) ? $ud->token : [];
            foreach ($tokens as $platform => $token) {
                $token = trim((string) $token);
                if ($token === '') {
                    continue;
                }

                $ok = $this->fcm->sendPush(
                    token: $token,
                    title: $title,
                    body: $body,
                    data: $data,
                    image: $image
                );

                if ($ok) {
                    $sent++;
                } else {
                    $failed++;
                }
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    public function pushToUserTopicByMajor(?string $major, string $title, string $body, array $data = [], ?string $image = null): bool
    {
        $topic = $this->resolveTopicByMajor($major, 'firebase_topic_user');
        if ($topic === '') {
            return false;
        }
        return $this->fcm->sendTopic($topic, $title, $body, $data, $image);
    }

    public function pushToAdminTopicByMajor(?string $major, string $title, string $body, array $data = [], ?string $image = null): bool
    {
        $topic = $this->resolveTopicByMajor($major, 'firebase_topic_admin');
        if ($topic === '') {
            return false;
        }
        return $this->fcm->sendTopic($topic, $title, $body, $data, $image);
    }

    public function notifyAdminDatabase(array $data, string $typeClass = 'App\\Notifications\\AdminEvent'): ?string
    {
        return $this->insertDatabaseNotification((string) self::ADMIN_USER_ID, $data, $typeClass);
    }

    public function notifyUserDatabase(string $userId, array $data, string $typeClass = 'App\\Notifications\\UserEvent'): ?string
    {
        return $this->insertDatabaseNotification($userId, $data, $typeClass);
    }

    private function resolveTopicByMajor(?string $major, string $column): string
    {
        $major = strtolower(trim((string) $major));
        if ($major === '' || !Schema::hasTable('languages') || !Schema::hasColumn('languages', $column)) {
            return '';
        }

        $language = Language::query()
            ->whereRaw('LOWER(code) = ?', [$major])
            ->orWhereRaw('LOWER(name) = ?', [$major])
            ->orWhereRaw('LOWER(module_code) = ?', [$major])
            ->first([$column]);

        $topic = $language ? trim((string) ($language->{$column} ?? '')) : '';
        return $topic;
    }

    private function insertDatabaseNotification(string $userId, array $data, string $typeClass): ?string
    {
        $userId = trim($userId);
        if ($userId === '' || $userId === '0' || !ctype_digit($userId)) {
            return null;
        }
        if (!Schema::hasTable('notifications')) {
            return null;
        }

        $id = (string) Str::uuid();
        $now = now();

        DB::table('notifications')->insert([
            'id' => $id,
            'type' => $typeClass,
            'notifiable_type' => 'App\\Models\\Learner',
            'notifiable_id' => $userId,
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'read_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $id;
    }
}
