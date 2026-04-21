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
        Schema::table('mini_programs', function (Blueprint $table) {
            $table->dropColumn(['function_type', 'function_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mini_programs', function (Blueprint $table) {
            $table->tinyInteger('function_type')->after('image_url');
            $table->tinyInteger('function_id')->after('function_type');
        });
    }
};
