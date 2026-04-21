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
        if (Schema::hasTable('studies')) {
            Schema::table('studies', function (Blueprint $table) {
                if (!Schema::hasColumn('studies', 'user_id')) {
                    $table->bigInteger('user_id')->nullable()->after('id');
                }
            });

            // 2. Migrate learner_id to user_id
            DB::statement("UPDATE studies SET user_id = learner_id WHERE user_id IS NULL AND learner_id IS NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('studies') && Schema::hasColumn('studies', 'user_id')) {
            Schema::table('studies', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }
    }
};
