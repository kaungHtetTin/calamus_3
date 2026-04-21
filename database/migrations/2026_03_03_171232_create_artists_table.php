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
        // 1. Ensure artists table has the necessary columns
        if (!Schema::hasTable('artists')) {
            Schema::create('artists', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('image_slug')->nullable();
                $table->string('nation')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('artists', function (Blueprint $table) {
                if (!Schema::hasColumn('artists', 'image_slug')) {
                    $table->string('image_slug')->nullable()->after('name');
                }
                if (!Schema::hasColumn('artists', 'created_at')) {
                    $table->timestamps();
                }
                // Also ensure nation is nullable if it already exists
                $table->string('nation')->nullable()->change();
            });
        }

        // 2. Add artist_id to songs table if it doesn't exist
        if (Schema::hasTable('songs')) {
            Schema::table('songs', function (Blueprint $table) {
                if (!Schema::hasColumn('songs', 'artist_id')) {
                    $table->unsignedBigInteger('artist_id')->nullable()->after('id');
                }
            });

            // 3. Migrate/Sync artists
            $songsArtists = DB::table('songs')
                ->select('artist', DB::raw('MIN(url) as url'))
                ->whereNotNull('artist')
                ->where('artist', '!=', '')
                ->groupBy('artist')
                ->get();

            foreach ($songsArtists as $songArtist) {
                // Check if artist exists by name
                $artist = DB::table('artists')->where('name', $songArtist->artist)->first();
                
                if ($artist) {
                    $artistId = $artist->id;
                    // Update image_slug if it's empty
                    if (empty($artist->image_slug)) {
                        DB::table('artists')->where('id', $artistId)->update([
                            'image_slug' => $songArtist->url,
                            'updated_at' => now(),
                        ]);
                    }
                } else {
                    // Create new artist
                    $artistId = DB::table('artists')->insertGetId([
                        'name' => $songArtist->artist,
                        'image_slug' => $songArtist->url,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // 4. Update songs with the artist_id
                DB::table('songs')
                    ->where('artist', $songArtist->artist)
                    ->update(['artist_id' => $artistId]);
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
        if (Schema::hasTable('songs') && Schema::hasColumn('songs', 'artist_id')) {
            Schema::table('songs', function (Blueprint $table) {
                $table->dropColumn('artist_id');
            });
        }
        Schema::dropIfExists('artists');
    }
};
