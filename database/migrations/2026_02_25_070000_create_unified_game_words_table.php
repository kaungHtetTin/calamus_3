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
        if (!Schema::hasTable('game_words')) {
            Schema::create('game_words', function (Blueprint $table) {
                $table->id();
                $table->string('major', 20); // english, korea, chinese, etc.
                
                // Common fields
                $table->string('display_word', 255);
                $table->string('display_image', 1000)->nullable();
                $table->string('display_audio', 1000)->nullable();
                $table->tinyInteger('category')->default(0);
                
                // Options
                $table->string('a', 255)->nullable();
                $table->string('b', 255)->nullable();
                $table->string('c', 255)->nullable();
                
                $table->string('ans', 10); // Answer key (e.g., 'a', 'b', 'c')
                
                $table->timestamps();
                $table->softDeletes();
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
        Schema::dropIfExists('game_words');
    }
};
