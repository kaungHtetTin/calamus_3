<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        $this->progressLine('Adding learners.user_id (legacy) and migrating learner_phone -> user_id...');

        if (!Schema::hasTable('learners')) {
            $this->progressLine('Skipped: learners table not found.');
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            try {
                DB::statement('ALTER TABLE `learners` ENGINE=InnoDB');
            } catch (\Exception $e) {
            }
        }

        if (!Schema::hasColumn('learners', 'user_id')) {
            $this->progressLine('Adding learners.user_id column...');
            Schema::table('learners', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            });
        } else {
            $this->progressLine('learners.user_id already exists.');
            if (DB::getDriverName() === 'mysql') {
                try {
                    DB::statement('ALTER TABLE `learners` MODIFY `user_id` BIGINT UNSIGNED NULL');
                } catch (\Exception $e) {
                }
            }
        }

        if (Schema::hasColumn('learners', 'learner_phone')) {
            $this->progressLine('Skipping learners.user_id backfill (data migration).');
        } else {
            $this->progressLine('Skipped: learners.learner_phone column not found.');
        }

        $this->progressLine('Skipping learners duplicate cleanup (requested).');

        $this->progressLine('Ensuring unique index on learners.user_id...');
        try {
            Schema::table('learners', function (Blueprint $table) {
                $table->unique('user_id');
            });
        } catch (\Exception $e) {
        }

        $this->progressLine('Ensuring index on learners.user_id...');
        try {
            Schema::table('learners', function (Blueprint $table) {
                $table->index('user_id');
            });
        } catch (\Exception $e) {
        }

        $this->progressLine('Done legacy learners.user_id migration.');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('learners') && Schema::hasColumn('learners', 'user_id')) {
            Schema::table('learners', function (Blueprint $table) {
                $table->dropUnique(['user_id']);
                $table->dropColumn('user_id');
            });
        }
    }

    private function progressLine(string $message): void
    {
        $line = '[Migration] ' . $message;
        if (isset($this->command) && method_exists($this->command, 'getOutput')) {
            $this->command->getOutput()->writeln($line);
            return;
        }
        echo $line . PHP_EOL;
    }
};
