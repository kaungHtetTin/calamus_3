<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('messages') || !Schema::hasColumn('messages', 'file_path')) {
            return;
        }

        DB::statement("
            UPDATE messages
            SET file_path = CONCAT('https://www.calamuseducation.com/', TRIM(LEADING '/' FROM file_path))
            WHERE file_path IS NOT NULL
              AND file_path != ''
              AND file_path NOT LIKE 'http://%'
              AND file_path NOT LIKE 'https://%'
        ");
    }

    public function down()
    {
        if (!Schema::hasTable('messages') || !Schema::hasColumn('messages', 'file_path')) {
            return;
        }

        DB::statement("
            UPDATE messages
            SET file_path = TRIM(LEADING '/' FROM REPLACE(file_path, 'https://www.calamuseducation.com/', ''))
            WHERE file_path LIKE 'https://www.calamuseducation.com/%'
        ");
    }
};
