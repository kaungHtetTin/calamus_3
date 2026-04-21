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
        Schema::create('speaking_dialogues', function (Blueprint $table) {
            $table->id();
            $table->string('major', 20)->index(); // english, korea, chinese, etc.
            $table->integer('level')->index();
            $table->text('person_a_text');
            $table->text('person_a_translation')->nullable();
            $table->text('person_b_text');
            $table->text('person_b_translation')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            // For efficient lookup of dialogues within a level
            $table->index(['major', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('speaking_dialogues');
    }
};
