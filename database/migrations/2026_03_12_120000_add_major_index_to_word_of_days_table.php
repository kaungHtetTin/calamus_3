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
        if (!Schema::hasTable('word_of_days')) {
            return;
        }

        Schema::table('word_of_days', function (Blueprint $table) {
            $table->index('major', 'word_of_days_major_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasTable('word_of_days')) {
            return;
        }

        Schema::table('word_of_days', function (Blueprint $table) {
            $table->dropIndex('word_of_days_major_index');
        });
    }
};
