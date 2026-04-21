<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('lesson_likes')) {
            Schema::create('lesson_likes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('lesson_id');
                $table->string('user_id', 50);
                $table->timestamps();

                $table->unique(['lesson_id', 'user_id']);
                $table->index(['user_id']);
            });
        }

        if (!Schema::hasTable('mylikes') || !Schema::hasTable('lessons')) {
            return;
        }

        // Backfill legacy likes from mylikes(content_id=post_id) -> lesson_likes(lesson_id,user_id)
        DB::table('mylikes')->orderBy('id')->chunk(200, function ($rows) {
            foreach ($rows as $row) {
                $postId = (int)($row->content_id ?? 0);
                if ($postId <= 0) {
                    continue;
                }

                $lessonId = DB::table('lessons')->where('date', $postId)->value('id');
                if (!$lessonId) {
                    continue; // like belongs to a normal post, not a lesson
                }

                $likesArr = json_decode((string)$row->likes, true);
                if (!is_array($likesArr)) {
                    continue;
                }

                foreach ($likesArr as $item) {
                    $userId = trim((string)($item['user_id'] ?? ''));
                    if ($userId === '') {
                        continue;
                    }

                    DB::table('lesson_likes')->updateOrInsert(
                        [
                            'lesson_id' => (int)$lessonId,
                            'user_id' => $userId,
                        ],
                        [
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
            }
        });

        // Keep lessons.like_count as copied from legacy posts during social backfill.
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_likes');
    }
};

