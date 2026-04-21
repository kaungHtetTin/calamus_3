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
        Schema::table('learners', function (Blueprint $table) {
            $table->string('work')->nullable()->change();
            $table->string('education')->nullable()->change();
            $table->string('region')->nullable()->change();
            $table->text('bio')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('learners', function (Blueprint $table) {
            $table->string('work')->nullable(false)->change();
            $table->string('education')->nullable(false)->change();
            $table->string('region')->nullable(false)->change();
            $table->text('bio')->nullable(false)->change();
        });
    }
};
