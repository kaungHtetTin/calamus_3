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
        Schema::table('studies', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('lesson_id');
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('studies', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['lesson_id']);
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropIndex(['category_id']);
        });
    }
};
