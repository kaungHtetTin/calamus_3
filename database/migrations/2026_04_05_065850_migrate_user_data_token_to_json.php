<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        if (!Schema::hasTable('user_data')) {
            return;
        }

        if (!Schema::hasColumn('user_data', 'token_new')) {
            Schema::table('user_data', function (Blueprint $table) {
                $table->json('token_new')->nullable()->after('token');
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
        return;
    }
};
