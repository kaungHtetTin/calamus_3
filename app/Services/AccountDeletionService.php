<?php

namespace App\Services;

use App\Models\Learner;
use App\Models\Post;
use App\Models\VipUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountDeletionService
{
    /**
     * All learner-related identifiers (user_id and legacy phone-based keys).
     *
     * @return list<int|string>
     */
    private static function legacyUserKeys(Learner $user): array
    {
        $keys = [
            $user->user_id,
            (string) $user->user_id,
        ];
        if (is_numeric($user->user_id)) {
            $keys[] = (int) $user->user_id;
        }
        if ($user->learner_phone !== null && $user->learner_phone !== '') {
            $keys[] = $user->learner_phone;
            $keys[] = (string) $user->learner_phone;
            if (is_numeric($user->learner_phone)) {
                $keys[] = (int) $user->learner_phone;
            }
        }

        $out = [];
        foreach ($keys as $k) {
            if ($k === null || $k === '') {
                continue;
            }
            $out[] = $k;
        }

        return array_values(array_unique($out, SORT_REGULAR));
    }

    /**
     * Permanently remove learner data (VIP purchases are archived first).
     */
    public static function deleteAccount(Learner $user): void
    {
        DB::transaction(function () use ($user) {
            $keys = self::legacyUserKeys($user);
            $uid = $user->user_id;

            self::backupVipPurchases($user);

            $userPostIds = Post::where('user_id', $uid)->pluck('post_id')->map(fn ($id) => (int) $id)->all();

            if (!empty($userPostIds)) {
                DB::table('mylikes')->whereIn('content_id', $userPostIds)->delete();
            }

            $commentIds = DB::table('comment')
                ->where(function ($q) use ($userPostIds, $uid) {
                    if (!empty($userPostIds)) {
                        $q->whereIn('post_id', $userPostIds);
                    }
                    $q->orWhere('writer_id', $uid);
                })
                ->pluck('id');

            if ($commentIds->isNotEmpty()) {
                DB::table('comment_likes')->whereIn('comment_id', $commentIds)->delete();
            }

            if (!empty($keys)) {
                DB::table('comment_likes')->whereIn('user_id', $keys)->delete();
            }

            DB::table('comment')
                ->where(function ($q) use ($userPostIds, $uid) {
                    if (!empty($userPostIds)) {
                        $q->whereIn('post_id', $userPostIds);
                    }
                    $q->orWhere('writer_id', $uid);
                })
                ->delete();

            if (!empty($userPostIds)) {
                NotificationCleanupService::forPosts($userPostIds);
            }

            Post::where('user_id', $uid)->delete();

            self::stripUserFromMylikesJson($uid, $keys);

            if (!empty($keys)) {
                DB::table('notifications')
                    ->where('notifiable_type', Learner::class)
                    ->whereIn('notifiable_id', $keys)
                    ->delete();
            }

            if (Schema::hasTable('blocks') && !empty($keys)) {
                DB::table('blocks')->where(function ($q) use ($keys) {
                    $q->whereIn('user_id', $keys)->orWhereIn('blocked_user_id', $keys);
                })->delete();
            }

            if (Schema::hasTable('certificates') && !empty($keys)) {
                DB::table('certificates')->whereIn('user_id', $keys)->delete();
            }

            if (Schema::hasTable('ee_error_speech') && !empty($keys)) {
                if (Schema::hasColumn('ee_error_speech', 'user_id')) {
                    DB::table('ee_error_speech')->whereIn('user_id', $keys)->delete();
                }
                if (Schema::hasColumn('ee_error_speech', 'phone')) {
                    DB::table('ee_error_speech')->whereIn('phone', $keys)->delete();
                }
            }

            $convIds = [];
            if (Schema::hasTable('conversations') && !empty($keys)) {
                $convIds = DB::table('conversations')
                    ->where(function ($q) use ($keys) {
                        $q->whereIn('user1_id', $keys)->orWhereIn('user2_id', $keys);
                    })
                    ->pluck('id')
                    ->all();
            }

            if (!empty($convIds) && Schema::hasTable('messages')) {
                DB::table('messages')->whereIn('conversation_id', $convIds)->delete();
            }

            if (!empty($convIds) && Schema::hasTable('conversations')) {
                DB::table('conversations')->whereIn('id', $convIds)->delete();
            }

            if (Schema::hasTable('friendships') && !empty($keys)) {
                DB::table('friendships')->where(function ($q) use ($keys) {
                    $q->whereIn('user_id', $keys)->orWhereIn('friend_id', $keys);
                })->delete();
            }

            if (Schema::hasTable('friend_request_lists') && !empty($keys)) {
                DB::table('friend_request_lists')->where(function ($q) use ($keys) {
                    $q->whereIn('sender_id', $keys)->orWhereIn('receiver_id', $keys);
                })->delete();
            }

            if (Schema::hasTable('hidden_posts') && !empty($keys)) {
                DB::table('hidden_posts')->whereIn('user_id', $keys)->delete();
            }

            if (Schema::hasTable('library_downloads') && !empty($keys)) {
                DB::table('library_downloads')->whereIn('user_id', $keys)->delete();
            }

            if (Schema::hasTable('likes') && !empty($keys)) {
                DB::table('likes')->whereIn('user_id', $keys)->delete();
            }

            if (Schema::hasTable('studies') && !empty($keys)) {
                DB::table('studies')->where(function ($q) use ($keys) {
                    $q->whereIn('user_id', $keys)->orWhereIn('learner_id', $keys);
                })->delete();
            }

            if (Schema::hasTable('user_card_states') && !empty($keys)) {
                DB::table('user_card_states')->whereIn('user_id', $keys)->delete();
            }

            if (Schema::hasTable('user_data') && !empty($keys)) {
                DB::table('user_data')->whereIn('user_id', $keys)->delete();
            }

            foreach (['user_learning_progress', 'user_learning_progresses'] as $t) {
                if (Schema::hasTable($t) && !empty($keys)) {
                    DB::table($t)->whereIn('user_id', $keys)->delete();
                }
            }

            if (Schema::hasTable('user_word_skips') && !empty($keys)) {
                DB::table('user_word_skips')->whereIn('user_id', $keys)->delete();
            }

            VipUser::where('user_id', $uid)->delete();

            $user->tokens()->delete();

            $user->delete();
        });
    }

    private static function backupVipPurchases(Learner $user): void
    {
        if (!Schema::hasTable('deleted_account_purchases')) {
            return;
        }

        $courseIds = VipUser::where('user_id', $user->user_id)
            ->pluck('course_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($courseIds === []) {
            return;
        }

        DB::table('deleted_account_purchases')->insert([
            'course_ids' => json_encode($courseIds),
            'email' => $user->learner_email ?? '',
            'phone' => $user->learner_phone,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Remove this learner from JSON `likes` blobs on other users' posts.
     *
     * @param  list<int|string>  $keys
     */
    private static function stripUserFromMylikesJson($uid, array $keys): void
    {
        if (!Schema::hasTable('mylikes')) {
            return;
        }

        $match = function ($entryUserId) use ($uid, $keys): bool {
            foreach ($keys as $k) {
                if ((string) $entryUserId === (string) $k) {
                    return true;
                }
                if (is_numeric($entryUserId) && is_numeric($k) && (int) $entryUserId === (int) $k) {
                    return true;
                }
            }

            return false;
        };

        DB::table('mylikes')->orderBy('id')->chunkById(200, function ($rows) use ($match) {
            foreach ($rows as $row) {
                $arr = json_decode($row->likes, true);
                if (!is_array($arr)) {
                    continue;
                }
                $filtered = [];
                foreach ($arr as $entry) {
                    if (!is_array($entry)) {
                        $filtered[] = $entry;
                        continue;
                    }
                    $entryUserId = $entry['user_id'] ?? null;
                    if ($entryUserId === null || !$match($entryUserId)) {
                        $filtered[] = $entry;
                    }
                }
                if (count($filtered) === count($arr)) {
                    continue;
                }
                if ($filtered === []) {
                    DB::table('mylikes')->where('id', $row->id)->delete();
                } else {
                    DB::table('mylikes')->where('id', $row->id)->update(['likes' => json_encode($filtered)]);
                }
            }
        });
    }
}
