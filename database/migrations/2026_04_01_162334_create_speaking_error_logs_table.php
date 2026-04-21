<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::create('speaking_error_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id'); // Corresponds to learner.user_id (phone)
            $table->string('major', 20);
            $table->unsignedBigInteger('dialogue_id');
            $table->text('error_text');
            $table->timestamps();

            $table->index(['user_id', 'major']);
        });

        if (DB::getDriverName() === 'mysql') {
            try {
                DB::statement('ALTER TABLE `speaking_error_logs` ENGINE=InnoDB');
            } catch (\Exception $e) {
            }
        }

        if (Schema::hasTable('speaking_dialogues') && Schema::hasColumn('speaking_dialogues', 'id')) {
            try {
                Schema::table('speaking_error_logs', function (Blueprint $table) {
                    $table->foreign('dialogue_id')->references('id')->on('speaking_dialogues')->onDelete('cascade');
                });
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('speaking_error_logs');
    }
};
