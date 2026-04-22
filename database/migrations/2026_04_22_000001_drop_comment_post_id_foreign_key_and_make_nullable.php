<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('comment') || !Schema::hasColumn('comment', 'post_id')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            $database = DB::getDatabaseName();

            $constraints = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('TABLE_SCHEMA', $database)
                ->where('TABLE_NAME', 'comment')
                ->where('COLUMN_NAME', 'post_id')
                ->whereNotNull('REFERENCED_TABLE_NAME')
                ->pluck('CONSTRAINT_NAME')
                ->map(fn ($v) => (string) $v)
                ->filter()
                ->unique()
                ->values()
                ->all();

            foreach ($constraints as $name) {
                try {
                    DB::statement("ALTER TABLE `comment` DROP FOREIGN KEY `{$name}`");
                } catch (\Throwable $e) {
                }
            }
        }

        try {
            DB::table('comment')->where('post_id', 0)->update(['post_id' => null]);
        } catch (\Throwable $e) {
        }

        $type = null;
        try {
            $type = Schema::getColumnType('comment', 'post_id');
        } catch (\Throwable $e) {
        }

        Schema::table('comment', function (Blueprint $table) use ($type) {
            if ($type === 'bigint') {
                $table->unsignedBigInteger('post_id')->nullable()->default(null)->change();
                return;
            }

            $table->integer('post_id')->nullable()->default(null)->change();
        });
    }

    public function down()
    {
    }
};
