<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('songs') || !Schema::hasColumn('songs', 'legacy_song_id')) {
            return;
        }

        if (!Schema::hasColumn('songs', 'legacy_meta')) {
            Schema::table('songs', function (Blueprint $table) {
                $table->longText('legacy_meta')->nullable();
            });
        }

        DB::table('songs')
            ->select('id', 'legacy_meta', 'legacy_song_id')
            ->orderBy('id')
            ->chunkById(500, function ($chunk) {
                foreach ($chunk as $row) {
                    $existing = trim((string) ($row->legacy_meta ?? ''));
                    $payload = [];
                    if ($existing !== '') {
                        $decoded = json_decode($existing, true);
                        if (is_array($decoded)) {
                            $payload = $decoded;
                        }
                    }
                    if (!array_key_exists('legacy_song_id', $payload)) {
                        $payload['legacy_song_id'] = $row->legacy_song_id;
                        DB::table('songs')->where('id', $row->id)->update([
                            'legacy_meta' => json_encode($payload),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });

        Schema::table('songs', function (Blueprint $table) {
            $table->dropColumn('legacy_song_id');
        });
    }

    public function down()
    {
    }
};

