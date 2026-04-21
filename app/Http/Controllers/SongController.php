<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Song;
use App\Models\SongLike;
use App\Models\Artist;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;

class SongController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            $category = $request->query('category', 'english');
            $category = preg_replace('/[^a-z0-9_]/', '', trim($category)) ?: 'english';
            
            $page = max(1, (int)$request->query('page', 1));
            
            // Get authenticated user
            $user = auth('sanctum')->user();
            $userId = $user ? $user->user_id : null;
            
            $limit = 20;
            $offset = ($page - 1) * $limit;

            // Popular and All Songs
            $popularSongs = Song::where('major', $category)->with('artistRef:id,name')->orderBy('like_count', 'desc')->limit(20)->get();
            $allSongsQuery = Song::where('major', $category)->with('artistRef:id,name')->orderBy('id', 'desc');
            $totalSongs = $allSongsQuery->count();
            $songsPage = $allSongsQuery->offset($offset)->limit($limit)->get();

            // Artists
            $artists = \App\Models\Artist::whereHas('songs', function ($query) use ($category) {
                $query->where('major', $category);
            })->orderBy('name')->limit(50)->get();
            
            // Check Likes efficiently
            $likedSongIds = [];
            if ($userId) {
                $allIds = $popularSongs->pluck('id')->merge($songsPage->pluck('id'))->unique();
                $likedSongIds = SongLike::where('user_id', $userId)
                    ->whereIn('song_id', $allIds)
                    ->pluck('song_id')
                    ->flip()
                    ->toArray();
            }

            $baseUrl = 'https://www.calamuseducation.com/uploads/songs';
            
            $formatSong = function ($s) use ($baseUrl, $likedSongIds, $userId) {
                $u = $s->asset_slug ?? '';
                $row = [
                    'id' => (int)$s->id,
                    'songId' => (int)$s->id,
                    'artistId' => (int)($s->artist_id ?? 0),
                    'title' => $this->ensureUtf8($s->title),
                    'artist' => $this->ensureUtf8($s->artistRef?->name ?? ''),
                    'url' => $u,
                    'likeCount' => (int)$s->like_count,
                    'downloadCount' => (int)$s->download_count,
                    'audioUrl' => $s->audio_url ?: "$baseUrl/audio/{$u}.mp3",
                    'imageUrl' => $s->image_url ?: "$baseUrl/web/{$u}.png",
                    'thumbnailUrl' => $s->thumbnail_url ?: "$baseUrl/image/{$u}.png",
                    'lyricsUrl' => $s->lyric_url ?: "$baseUrl/lyrics/{$u}.txt",
                ];
                if ($userId) {
                    $row['liked'] = isset($likedSongIds[$s->id]);
                }
                return $row;
            };

            return $this->successResponse([
                'popularSongs' => $popularSongs->map($formatSong),
                'songs' => $songsPage->map($formatSong),
                'artists' => $artists->map(fn($a) => [
                    'id' => (int) $a->id,
                    'name' => $this->ensureUtf8($a->name),
                    'imageUrl' => $a->image_url ?: ($baseUrl . "/web/" . ($a->image_slug ?? '') . ".png"),
                ]),
            ], 200, array_merge($this->paginate($totalSongs, $page, $limit), ['category' => $category]));

        } catch (\Exception $e) {
            return $this->errorResponse('Server error: ' . $e->getMessage(), 500);
        }
    }

    public function toggleLike(Request $request)
    {
       
        try {
            $user = $request->user();
            if (!$user) {
                return $this->errorResponse('Not authenticated', 401);
            }
            $userId = $user->user_id;
            $songId = (int)$request->input('songId');
      
            $song = Song::find($songId);
            if (!$song) {
                return $this->errorResponse('Song not found', 404);
            }

            $like = SongLike::where('user_id', $userId)
                ->where('song_id', $songId)
                ->first();

            if ($like) {
                // Unlike
                $like->delete();
                $song->decrement('like_count');
                $isLiked = false;
            } else {
                // Like
                SongLike::create([
                    'user_id' => $userId,
                    'song_id' => $songId,
                ]);
                $song->increment('like_count');
                $isLiked = true;
            }

            return $this->successResponse([
                'success' => true,
                'isLiked' => $isLiked,
                'likeCount' => (int)$song->like_count,
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Server error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Search songs by title and/or artist name.
     */
    public function search(Request $request)
    {
        try {
            $q = trim((string)$request->query('q', ''));
            $title = trim((string)$request->query('title', ''));
            $artist = trim((string)$request->query('artist', ''));
            $category = preg_replace('/[^a-z0-9_]/', '', trim((string)$request->query('category', ''))) ?: 'english';
            $page = max(1, (int)$request->query('page', 1));
            $limit = min(50, max(5, (int)$request->query('limit', 20)));

            if ($q === '' && $title === '' && $artist === '') {
                return $this->errorResponse('Query (q), title, or artist is required', 400);
            }

            $query = Song::where('major', $category)->with('artistRef:id,name');

            if ($q !== '') {
                $term = '%' . $q . '%';
                $query->where(function ($qry) use ($term) {
                    $qry->where('title', 'like', $term)
                        ->orWhereHas('artistRef', fn ($rel) => $rel->where('name', 'like', $term));
                });
            } else {
                if ($title !== '') {
                    $query->where('title', 'like', '%' . $title . '%');
                }
                if ($artist !== '') {
                    $query->where(function ($qry) use ($artist) {
                        $term = '%' . $artist . '%';
                        $qry->whereHas('artistRef', fn ($rel) => $rel->where('name', 'like', $term));
                    });
                }
            }

            $total = $query->count();
            $songs = $query->orderBy('like_count', 'desc')->offset(($page - 1) * $limit)->limit($limit)->get();

            $user = auth('sanctum')->user();
            $userId = $user ? $user->user_id : null;
            $likedSongIds = [];
            if ($userId && $songs->isNotEmpty()) {
                $likedSongIds = SongLike::where('user_id', $userId)
                    ->whereIn('song_id', $songs->pluck('id'))
                    ->pluck('song_id')->flip()->toArray();
            }

            $baseUrl = 'https://www.calamuseducation.com/uploads/songs';
            $formatted = $songs->map(function ($s) use ($baseUrl, $likedSongIds, $userId) {
                $u = $s->asset_slug ?? '';
                $row = [
                    'id' => (int)$s->id,
                    'songId' => (int)$s->id,
                    'artistId' => (int)($s->artist_id ?? 0),
                    'title' => $this->ensureUtf8($s->title),
                    'artist' => $this->ensureUtf8($s->artistRef?->name ?? ''),
                    'url' => $u,
                    'likeCount' => (int)$s->like_count,
                    'downloadCount' => (int)$s->download_count,
                    'audioUrl' => $s->audio_url ?: "$baseUrl/audio/{$u}.mp3",
                    'imageUrl' => $s->image_url ?: "$baseUrl/web/{$u}.png",
                    'thumbnailUrl' => $s->thumbnail_url ?: "$baseUrl/image/{$u}.png",
                    'lyricsUrl' => $s->lyric_url ?: "$baseUrl/lyrics/{$u}.txt",
                ];
                if ($userId) {
                    $row['liked'] = isset($likedSongIds[$s->id]);
                }
                return $row;
            });

            return $this->successResponse($formatted, 200, $this->paginate($total, $page, $limit));
        } catch (\Exception $e) {
            return $this->errorResponse('Server error', 500);
        }
    }

    /**
     * Get paginated artist list filtered by song category.
     */
    public function artists(Request $request)
    {
        try {
            $category = $request->query('category', 'english');
            $category = preg_replace('/[^a-z0-9_]/', '', trim($category)) ?: 'english';
            $page = max(1, (int)$request->query('page', 1));
            $limit = min(50, max(5, (int)$request->query('limit', 20)));

            $query = Artist::whereHas('songs', function ($q) use ($category) {
                $q->where('major', $category);
            })->withCount(['songs as song_count' => function ($q) use ($category) {
                $q->where('major', $category);
            }])->orderBy('name');

            $total = $query->count();
            $artists = $query->offset(($page - 1) * $limit)->limit($limit)->get();

            $baseUrl = 'https://www.calamuseducation.com/uploads/songs';

            $formatted = $artists->map(fn ($a) => [
                'id' => (int)$a->id,
                'name' => $this->ensureUtf8($a->name),
                'imageUrl' => $a->image_url ?: ($baseUrl . '/web/' . ($a->image_slug ?? '') . '.png'),
                'imageSlug' => $a->image_slug ?? null,
                'songCount' => (int)($a->song_count ?? 0),
            ]);

            return $this->successResponse($formatted, 200, $this->paginate($total, $page, $limit));
        } catch (\Exception $e) {
            return $this->errorResponse('Server error', 500);
        }
    }

    public function incrementDownloadCount(Request $request)
    {
        try {
            $songId = (int)$request->input('songId');

            $song = Song::find($songId);
            if (!$song) {
                return $this->errorResponse('Song not found', 404);
            }

            $song->increment('download_count');

            return $this->successResponse([
                'success' => true,
                'downloadCount' => (int)$song->download_count,
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Server error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get paginated songs for a specific artist.
     */
    public function getByArtist(Request $request)
    {
        try {
            $artistId = (int)$request->query('artistId');
            if ($artistId <= 0) {
                return $this->errorResponse('artistId is required', 400);
            }

            $category = preg_replace('/[^a-z0-9_]/', '', trim((string)$request->query('category', ''))) ?: 'english';
            $page = max(1, (int)$request->query('page', 1));
            $limit = min(50, max(5, (int)$request->query('limit', 20)));

            $artist = Artist::find($artistId);
            if (!$artist) {
                return $this->errorResponse('Artist not found', 404);
            }

            $query = Song::where('artist_id', $artistId)->where('major', $category)->with('artistRef:id,name');

            $total = $query->count();
            $songs = $query->orderBy('like_count', 'desc')->offset(($page - 1) * $limit)->limit($limit)->get();

            $user = auth('sanctum')->user();
            $userId = $user ? $user->user_id : null;
            $likedSongIds = [];
            if ($userId && $songs->isNotEmpty()) {
                $likedSongIds = SongLike::where('user_id', $userId)
                    ->whereIn('song_id', $songs->pluck('id'))
                    ->pluck('song_id')->flip()->toArray();
            }

            $baseUrl = 'https://www.calamuseducation.com/uploads/songs';
            $formatted = $songs->map(function ($s) use ($baseUrl, $likedSongIds, $userId) {
                $u = $s->asset_slug ?? '';
                $row = [
                    'id' => (int)$s->id,
                    'songId' => (int)$s->id,
                    'artistId' => (int)($s->artist_id ?? 0),
                    'title' => $this->ensureUtf8($s->title),
                    'artist' => $this->ensureUtf8($s->artistRef?->name ?? ''),
                    'url' => $u,
                    'likeCount' => (int)$s->like_count,
                    'downloadCount' => (int)$s->download_count,
                    'audioUrl' => $s->audio_url ?: "$baseUrl/audio/{$u}.mp3",
                    'imageUrl' => $s->image_url ?: "$baseUrl/web/{$u}.png",
                    'thumbnailUrl' => $s->thumbnail_url ?: "$baseUrl/image/{$u}.png",
                    'lyricsUrl' => $s->lyric_url ?: "$baseUrl/lyrics/{$u}.txt",
                ];
                if ($userId) {
                    $row['liked'] = isset($likedSongIds[$s->id]);
                }
                return $row;
            });

            return $this->successResponse([
                'artist' => [
                    'id' => (int)$artist->id,
                    'name' => $this->ensureUtf8($artist->name),
                    'imageUrl' => $artist->image_url ?: ($baseUrl . '/web/' . ($artist->image_slug ?? '') . '.png'),
                ],
                'songs' => $formatted
            ], 200, $this->paginate($total, $page, $limit));

        } catch (\Exception $e) {
            return $this->errorResponse('Server error', 500);
        }
    }

    private function ensureUtf8($string)
    {
        return mb_convert_encoding($string ?? '', 'UTF-8', 'UTF-8');
    }
}
