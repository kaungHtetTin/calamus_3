<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        $this->progress('Skipping learners primary key migration (keep learners.id).');
        return;

        if (DB::getDriverName() !== 'mysql') {
            $this->progress('Skipped: only supported on mysql driver.');
            return;
        }

        if (!Schema::hasTable('learners')) {
            $this->progress('Skipped: learners table not found.');
            return;
        }

        if (!Schema::hasColumn('learners', 'id')) {
            $this->progress('Skipped: learners.id not found (already migrated).');
            return;
        }

        if (!Schema::hasColumn('learners', 'user_id')) {
            $this->progress('Adding learners.user_id...');
            Schema::table('learners', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            });
        }

        try {
            $this->progress('Ensuring unique index on learners.user_id...');
            Schema::table('learners', function (Blueprint $table) {
                $table->unique('user_id');
            });
        } catch (\Exception $e) {
        }

        try {
            $this->progress('Copying learners.id -> learners.user_id...');
            $affected = DB::affectingStatement('UPDATE learners SET user_id = id');
            $this->progress('Updated learners rows: ' . (int) $affected);
        } catch (\Exception $e) {
        }

        try {
            $this->progress('Dropping foreign keys referencing learners.user_id (temporary)...');
            $this->dropForeignKeyIfExists('friend_request_lists', 'friend_request_lists_sender_id_foreign');
            $this->dropForeignKeyIfExists('friend_request_lists', 'friend_request_lists_receiver_id_foreign');
            $this->dropForeignKeyIfExists('friendships', 'friendships_user_id_foreign');
            $this->dropForeignKeyIfExists('friendships', 'friendships_friend_id_foreign');
        } catch (\Exception $e) {
        }

        try {
            $this->progress('Dropping unique index on learners.user_id (will become PRIMARY)...');
            Schema::table('learners', function (Blueprint $table) {
                $table->dropUnique(['user_id']);
            });
        } catch (\Exception $e) {
        }

        try {
            $this->progress('Dropping current PRIMARY key on learners...');
            Schema::table('learners', function (Blueprint $table) {
                $table->dropPrimary('PRIMARY');
            });
        } catch (\Exception $e) {
        }

        try {
            $this->progress('Changing learners.user_id to AUTO_INCREMENT...');
            DB::statement('ALTER TABLE `learners` MODIFY `user_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        } catch (\Exception $e) {
        }

        try {
            $this->progress('Setting PRIMARY KEY to learners.user_id...');
            DB::statement('ALTER TABLE `learners` ADD PRIMARY KEY (`user_id`)');
        } catch (\Exception $e) {
        }

        if (Schema::hasColumn('learners', 'id')) {
            try {
                $this->progress('Dropping learners.id column...');
                Schema::table('learners', function (Blueprint $table) {
                    $table->dropColumn('id');
                });
            } catch (\Exception $e) {
            }
        }

        if (Schema::hasTable('friend_request_lists')) {
            $this->progress('Re-adding foreign keys for friend_request_lists...');
            try {
                Schema::table('learners', function (Blueprint $table) {
                    $table->index('user_id');
                });
            } catch (\Exception $e) {
            }

            try {
                Schema::table('friend_request_lists', function (Blueprint $table) {
                    $table->foreign('sender_id')->references('user_id')->on('learners')->onDelete('cascade');
                });
            } catch (\Exception $e) {
            }

            try {
                Schema::table('friend_request_lists', function (Blueprint $table) {
                    $table->foreign('receiver_id')->references('user_id')->on('learners')->onDelete('cascade');
                });
            } catch (\Exception $e) {
            }
        }

        if (Schema::hasTable('friendships')) {
            $this->progress('Re-adding foreign keys for friendships...');
            try {
                Schema::table('learners', function (Blueprint $table) {
                    $table->index('user_id');
                });
            } catch (\Exception $e) {
            }

            try {
                Schema::table('friendships', function (Blueprint $table) {
                    $table->foreign('user_id')->references('user_id')->on('learners')->onDelete('cascade');
                });
            } catch (\Exception $e) {
            }

            try {
                Schema::table('friendships', function (Blueprint $table) {
                    $table->foreign('friend_id')->references('user_id')->on('learners')->onDelete('cascade');
                });
            } catch (\Exception $e) {
            }
        }

        $this->progress('Done migrating learners.');
    }

    public function down()
    {
        return;
    }

    private function progress(string $message): void
    {
        $line = '[Migration] ' . $message;
        if (isset($this->command) && method_exists($this->command, 'getOutput')) {
            $this->command->getOutput()->writeln($line);
            return;
        }
        echo $line . PHP_EOL;
    }

    private function dropForeignKeyIfExists(string $table, string $foreignKey): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        $database = DB::getDatabaseName();
        $exists = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
            AND CONSTRAINT_NAME = ?
        ", [$database, $table, $foreignKey]);

        if (!$exists) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $tableObj) use ($foreignKey) {
                $tableObj->dropForeign($foreignKey);
            });
        } catch (\Exception $e) {
        }
    }
};
