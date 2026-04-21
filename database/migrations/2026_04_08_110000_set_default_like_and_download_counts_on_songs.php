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

        if (Schema::hasColumn('songs', 'like_count')) {
            DB::statement('UPDATE songs SET like_count = 0 WHERE like_count IS NULL');
            DB::statement('ALTER TABLE songs MODIFY like_count INT NOT NULL DEFAULT 0');
        }

        if (Schema::hasColumn('songs', 'download_count')) {
            DB::statement('UPDATE songs SET download_count = 0 WHERE download_count IS NULL');
            DB::statement('ALTER TABLE songs MODIFY download_count INT NOT NULL DEFAULT 0');
        }
    }

    public function down()
    {
    }
};

