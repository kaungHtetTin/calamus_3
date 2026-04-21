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
        Schema::table('user_data', function (Blueprint $table) {
            if (!Schema::hasColumn('user_data', 'meta')) {
                $table->json('meta')->nullable()->after('speaking_level');
            }
            if (Schema::hasColumn('user_data', 'speaking_dialogue_title_id')) {
                $table->dropColumn('speaking_dialogue_title_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_data', function (Blueprint $table) {
            $table->dropColumn('meta');
        });
    }
};
