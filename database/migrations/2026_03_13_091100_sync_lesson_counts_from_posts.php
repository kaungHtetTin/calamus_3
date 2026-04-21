<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('lessons') || !Schema::hasTable('posts')) {
            return;
        }

        $lessons = DB::table('lessons')
            ->select('id', 'date')
            ->whereNotNull('date')
            ->where('date', '>', 0)
            ->orderBy('id')
            ->get();

        foreach ($lessons as $lesson) {
            $post = DB::table('posts')
                ->select('post_like', 'comments')
                ->where('post_id', (int)$lesson->date)
                ->first();

            

            if (!$post) {
                continue;
            }

            echo $post->post_like . ' ' . $post->comments . ' ' . $lesson->date . ' ' . $lesson->id . PHP_EOL;

            DB::table('lessons')
                ->where('id', (int)$lesson->id)
                ->update([
                    'like_count' => (int)($post->post_like ?? 0),
                    'comment_count' => (int)($post->comments ?? 0),
                ]);
        }
    }

    public function down(): void
    {
        // Intentionally irreversible.
    }
};

