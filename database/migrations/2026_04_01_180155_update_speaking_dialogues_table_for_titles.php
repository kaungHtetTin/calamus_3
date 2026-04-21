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
        Schema::table('speaking_dialogues', function (Blueprint $table) {
            // Drop existing index first if it exists
            $table->dropIndex(['major', 'level']);
            $table->dropColumn('level');
            
            $table->foreignId('speaking_dialogue_title_id')->after('major')->constrained('speaking_dialogue_titles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('speaking_dialogues', function (Blueprint $table) {
            $table->dropForeign(['speaking_dialogue_title_id']);
            $table->dropColumn('speaking_dialogue_title_id');
            
            $table->integer('level')->after('major')->index();
            $table->index(['major', 'level']);
        });
    }
};
