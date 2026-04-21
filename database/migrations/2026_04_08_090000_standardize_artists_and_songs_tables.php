<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('songs') || !Schema::hasTable('artists')) {
            return;
        }

        Schema::table('artists', function (Blueprint $table) {
            if (!Schema::hasColumn('artists', 'major')) {
                $table->string('major', 50)->nullable()->index();
            }
        });

        if (Schema::hasColumn('artists', 'nation')) {
            DB::statement("UPDATE artists SET major = NULLIF(TRIM(nation), '') WHERE (major IS NULL OR major = '') AND nation IS NOT NULL AND nation != ''");
        }

        if (!Schema::hasColumn('songs', 'artist_id')) {
            Schema::table('songs', function (Blueprint $table) {
                $table->unsignedBigInteger('artist_id')->nullable()->after('id');
            });
        }

        Schema::table('songs', function (Blueprint $table) {
            if (!Schema::hasColumn('songs', 'major')) {
                $table->string('major', 50)->nullable()->index()->after('artist_id');
            }
            if (!Schema::hasColumn('songs', 'asset_slug')) {
                $table->string('asset_slug')->nullable()->index()->after('title');
            }
            if (!Schema::hasColumn('songs', 'legacy_song_id')) {
                $table->unsignedBigInteger('legacy_song_id')->nullable()->index()->after('id');
            }
            if (!Schema::hasColumn('songs', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (!Schema::hasColumn('songs', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });

        if (Schema::hasColumn('songs', 'type')) {
            DB::statement("UPDATE songs SET major = NULLIF(TRIM(type), '') WHERE (major IS NULL OR major = '') AND type IS NOT NULL AND type != ''");
        }

        if (Schema::hasColumn('songs', 'url')) {
            DB::statement("UPDATE songs SET asset_slug = NULLIF(TRIM(url), '') WHERE (asset_slug IS NULL OR asset_slug = '') AND url IS NOT NULL AND url != ''");
        }

        if (Schema::hasColumn('songs', 'song_id')) {
            DB::statement("UPDATE songs SET legacy_song_id = song_id WHERE legacy_song_id IS NULL AND song_id IS NOT NULL");
        }

        DB::statement("UPDATE songs SET created_at = COALESCE(created_at, NOW()), updated_at = COALESCE(updated_at, NOW())");

        if (Schema::hasColumn('songs', 'artist')) {
            $songArtists = DB::table('songs')
                ->select(
                    'artist',
                    DB::raw('MIN(asset_slug) as asset_slug'),
                    DB::raw('MIN(major) as major')
                )
                ->whereNotNull('artist')
                ->where('artist', '!=', '')
                ->groupBy('artist')
                ->get();

            foreach ($songArtists as $row) {
                $name = trim((string) $row->artist);
                if ($name === '') {
                    continue;
                }

                $major = trim((string) ($row->major ?? ''));
                $major = $major !== '' ? $major : null;
                $imageSlug = trim((string) ($row->asset_slug ?? ''));
                $imageSlug = $imageSlug !== '' ? $imageSlug : null;

                $artist = DB::table('artists')->where('name', $name)->first();

                if ($artist) {
                    $updates = [];
                    if (($artist->major ?? null) === null && $major !== null) {
                        $updates['major'] = $major;
                    }
                    if (($artist->image_slug ?? null) === null && $imageSlug !== null) {
                        $updates['image_slug'] = $imageSlug;
                    }
                    if (!empty($updates)) {
                        $updates['updated_at'] = now();
                        DB::table('artists')->where('id', $artist->id)->update($updates);
                    }
                    $artistId = $artist->id;
                } else {
                    $artistId = DB::table('artists')->insertGetId([
                        'name' => $name,
                        'major' => $major,
                        'image_slug' => $imageSlug,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('songs')
                    ->where('artist', $name)
                    ->whereNull('artist_id')
                    ->update(['artist_id' => $artistId]);
            }
        }

        $unknownArtistId = null;
        $missingArtistIds = DB::table('songs')->whereNull('artist_id')->limit(1)->exists();
        if ($missingArtistIds) {
            $unknown = DB::table('artists')->where('name', 'Unknown Artist')->first();
            if ($unknown) {
                $unknownArtistId = $unknown->id;
            } else {
                $unknownArtistId = DB::table('artists')->insertGetId([
                    'name' => 'Unknown Artist',
                    'major' => null,
                    'image_slug' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('songs')
                ->whereNull('artist_id')
                ->update(['artist_id' => $unknownArtistId]);
        }

        if (Schema::hasColumn('artists', 'major') && Schema::hasColumn('songs', 'major')) {
            DB::statement("
                UPDATE artists a
                JOIN (
                    SELECT artist_id, MIN(major) AS major
                    FROM songs
                    WHERE artist_id IS NOT NULL AND major IS NOT NULL AND major != ''
                    GROUP BY artist_id
                ) s ON s.artist_id = a.id
                SET a.major = s.major
                WHERE (a.major IS NULL OR a.major = '')
            ");
        }

        if (Schema::hasColumn('artists', 'nation')) {
            Schema::table('artists', function (Blueprint $table) {
                $table->dropColumn('nation');
            });
        }

        if (Schema::hasColumn('songs', 'song_id') || Schema::hasColumn('songs', 'artist') || Schema::hasColumn('songs', 'url') || Schema::hasColumn('songs', 'type')) {
            Schema::table('songs', function (Blueprint $table) {
                if (Schema::hasColumn('songs', 'song_id')) {
                    $table->dropColumn('song_id');
                }
                if (Schema::hasColumn('songs', 'artist')) {
                    $table->dropColumn('artist');
                }
                if (Schema::hasColumn('songs', 'url')) {
                    $table->dropColumn('url');
                }
                if (Schema::hasColumn('songs', 'type')) {
                    $table->dropColumn('type');
                }
            });
        }

        $songsStatus = DB::select("SHOW TABLE STATUS LIKE 'songs'");
        if (!empty($songsStatus) && isset($songsStatus[0]->Engine) && strtolower((string) $songsStatus[0]->Engine) !== 'innodb') {
            DB::statement('ALTER TABLE songs ENGINE=InnoDB');
        }

        $artistsStatus = DB::select("SHOW TABLE STATUS LIKE 'artists'");
        if (!empty($artistsStatus) && isset($artistsStatus[0]->Engine) && strtolower((string) $artistsStatus[0]->Engine) !== 'innodb') {
            DB::statement('ALTER TABLE artists ENGINE=InnoDB');
        }

        $artistsPkOk = DB::table('information_schema.STATISTICS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'artists')
            ->where('INDEX_NAME', 'PRIMARY')
            ->where('COLUMN_NAME', 'id')
            ->exists();
        if (!$artistsPkOk) {
            $artistsIdExists = Schema::hasColumn('artists', 'id');
            if ($artistsIdExists) {
                DB::statement('ALTER TABLE artists ADD PRIMARY KEY (id)');
            }
        }

        if (Schema::hasColumn('artists', 'id')) {
            $artistsIdCol = DB::select("SHOW COLUMNS FROM artists LIKE 'id'");
            if (!empty($artistsIdCol) && isset($artistsIdCol[0]->Type)) {
                $type = strtolower((string) $artistsIdCol[0]->Type);
                $needsBigint = !str_contains($type, 'bigint');
                $needsUnsigned = !str_contains($type, 'unsigned');
                if ($needsBigint || $needsUnsigned) {
                    $hasIncomingFk = DB::table('information_schema.KEY_COLUMN_USAGE')
                        ->whereRaw('TABLE_SCHEMA = DATABASE()')
                        ->where('REFERENCED_TABLE_NAME', 'artists')
                        ->where('REFERENCED_COLUMN_NAME', 'id')
                        ->exists();

                    if (!$hasIncomingFk) {
                        try {
                            DB::statement('ALTER TABLE artists MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
                        } catch (\Exception $e) {
                        }
                    }
                }
            }
        }

        if (Schema::hasColumn('songs', 'artist_id')) {
            $artistIdCol = DB::select("SHOW COLUMNS FROM songs LIKE 'artist_id'");
            if (!empty($artistIdCol) && isset($artistIdCol[0]->Type)) {
                $type = strtolower((string) $artistIdCol[0]->Type);
                $needsBigint = !str_contains($type, 'bigint');
                $needsUnsigned = !str_contains($type, 'unsigned');
                if ($needsBigint || $needsUnsigned) {
                    DB::statement('ALTER TABLE songs MODIFY artist_id BIGINT UNSIGNED NULL');
                }
            }

            $artistIdIndexExists = DB::table('information_schema.STATISTICS')
                ->whereRaw('TABLE_SCHEMA = DATABASE()')
                ->where('TABLE_NAME', 'songs')
                ->where('COLUMN_NAME', 'artist_id')
                ->where('INDEX_NAME', '!=', 'PRIMARY')
                ->exists();
            if (!$artistIdIndexExists) {
                DB::statement('ALTER TABLE songs ADD INDEX songs_artist_id_index (artist_id)');
            }
        }

        $hasInvalidArtistIds = DB::table('songs as s')
            ->leftJoin('artists as a', 'a.id', '=', 's.artist_id')
            ->whereNotNull('s.artist_id')
            ->whereNull('a.id')
            ->limit(1)
            ->exists();
        if ($hasInvalidArtistIds) {
            $unknown = DB::table('artists')->where('name', 'Unknown Artist')->first();
            $unknownArtistId = $unknown?->id;
            if (!$unknownArtistId) {
                $unknownArtistId = DB::table('artists')->insertGetId([
                    'name' => 'Unknown Artist',
                    'major' => null,
                    'image_slug' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::table('songs as s')
                ->leftJoin('artists as a', 'a.id', '=', 's.artist_id')
                ->whereNotNull('s.artist_id')
                ->whereNull('a.id')
                ->update(['s.artist_id' => $unknownArtistId]);
        }

        $hasFk = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'songs')
            ->where('COLUMN_NAME', 'artist_id')
            ->where('REFERENCED_TABLE_NAME', 'artists')
            ->exists();

        if (!$hasFk) {
            try {
                Schema::table('songs', function (Blueprint $table) {
                    $table->foreign('artist_id', 'songs_artist_id_fk')->references('id')->on('artists')->onDelete('restrict');
                });
            } catch (\Exception $e) {
            }
        }
    }

    public function down()
    {
    }
};
