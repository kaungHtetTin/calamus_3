<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('artists')) {
            return;
        }

        if (!Schema::hasColumn('artists', 'image_url')) {
            Schema::table('artists', function (Blueprint $table) {
                $table->string('image_url')->nullable()->index();
            });
        }

        if (Schema::hasColumn('artists', 'image_slug')) {
            DB::statement("
                UPDATE artists
                SET image_url = CONCAT('https://www.calamuseducation.com/uploads/songs/web/', image_slug, '.png')
                WHERE (image_url IS NULL OR image_url = '')
                  AND image_slug IS NOT NULL
                  AND image_slug != ''
            ");
        }
    }

    public function down()
    {
    }
};

