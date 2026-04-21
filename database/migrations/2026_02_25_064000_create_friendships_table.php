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
        if (!Schema::hasTable('friendships')) {
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

            Schema::create('friendships', function (Blueprint $table) {
                $table->id();
                $table->engine = 'InnoDB';
                
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('friend_id');
                
                $table->timestamps();

                // Unique constraint to prevent duplicate friendships (Global Scope)
                $table->unique(['user_id', 'friend_id']);
            });

            if (Schema::hasTable('learners') && Schema::hasColumn('learners', 'user_id')) {
                try {
                    Schema::table('friendships', function (Blueprint $table) {
                        $table->foreign('user_id')->references('user_id')->on('learners')->onDelete('cascade');
                        $table->foreign('friend_id')->references('user_id')->on('learners')->onDelete('cascade');
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
        Schema::dropIfExists('friendships');
    }
};
