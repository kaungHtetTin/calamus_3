<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Add user_id column if it doesn't exist
        if (Schema::hasTable('posts')) {
            Schema::table('posts', function (Blueprint $table) {
                if (!Schema::hasColumn('posts', 'user_id')) {
                    $table->bigInteger('user_id')->nullable()->after('id');
                }
            });

            // 2. Migrate learner_id to user_id
            // Note: learner_id in posts refers to the phone number (user_id) of the learner? 
            // Or does it refer to the 'id' of the learner table?
            // In legacy Calamus, learner_id usually referred to the phone number (which is the user ID concept).
            // Let's assume learner_id IS the phone number/user identifier we want to migrate.
            DB::statement("UPDATE posts SET user_id = learner_id WHERE user_id IS NULL AND learner_id IS NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('posts') && Schema::hasColumn('posts', 'user_id')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }
    }
};
