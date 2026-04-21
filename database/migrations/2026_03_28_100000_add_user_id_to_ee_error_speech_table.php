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
     * Legacy ee_error_speech rows keyed user by phone; copy those values into user_id.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('ee_error_speech')) {
            return;
        }

        Schema::table('ee_error_speech', function (Blueprint $table) {
            if (!Schema::hasColumn('ee_error_speech', 'user_id')) {
                if (Schema::hasColumn('ee_error_speech', 'phone')) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('phone');
                } else {
                    $table->unsignedBigInteger('user_id')->nullable();
                }
            }
        });

        if (Schema::hasColumn('ee_error_speech', 'phone')) {
            DB::statement(
                'UPDATE ee_error_speech SET user_id = phone WHERE user_id IS NULL AND phone IS NOT NULL'
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('ee_error_speech') && Schema::hasColumn('ee_error_speech', 'user_id')) {
            Schema::table('ee_error_speech', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }
    }
};
