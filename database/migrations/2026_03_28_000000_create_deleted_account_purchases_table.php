<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Past course purchase snapshots when a learner account is removed.
     * course_ids is a JSON array of purchased course IDs (no FK so history survives course changes).
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('deleted_account_purchases')) {
            Schema::create('deleted_account_purchases', function (Blueprint $table) {
                $table->id();
                $table->json('course_ids');
                $table->string('email', 255);
                $table->string('phone', 64)->nullable();
                $table->timestamps();

                $table->index('email');
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
        Schema::dropIfExists('deleted_account_purchases');
    }
};
