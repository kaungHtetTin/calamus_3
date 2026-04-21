<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->backfillLessonSocial();
        $this->backfillCommentTargets();
    }

    public function down(): void
    {
        // Data backfill is intentionally irreversible.
    }

    private function backfillLessonSocial(): void
    {
        if (!Schema::hasTable('lessons') || !Schema::hasTable('posts')) {
            return;
        }

        $lessons = DB::table('lessons')
            ->select('id', 'date', 'isVideo')
            ->whereNotNull('date')
            ->where('date', '>', 0)
            ->orderBy('id')
            ->get();

        foreach ($lessons as $lesson) {
            $post = DB::table('posts')
                ->select('post_like', 'comments', 'share_count', 'view_count', 'vimeo', 'video_url')
                ->where('post_id', (int)$lesson->date)
                ->first();

            if (!$post) {
                continue;
            }

            DB::table('lessons')
                ->where('id', (int)$lesson->id)
                ->update([
                    // Keep source-of-truth from posts for migration parity.
                    'like_count' => (int)($post->post_like ?? 0),
                    'comment_count' => (int)($post->comments ?? 0),
                    'share_count' => (int)($post->share_count ?? 0),
                    'view_count' => (int)($post->view_count ?? 0),
                    'link' => !empty($post->vimeo) ? $post->vimeo : DB::raw('link'),
                    'download_url' => ((int)$lesson->isVideo === 1 && !empty($post->video_url))
                        ? $post->video_url
                        : DB::raw('download_url'),
                ]);
        }
    }

    private function backfillCommentTargets(): void
    {
        if (!Schema::hasTable('comment') || !Schema::hasTable('lessons')) {
            return;
        }

        $comments = DB::table('comment')
            ->select('id', 'post_id')
            ->orderBy('id')
            ->get();

        foreach ($comments as $comment) {
            $postId = (int)($comment->post_id ?? 0);
            if ($postId <= 0) {
                continue;
            }

            $lessonId = DB::table('lessons')
                ->where('date', $postId)
                ->value('id');

            if ($lessonId) {
                DB::table('comment')
                    ->where('id', (int)$comment->id)
                    ->update([
                        'target_type' => 'lesson',
                        'target_id' => (int)$lessonId,
                    ]);
            } else {
                DB::table('comment')
                    ->where('id', (int)$comment->id)
                    ->update([
                        'target_type' => 'post',
                        'target_id' => $postId,
                    ]);
            }
        }
    }

};

