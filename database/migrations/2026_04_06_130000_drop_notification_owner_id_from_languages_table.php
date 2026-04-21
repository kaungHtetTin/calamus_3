<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('languages') && Schema::hasColumn('languages', 'notification_owner_id')) {
            $database = DB::getDatabaseName();
            $foreignKeyName = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('TABLE_SCHEMA', $database)
                ->where('TABLE_NAME', 'languages')
                ->where('COLUMN_NAME', 'notification_owner_id')
                ->whereNotNull('REFERENCED_TABLE_NAME')
                ->value('CONSTRAINT_NAME');

            if ($foreignKeyName) {
                DB::statement("ALTER TABLE `languages` DROP FOREIGN KEY `{$foreignKeyName}`");
            }

            Schema::table('languages', function (Blueprint $table) {
                $table->dropColumn('notification_owner_id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('languages') && !Schema::hasColumn('languages', 'notification_owner_id')) {
            Schema::table('languages', function (Blueprint $table) {
                $table->unsignedBigInteger('notification_owner_id')->nullable()->after('module_code');
            });
        }
    }
};
