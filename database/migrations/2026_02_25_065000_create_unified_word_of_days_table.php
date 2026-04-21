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
            Schema::create('word_of_days', function (Blueprint $table) {
                $table->id();
                $table->string('major', 20); // english, korea, chinese, etc.
                
                // Common fields
                $table->string('word', 255); // The main word (was 'english', 'korea', etc.)
                $table->string('translation', 255); // Myanmar translation (was 'myanmar')
                $table->string('speech', 100)->nullable(); // Part of speech or similar
                $table->text('example')->nullable(); // Example sentence
                $table->string('thumb', 500)->nullable(); // Image URL
                $table->string('audio', 500)->nullable(); // Audio URL (if exists in some tables)
                
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
        Schema::dropIfExists('word_of_days');
    }
};
