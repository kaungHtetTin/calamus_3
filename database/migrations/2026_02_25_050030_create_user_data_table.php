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
            Schema::create('user_data', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('user_id'); // Corresponds to phone number in legacy
                $table->string('major', 20); // english, korea, chinese, etc.
                
                // Common fields across all language tables
                $table->tinyInteger('is_vip')->default(0);
                $table->tinyInteger('diamond_plan')->default(0);
                $table->integer('game_score')->default(0);
                $table->integer('speaking_level')->nullable(); // Specific to some
             
                $table->string('token', 500)->nullable(); // FCM Token?
                $table->integer('login_time')->default(0);
                $table->timestamp('first_join')->nullable();
                $table->timestamp('last_active')->nullable();
          
                $table->timestamps();

                // Unique constraint: One record per user per major
                $table->unique(['user_id', 'major']);
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
        Schema::dropIfExists('user_data');
    }
};
