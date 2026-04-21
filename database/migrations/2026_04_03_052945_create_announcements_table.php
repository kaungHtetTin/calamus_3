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
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('link', 500);
            $table->string('major', 20);
            $table->text('is_seen');
            $table->timestamps();
        });

        // Migrate data from anouncement to announcements
        if (Schema::hasTable('anouncement')) {
            $items = DB::table('anouncement')->get();
            foreach ($items as $item) {
                DB::table('announcements')->insert([
                    'id' => $item->id,
                    'link' => $item->link,
                    'major' => $item->major,
                    'is_seen' => $item->is_seen,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
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
        Schema::dropIfExists('announcements');
    }
};
