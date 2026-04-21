<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardService
{
    private const SUPPORT_ADMIN_USER_ID = 10000;

    /**
     * Get data for the admin dashboard.
     *
     * @return array
     */
    public function getDashboardData(): array
    {
        $countIfTableExists = function (string $table): int {
            if (!Schema::hasTable($table)) {
                return 0;
            }
            return (int) DB::table($table)->count();
        };

        $recentLessonCommentsByAdmin = [];
        if (Schema::hasTable('comment') && Schema::hasTable('lessons')) {
            $recentLessonCommentsByAdmin = DB::table('comment')
                ->leftJoin('lessons', 'lessons.id', '=', 'comment.target_id')
                ->leftJoin('lessons_categories as lc', 'lc.id', '=', 'lessons.category_id')
                ->select([
                    'comment.id',
                    'comment.target_id as lesson_id',
                    'lessons.title as lesson_title',
                    'lessons.isVideo as is_video',
                    'lessons.category_id as category_id',
                    'lc.course_id as course_id',
                    'comment.body',
                    'comment.image',
                    'comment.time',
                ])
                ->where('comment.target_type', 'lesson')
                ->where('comment.writer_id', 10000)
                ->orderByDesc('comment.time')
                ->limit(10)
                ->get()
                ->map(function ($row) {
                    return [
                        'id' => (int) ($row->id ?? 0),
                        'lesson_id' => (int) ($row->lesson_id ?? 0),
                        'lesson_title' => (string) ($row->lesson_title ?? ''),
                        'course_id' => (int) ($row->course_id ?? 0),
                        'category_id' => (int) ($row->category_id ?? 0),
                        'is_video' => (int) ($row->is_video ?? 0),
                        'body' => (string) ($row->body ?? ''),
                        'image' => (string) ($row->image ?? ''),
                        'time' => (int) ($row->time ?? 0),
                    ];
                })
                ->all();
        }

        $recentConversations = [];
        if (Schema::hasTable('conversations') && Schema::hasTable('messages')) {
            $supportConversations = DB::table('conversations')
                ->where(function ($q) {
                    $q->where('user1_id', self::SUPPORT_ADMIN_USER_ID)->orWhere('user2_id', self::SUPPORT_ADMIN_USER_ID);
                })
                ->orderByDesc('last_message_at')
                ->orderByDesc('id')
                ->limit(10)
                ->get(['id', 'user1_id', 'user2_id', 'major', 'last_message_at']);

            if ($supportConversations->isNotEmpty()) {
                $conversationIds = $supportConversations->pluck('id')->map(fn ($v) => (int) $v)->all();
                $friendIds = $supportConversations
                    ->map(function ($c) {
                        $u1 = (int) ($c->user1_id ?? 0);
                        $u2 = (int) ($c->user2_id ?? 0);
                        return $u1 === self::SUPPORT_ADMIN_USER_ID ? $u2 : $u1;
                    })
                    ->filter(fn ($id) => (int) $id > 0)
                    ->unique()
                    ->values()
                    ->all();

                $learnersById = [];
                if (!empty($friendIds) && Schema::hasTable('learners')) {
                    $learnersById = DB::table('learners')
                        ->whereIn('user_id', $friendIds)
                        ->get(['user_id', 'learner_name', 'learner_image', 'learner_phone', 'learner_email'])
                        ->keyBy('user_id')
                        ->all();
                }

                $unreadCounts = DB::table('messages')
                    ->select('conversation_id', DB::raw('COUNT(*) as unread_count'))
                    ->whereIn('conversation_id', $conversationIds)
                    ->where('sender_id', '!=', self::SUPPORT_ADMIN_USER_ID)
                    ->where('is_read', 0)
                    ->groupBy('conversation_id')
                    ->pluck('unread_count', 'conversation_id')
                    ->toArray();

                $latestMsgIds = DB::table('messages')
                    ->select(DB::raw('MAX(id) as max_id'))
                    ->whereIn('conversation_id', $conversationIds)
                    ->groupBy('conversation_id');

                $lastMessages = DB::table('messages')
                    ->joinSub($latestMsgIds, 'latest', function ($join) {
                        $join->on('messages.id', '=', 'latest.max_id');
                    })
                    ->select('messages.conversation_id', 'messages.message_text', 'messages.message_type', 'messages.file_path', 'messages.created_at')
                    ->get()
                    ->keyBy('conversation_id')
                    ->all();

                $recentConversations = $supportConversations
                    ->map(function ($c) use ($learnersById, $unreadCounts, $lastMessages) {
                        $u1 = (int) ($c->user1_id ?? 0);
                        $u2 = (int) ($c->user2_id ?? 0);
                        $friendId = $u1 === self::SUPPORT_ADMIN_USER_ID ? $u2 : $u1;
                        $learner = $learnersById[$friendId] ?? null;
                        $last = $lastMessages[(int) $c->id] ?? null;

                        return [
                            'id' => (int) ($c->id ?? 0),
                            'major' => (string) ($c->major ?? ''),
                            'other_user_id' => (int) $friendId,
                            'unread_count' => (int) ($unreadCounts[(int) $c->id] ?? 0),
                            'last_message_text' => $last ? (string) ($last->message_text ?? '') : '',
                            'last_message_type' => $last ? (string) ($last->message_type ?? '') : '',
                            'last_message_file_path' => $last ? (string) ($last->file_path ?? '') : '',
                            'last_message_at' => $c->last_message_at,
                            'friend' => $learner ? [
                                'id' => (int) ($learner->user_id ?? 0),
                                'name' => (string) ($learner->learner_name ?? ''),
                                'image' => (string) ($learner->learner_image ?? ''),
                                'phone' => (string) ($learner->learner_phone ?? ''),
                                'email' => (string) ($learner->learner_email ?? ''),
                            ] : null,
                        ];
                    })
                    ->all();
            }
        }

        return [
            'stats' => [
                'users_count' => $countIfTableExists('users'),
                'learners_count' => $countIfTableExists('learners'),
                'courses_count' => $countIfTableExists('courses'),
                'lessons_count' => $countIfTableExists('lessons'),
                'lesson_categories_count' => $countIfTableExists('lessons_categories'),
                'posts_count' => $countIfTableExists('posts'),
                'comments_count' => $countIfTableExists('comment'),
                'conversations_count' => $countIfTableExists('conversations'),
                'messages_count' => $countIfTableExists('messages'),
                'songs_count' => $countIfTableExists('songs'),
                'artists_count' => $countIfTableExists('artists'),
                'payments_count' => $countIfTableExists('payments'),
                'notifications_count' => (Schema::hasTable('notifications') && Schema::hasColumn('notifications', 'read_at'))
                    ? (int) DB::table('notifications')->whereNull('read_at')->count()
                    : 0,
                'apps_count' => $countIfTableExists('apps'),
                'mini_programs_count' => $countIfTableExists('mini_programs'),
            ],
            'recent_users' => Schema::hasTable('users') ? DB::table('users')->orderByDesc('id')->limit(5)->get() : [],
            'recent_conversations' => $recentConversations,
            'recent_lesson_comments_by_admin' => $recentLessonCommentsByAdmin,
        ];
    }
}
