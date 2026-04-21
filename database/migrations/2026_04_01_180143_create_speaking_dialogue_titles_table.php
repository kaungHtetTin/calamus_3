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
        Schema::create('speaking_dialogue_titles', function (Blueprint $table) {
            $table->id();
            $table->string('major', 20)->index();
            $table->bigInteger('legacy_id')->index(); // This is saturation_id from legacy
            $table->string('title');
            $table->timestamps();
            
            $table->unique(['major', 'legacy_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('speaking_dialogue_titles');
    }
};
