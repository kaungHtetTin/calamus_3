<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Facades\DB;
use App\Models\Song;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('mylikes')->orderBy('id')->chunk(100, function ($mylikes) {
            foreach ($mylikes as $mylike) {
                $likes = json_decode($mylike->likes, true);
                if (!is_array($likes)) continue;

                // Find song by song_id (legacy identifier)
                $song = Song::where('song_id', $mylike->content_id)->first();
                if (!$song) continue;

                foreach ($likes as $like) {
                    if (!isset($like['user_id'])) continue;

                    // Sanitize user_id (strip hyphens) if it's a string
                    $userId = $like['user_id'];
                    if (is_string($userId)) {
                        $userId = (int) str_replace('-', '', $userId);
                    }

                    // Use updateOrInsert to avoid unique constraint violations
                    DB::table('song_likes')->updateOrInsert(
                        ['user_id' => $userId, 'song_id' => $song->id],
                        ['created_at' => now()]
                    );
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Data migration down is usually nothing
    }
};
