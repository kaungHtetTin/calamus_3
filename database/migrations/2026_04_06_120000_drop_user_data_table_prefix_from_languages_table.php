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
        if (Schema::hasTable('languages') && Schema::hasColumn('languages', 'user_data_table_prefix')) {
            Schema::table('languages', function (Blueprint $table) {
                $table->dropColumn('user_data_table_prefix');
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
        if (Schema::hasTable('languages') && !Schema::hasColumn('languages', 'user_data_table_prefix')) {
            Schema::table('languages', function (Blueprint $table) {
                $table->string('user_data_table_prefix', 50)->nullable()->after('module_code');
            });
        }
    }
};
