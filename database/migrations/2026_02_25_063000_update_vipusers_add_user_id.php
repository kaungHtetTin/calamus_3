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
        if (Schema::hasTable('vipusers')) {
            Schema::table('vipusers', function (Blueprint $table) {
                if (!Schema::hasColumn('vipusers', 'user_id')) {
                    $table->bigInteger('user_id')->nullable()->after('id');
                }
            });

            // 2. Migrate phone to user_id
            DB::statement("UPDATE vipusers SET user_id = phone WHERE user_id IS NULL AND phone IS NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('vipusers') && Schema::hasColumn('vipusers', 'user_id')) {
            Schema::table('vipusers', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }
    }
};
