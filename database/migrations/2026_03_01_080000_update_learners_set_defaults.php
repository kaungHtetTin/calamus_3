<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('learners')) {
            return;
        }

        $db = env('DB_DATABASE');
        $defaults = [
            'learner_phone' => '',
            'learner_email' => '',
            'learner_name' => '',
            'learner_image' => 'https://www.calamuseducation.com/uploads/placeholder.png',
            'cover_image' => '',
            'gender' => '',
            'bd_day' => 0,
            'bd_month' => 0,
            'bd_year' => 0,
            'work' => '',
            'education' => '',
            'region' => '',
            'bio' => '',
            'otp' => '0',
            'auth_token' => '[]',
            'auth_token_mobile' => '[]',
        ];

        foreach ($defaults as $column => $value) {
            if (!Schema::hasColumn('learners', $column)) {
                continue;
            }

            $info = DB::table('information_schema.columns')
                ->select('COLUMN_TYPE', 'IS_NULLABLE')
                ->where('TABLE_SCHEMA', $db)
                ->where('TABLE_NAME', 'learners')
                ->where('COLUMN_NAME', $column)
                ->first();

            if (!$info) {
                continue;
            }

            $type = $info->COLUMN_TYPE;
            $nullable = ($info->IS_NULLABLE === 'YES') ? 'NULL' : 'NOT NULL';
            $isNumericDefault = is_int($value) || is_float($value);
            $defaultSql = $isNumericDefault ? (string)$value : ("'" . addslashes($value) . "'");

            $sql = "ALTER TABLE `learners` MODIFY `{$column}` {$type} {$nullable} DEFAULT {$defaultSql}";
            try {
                DB::statement($sql);
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }

    public function down()
    {
        if (!Schema::hasTable('learners')) {
            return;
        }

        $db = env('DB_DATABASE');
        $columns = array_keys([
            'learner_phone' => true,
            'learner_email' => true,
            'learner_name' => true,
            'learner_image' => true,
            'cover_image' => true,
            'gender' => true,
            'bd_day' => true,
            'bd_month' => true,
            'bd_year' => true,
            'work' => true,
            'education' => true,
            'region' => true,
            'bio' => true,
            'otp' => true,
            'auth_token' => true,
            'auth_token_mobile' => true,
        ]);

        foreach ($columns as $column) {
            if (!Schema::hasColumn('learners', $column)) {
                continue;
            }

            $info = DB::table('information_schema.columns')
                ->select('COLUMN_TYPE', 'IS_NULLABLE')
                ->where('TABLE_SCHEMA', $db)
                ->where('TABLE_NAME', 'learners')
                ->where('COLUMN_NAME', $column)
                ->first();

            if (!$info) {
                continue;
            }

            $type = $info->COLUMN_TYPE;
            $nullable = ($info->IS_NULLABLE === 'YES') ? 'NULL' : 'NOT NULL';
            $defaultSql = ($nullable === 'NULL') ? 'NULL' : "''";

            $sql = "ALTER TABLE `learners` MODIFY `{$column}` {$type} {$nullable} DEFAULT {$defaultSql}";
            try {
                DB::statement($sql);
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }
};
