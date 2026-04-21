<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('friend_request_lists')) {
            if (DB::getDriverName() === 'mysql' && Schema::hasTable('learners')) {
                try {
                    DB::statement('ALTER TABLE `learners` ENGINE=InnoDB');
                } catch (\Exception $e) {
                }
            }

            if (Schema::hasTable('learners')) {
                try {
                    Schema::table('learners', function (Blueprint $table) {
                        $table->index('user_id');
                    });
                } catch (\Exception $e) {
                }
            }

            Schema::create('friend_request_lists', function (Blueprint $table) {
                $table->id();
                $table->engine = 'InnoDB';
                
                // Define columns first
                $table->unsignedBigInteger('sender_id');
                $table->unsignedBigInteger('receiver_id');
                
                $table->timestamps();

                // Unique constraint: A sender can only request a receiver once globally
                $table->unique(['sender_id', 'receiver_id']);
            });

            if (Schema::hasTable('learners') && Schema::hasColumn('learners', 'user_id')) {
                try {
                    Schema::table('friend_request_lists', function (Blueprint $table) {
                        $table->foreign('sender_id')->references('user_id')->on('learners')->onDelete('cascade');
                        $table->foreign('receiver_id')->references('user_id')->on('learners')->onDelete('cascade');
                    });
                } catch (\Exception $e) {
                }
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
        Schema::dropIfExists('friend_request_lists');
    }
};
