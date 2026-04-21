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
        if (Schema::hasTable('partner_earnings')) {
            Schema::table('partner_earnings', function (Blueprint $table) {
                if (!Schema::hasColumn('partner_earnings', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('id');
                    $table->index('user_id');
                }
            });

            // 2. Transfer learner_phone data to user_id
            // This assumes learner_phone contains the value intended for user_id
            DB::statement("UPDATE partner_earnings SET user_id = learner_phone WHERE user_id IS NULL AND learner_phone IS NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('partner_earnings') && Schema::hasColumn('partner_earnings', 'user_id')) {
            Schema::table('partner_earnings', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }
    }
};
