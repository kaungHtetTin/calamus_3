<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Make learner_id nullable in studies table
        if (Schema::hasTable('studies') && Schema::hasColumn('studies', 'learner_id')) {
            Schema::table('studies', function (Blueprint $table) {
                $table->bigInteger('learner_id')->nullable()->change();
            });
        }

        // Make learner_id nullable in posts table
        if (Schema::hasTable('posts') && Schema::hasColumn('posts', 'learner_id')) {
            $this->dropForeignKeysOnColumn('posts', 'learner_id');
            Schema::table('posts', function (Blueprint $table) {
                $table->bigInteger('learner_id')->nullable()->change();
            });
        }

        // Make phone nullable in vipusers table (phone was the legacy user identifier)
        if (Schema::hasTable('vipusers') && Schema::hasColumn('vipusers', 'phone')) {
            Schema::table('vipusers', function (Blueprint $table) {
                $table->string('phone')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('studies') && Schema::hasColumn('studies', 'learner_id')) {
            Schema::table('studies', function (Blueprint $table) {
                $table->bigInteger('learner_id')->nullable(false)->change();
            });
        }

        if (Schema::hasTable('posts') && Schema::hasColumn('posts', 'learner_id')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->bigInteger('learner_id')->nullable(false)->change();
            });
        }

        if (Schema::hasTable('vipusers') && Schema::hasColumn('vipusers', 'phone')) {
            Schema::table('vipusers', function (Blueprint $table) {
                $table->string('phone')->nullable(false)->change();
            });
        }
    }

    private function dropForeignKeysOnColumn(string $table, string $column): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (!Schema::hasTable($table)) {
            return;
        }

        $database = DB::getDatabaseName();
        $constraints = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->pluck('CONSTRAINT_NAME')
            ->map(function ($v) {
                return (string) $v;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        foreach ($constraints as $name) {
            try {
                Schema::table($table, function (Blueprint $tableObj) use ($name) {
                    $tableObj->dropForeign($name);
                });
            } catch (\Exception $e) {
            }
        }
    }
};
