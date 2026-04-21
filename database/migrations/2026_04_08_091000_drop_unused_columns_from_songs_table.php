<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('songs')) {
            return;
        }

        $hasDrama = Schema::hasColumn('songs', 'drama');
        $hasCommentCount = Schema::hasColumn('songs', 'comment_count');

        if (!$hasDrama && !$hasCommentCount) {
            return;
        }

        if (!Schema::hasColumn('songs', 'legacy_meta')) {
            Schema::table('songs', function (Blueprint $table) {
                $table->longText('legacy_meta')->nullable();
            });
        }

        DB::table('songs')
            ->select('id', 'legacy_meta', 'drama', 'comment_count')
            ->orderBy('id')
            ->chunkById(500, function ($chunk) use ($hasDrama, $hasCommentCount) {
                foreach ($chunk as $row) {
                    $existing = trim((string) ($row->legacy_meta ?? ''));
                    if ($existing !== '') {
                        continue;
                    }

                    $payload = [];
                    if ($hasDrama) {
                        $payload['drama'] = $row->drama;
                    }
                    if ($hasCommentCount) {
                        $payload['comment_count'] = $row->comment_count;
                    }

                    DB::table('songs')->where('id', $row->id)->update([
                        'legacy_meta' => json_encode($payload),
                        'updated_at' => now(),
                    ]);
                }
            });

        Schema::table('songs', function (Blueprint $table) use ($hasDrama, $hasCommentCount) {
            if ($hasDrama) {
                $table->dropColumn('drama');
            }
            if ($hasCommentCount) {
                $table->dropColumn('comment_count');
            }
        });
    }

    public function down()
    {
    }
};
