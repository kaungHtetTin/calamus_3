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

        Schema::table('songs', function (Blueprint $table) {
            if (!Schema::hasColumn('songs', 'audio_url')) {
                $table->string('audio_url')->nullable()->index();
            }
            if (!Schema::hasColumn('songs', 'image_url')) {
                $table->string('image_url')->nullable()->index();
            }
            if (!Schema::hasColumn('songs', 'thumbnail_url')) {
                $table->string('thumbnail_url')->nullable()->index();
            }
            if (!Schema::hasColumn('songs', 'lyric_url')) {
                $table->string('lyric_url')->nullable()->index();
            }
        });

        if (Schema::hasColumn('songs', 'asset_slug')) {
            DB::statement("
                UPDATE songs
                SET
                    audio_url = COALESCE(audio_url, CONCAT('https://www.calamuseducation.com/uploads/songs/audio/', asset_slug, '.mp3')),
                    image_url = COALESCE(image_url, CONCAT('https://www.calamuseducation.com/uploads/songs/web/', asset_slug, '.png')),
                    thumbnail_url = COALESCE(thumbnail_url, CONCAT('https://www.calamuseducation.com/uploads/songs/image/', asset_slug, '.png')),
                    lyric_url = COALESCE(lyric_url, CONCAT('https://www.calamuseducation.com/uploads/songs/lyrics/', asset_slug, '.txt'))
                WHERE asset_slug IS NOT NULL AND asset_slug != ''
            ");
        }
    }

    public function down()
    {
    }
};

