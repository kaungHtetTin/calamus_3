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
        if (!Schema::hasTable('song_likes')) {
            $hasSongs = Schema::hasTable('songs') && Schema::hasColumn('songs', 'id');

            $songIdIsBigInt = false;
            if ($hasSongs && DB::getDriverName() === 'mysql') {
                try {
                    DB::statement('ALTER TABLE `songs` ENGINE=InnoDB');
                } catch (\Exception $e) {
                }

                try {
                    $db = DB::getDatabaseName();
                    $type = DB::table('information_schema.COLUMNS')
                        ->where('TABLE_SCHEMA', $db)
                        ->where('TABLE_NAME', 'songs')
                        ->where('COLUMN_NAME', 'id')
                        ->value('COLUMN_TYPE');
                    $type = strtolower((string) $type);
                    $songIdIsBigInt = str_contains($type, 'bigint');
                } catch (\Exception $e) {
                }
            }

            Schema::create('song_likes', function (Blueprint $table) use ($songIdIsBigInt) {
                $table->id();
                $table->engine = 'InnoDB';
                $table->bigInteger('user_id')->index();
                if ($songIdIsBigInt) {
                    $table->unsignedBigInteger('song_id');
                } else {
                    $table->unsignedInteger('song_id');
                }
                $table->timestamp('created_at')->nullable();
                
                $table->unique(['user_id', 'song_id']);
            });

            if ($hasSongs) {
                try {
                    Schema::table('song_likes', function (Blueprint $table) {
                        $table->foreign('song_id')->references('id')->on('songs')->onDelete('cascade');
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
        Schema::dropIfExists('song_likes');
    }
};
