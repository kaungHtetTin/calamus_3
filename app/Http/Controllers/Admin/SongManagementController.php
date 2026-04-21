<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Models\RequestedSong;
use App\Models\Song;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SongManagementController extends Controller
{
    private function storeArtistImage(?UploadedFile $file): ?string
    {
        if (!$file) {
            return null;
        }

        $fileName = time() . '_' . uniqid() . '.' . strtolower($file->getClientOriginalExtension());
        $path = 'songs/artists';
        $storedPath = Storage::disk('uploads')->putFileAs($path, $file, $fileName);

        return env('APP_URL') . Storage::disk('uploads')->url($storedPath);
    }

    private function storeSongFile(?UploadedFile $file, string $major, string $folder, string $prefix): ?string
    {
        if (!$file) {
            return null;
        }

        $safeMajor = preg_replace('/[^a-z0-9_-]+/i', '-', strtolower(trim($major)));
        $extension = strtolower($file->getClientOriginalExtension());
        $fileName = $prefix . '_' . time() . '_' . uniqid() . ($extension ? ".{$extension}" : '');
        $path = "songs/{$safeMajor}/{$folder}";
        $storedPath = Storage::disk('uploads')->putFileAs($path, $file, $fileName);

        return env('APP_URL') . Storage::disk('uploads')->url($storedPath);
    }

    private function storeSongImagePair(?UploadedFile $file, string $major): array
    {
        if (!$file) {
            return [null, null];
        }

        $safeMajor = preg_replace('/[^a-z0-9_-]+/i', '-', strtolower(trim($major)));

        // Store original as thumbnail_url
        $origExt = strtolower($file->getClientOriginalExtension());
        $origExt = $origExt ?: 'jpg';
        $origName = 'thumb_' . time() . '_' . uniqid() . '.' . $origExt;
        $thumbPathDir = "songs/{$safeMajor}/thumbnails";
        $thumbStoredPath = Storage::disk('uploads')->putFileAs($thumbPathDir, $file, $origName);
        $thumbnailUrl = env('APP_URL') . Storage::disk('uploads')->url($thumbStoredPath);

        // Create light version for image_url using GD
        $lightName = 'image_' . time() . '_' . uniqid() . '.jpg';
        $imagePathDir = "songs/{$safeMajor}/images";
        $lightFullRelative = "{$imagePathDir}/{$lightName}";

        try {
            $contents = file_get_contents($file->getRealPath());
            $src = imagecreatefromstring($contents);
            if ($src !== false) {
                $srcW = imagesx($src);
                $srcH = imagesy($src);
                $targetW = $srcW;
                $targetH = $srcH;
                $maxW = 900;
                if ($srcW > $maxW) {
                    $ratio = $maxW / $srcW;
                    $targetW = (int) round($srcW * $ratio);
                    $targetH = (int) round($srcH * $ratio);
                }
                $dst = imagecreatetruecolor($targetW, $targetH);
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetW, $targetH, $srcW, $srcH);
                ob_start();
                imagejpeg($dst, null, 72);
                $jpegData = ob_get_clean();
                imagedestroy($dst);
                imagedestroy($src);
                if ($jpegData) {
                    Storage::disk('uploads')->put($lightFullRelative, $jpegData, 'public');
                } else {
                    // Fallback: store original also as image_url if encode fails
                    Storage::disk('uploads')->putFileAs($imagePathDir, $file, $lightName);
                }
            } else {
                // Non-image? fallback store as-is
                Storage::disk('uploads')->putFileAs($imagePathDir, $file, $lightName);
            }
        } catch (\Throwable $e) {
            // Fallback on any error
            Storage::disk('uploads')->putFileAs($imagePathDir, $file, $lightName);
        }

        $imageUrl = env('APP_URL') . Storage::disk('uploads')->url($lightFullRelative);

        return [$imageUrl, $thumbnailUrl];
    }

    private function getAdminMajorScope(Request $request)
    {
        $admin = $request->user('admin');
        $raw = collect((array) ($admin?->major_scope ?? []))
            ->map(function ($item) {
                return strtolower(trim((string) $item));
            })
            ->filter()
            ->unique()
            ->values();

        if ($raw->contains('*')) {
            return collect(['*']);
        }

        $languageValues = DB::table('languages')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['name', 'code'])
            ->map(function ($row) {
                $value = strtolower(trim((string) ($row->code ?: $row->name)));
                return $value !== '' ? $value : null;
            })
            ->filter()
            ->unique()
            ->values();

        return $raw
            ->filter(function ($value) use ($languageValues) {
                return $languageValues->contains($value);
            })
            ->values();
    }

    private function filterLanguagesByScope($languages, $scope)
    {
        if ($scope->contains('*')) {
            return $languages;
        }

        if ($scope->isEmpty()) {
            return collect();
        }

        $allowed = $scope->all();
        return $languages->filter(function ($row) use ($allowed) {
            $code = strtolower(trim((string) ($row->code ?: $row->name ?: '')));
            return $code !== '' && in_array($code, $allowed, true);
        })->values();
    }

    public function index(Request $request)
    {
        $languages = DB::table('languages')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get(['code', 'display_name', 'name', 'module_code', 'image_path', 'primary_color']);

        $scope = $this->getAdminMajorScope($request);
        $languages = $this->filterLanguagesByScope($languages, $scope);

        return Inertia::render('Admin/SongManagement', [
            'languages' => $languages,
        ]);
    }

    public function workspace(Request $request)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $tab = strtolower(trim((string) $request->query('tab', 'overview')));
        $allowedTabs = collect(['overview', 'artist', 'songs', 'requested-songs']);
        if (!$allowedTabs->contains($tab)) {
            $tab = 'overview';
        }

        $languages = DB::table('languages')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get(['code', 'display_name', 'name', 'module_code', 'image_path', 'primary_color']);

        $scope = $this->getAdminMajorScope($request);
        $languages = $this->filterLanguagesByScope($languages, $scope);

        if ($selectedMajor === '' && $languages->count() > 0) {
            $selectedMajor = (string) ($languages->first()->code ?? '');
        }

        if ($selectedMajor !== '') {
            $normalizedSelected = strtolower(trim($selectedMajor));
            if (!$scope->contains('*') && !$languages->contains(function ($row) use ($normalizedSelected) {
                $code = strtolower(trim((string) ($row->code ?: $row->name ?: '')));
                return $code === $normalizedSelected;
            })) {
                abort(403);
            }
        }

        $selectedLanguage = $languages->firstWhere('code', $selectedMajor);

        $overview = null;
        if ($selectedMajor !== '' && $tab === 'overview') {
            $hasLikeCount = Schema::hasColumn('songs', 'like_count');
            $hasDownloadCount = Schema::hasColumn('songs', 'download_count');

            $artistCount = (int) Artist::query()->where('major', $selectedMajor)->count();
            $songCount = (int) Song::query()->where('major', $selectedMajor)->count();
            $requestedCount = (int) RequestedSong::query()
                ->join('artists as a', 'a.id', '=', 'requestedsongs.artist_id')
                ->where('a.major', $selectedMajor)
                ->count();

            $topArtistsQuery = DB::table('songs as s')
                ->join('artists as a', 'a.id', '=', 's.artist_id')
                ->where('s.major', $selectedMajor)
                ->whereNotNull('s.artist_id')
                ->select(
                    's.artist_id',
                    'a.name as artist_name',
                    DB::raw('COUNT(s.id) as songs_count'),
                    DB::raw(($hasLikeCount ? 'COALESCE(SUM(s.like_count),0)' : '0') . ' as like_count'),
                    DB::raw(($hasDownloadCount ? 'COALESCE(SUM(s.download_count),0)' : '0') . ' as download_count')
                )
                ->groupBy('s.artist_id', 'a.name')
                ->orderByDesc('songs_count')
                ->orderByDesc('like_count')
                ->limit(8);

            $topArtists = $topArtistsQuery->get()->map(function ($row) {
                $likes = (int) ($row->like_count ?? 0);
                $downloads = (int) ($row->download_count ?? 0);
                return [
                    'artist_id' => (int) ($row->artist_id ?? 0),
                    'name' => (string) ($row->artist_name ?? ''),
                    'songs_count' => (int) ($row->songs_count ?? 0),
                    'like_count' => $likes,
                    'download_count' => $downloads,
                    'score' => $likes + $downloads,
                ];
            })->values();

            $topSongsQuery = DB::table('songs as s')
                ->leftJoin('artists as a', 'a.id', '=', 's.artist_id')
                ->where('s.major', $selectedMajor)
                ->select(
                    's.id',
                    's.title',
                    's.artist_id',
                    'a.name as artist_name',
                    's.audio_url',
                    's.image_url',
                    's.thumbnail_url',
                    DB::raw(($hasLikeCount ? 'COALESCE(s.like_count,0)' : '0') . ' as like_count'),
                    DB::raw(($hasDownloadCount ? 'COALESCE(s.download_count,0)' : '0') . ' as download_count')
                )
                ->orderByDesc('download_count')
                ->orderByDesc('like_count')
                ->orderByDesc('s.id')
                ->limit(8);

            $topSongs = $topSongsQuery->get()->map(function ($row) {
                $likes = (int) ($row->like_count ?? 0);
                $downloads = (int) ($row->download_count ?? 0);
                return [
                    'id' => (int) ($row->id ?? 0),
                    'title' => (string) ($row->title ?? ''),
                    'artist_id' => (int) ($row->artist_id ?? 0),
                    'artist_name' => (string) ($row->artist_name ?? ''),
                    'audio_url' => (string) ($row->audio_url ?? ''),
                    'image_url' => (string) ($row->image_url ?? ''),
                    'thumbnail_url' => (string) ($row->thumbnail_url ?? ''),
                    'like_count' => $likes,
                    'download_count' => $downloads,
                    'score' => $likes + $downloads,
                ];
            })->values();

            $songTitleIndex = [];
            $songRows = DB::table('songs')
                ->where('major', $selectedMajor)
                ->get(['id', 'title', 'artist_id']);

            $normalize = function ($text) {
                $t = strtolower(trim((string) $text));
                $t = preg_replace('/\s+/', ' ', $t);
                $t = preg_replace('/[^a-z0-9 ]+/', '', $t);
                return trim((string) $t);
            };

            foreach ($songRows as $row) {
                $key = $normalize($row->title ?? '');
                if ($key === '') {
                    continue;
                }
                if (!isset($songTitleIndex[$key])) {
                    $songTitleIndex[$key] = true;
                }
            }

            $topRequested = RequestedSong::query()
                ->join('artists as a', 'a.id', '=', 'requestedsongs.artist_id')
                ->where('a.major', $selectedMajor)
                ->select('requestedsongs.id', 'requestedsongs.artist_id', 'requestedsongs.name', 'requestedsongs.vote', 'a.name as artist_name')
                ->orderByDesc('requestedsongs.vote')
                ->orderByDesc('requestedsongs.id')
                ->limit(8)
                ->get()
                ->map(function ($row) use ($normalize, $songTitleIndex) {
                    $titleKey = $normalize($row->name ?? '');
                    $isUploaded = $titleKey !== '' && isset($songTitleIndex[$titleKey]);
                    return [
                        'id' => (int) ($row->id ?? 0),
                        'artist_id' => (int) ($row->artist_id ?? 0),
                        'artist_name' => (string) ($row->artist_name ?? ''),
                        'name' => (string) ($row->name ?? ''),
                        'vote' => (int) ($row->vote ?? 0),
                        'is_uploaded' => $isUploaded,
                    ];
                })->values();

            $overview = [
                'counts' => [
                    'artists' => $artistCount,
                    'songs' => $songCount,
                    'requested_songs' => $requestedCount,
                ],
                'top_artists' => $topArtists,
                'top_songs' => $topSongs,
                'top_requested' => $topRequested,
            ];
        }

        $artists = collect();
        if ($tab === 'artist' && $selectedMajor !== '') {
            $artists = Artist::query()
                ->where('major', $selectedMajor)
                ->withCount(['songs as song_count' => function ($q) use ($selectedMajor) {
                    $q->where('major', $selectedMajor);
                }])
                ->orderBy('name')
                ->get(['id', 'name', 'image_url', 'major']);
        }

        $artistOptions = collect();
        $songs = collect();
        $requestedSongs = collect();
        if ($selectedMajor !== '' && ($tab === 'songs' || $tab === 'requested-songs')) {
            $artistOptions = Artist::query()
                ->where('major', $selectedMajor)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        if ($selectedMajor !== '' && $tab === 'songs') {

            $songs = Song::query()
                ->where('major', $selectedMajor)
                ->with(['artistRef:id,name'])
                ->orderByDesc('id')
                ->get([
                    'id',
                    'title',
                    'artist_id',
                    'major',
                    'audio_url',
                    'image_url',
                    'thumbnail_url',
                    'lyric_url',
                    'asset_slug',
                ])
                ->map(function (Song $song) {
                    return [
                        'id' => (int) $song->id,
                        'title' => (string) ($song->title ?? ''),
                        'artist_id' => (int) ($song->artist_id ?? 0),
                        'artist_name' => (string) ($song->artistRef?->name ?? ''),
                        'audio_url' => (string) ($song->audio_url ?? ''),
                        'image_url' => (string) ($song->image_url ?? ''),
                        'thumbnail_url' => (string) ($song->thumbnail_url ?? ''),
                        'lyric_url' => (string) ($song->lyric_url ?? ''),
                        'asset_slug' => (string) ($song->asset_slug ?? ''),
                    ];
                });
        }

        if ($selectedMajor !== '' && $tab === 'requested-songs') {
            $uploadedByArtist = [];
            $uploadedTitles = [];
            $songRows = DB::table('songs')
                ->where('major', $selectedMajor)
                ->get(['id', 'title', 'artist_id']);

            $normalize = function ($text) {
                $t = strtolower(trim((string) $text));
                $t = preg_replace('/\s+/', ' ', $t);
                $t = preg_replace('/[^a-z0-9 ]+/', '', $t);
                return trim((string) $t);
            };

            foreach ($songRows as $row) {
                $titleKey = $normalize($row->title ?? '');
                if ($titleKey === '') {
                    continue;
                }
                $uploadedTitles[$titleKey] = true;
                $artistId = (int) ($row->artist_id ?? 0);
                if (!isset($uploadedByArtist[$artistId])) {
                    $uploadedByArtist[$artistId] = [];
                }
                $uploadedByArtist[$artistId][$titleKey] = true;
            }

            $requestedSongs = RequestedSong::query()
                ->join('artists as a', 'a.id', '=', 'requestedsongs.artist_id')
                ->where('a.major', $selectedMajor)
                ->select('requestedsongs.id', 'requestedsongs.artist_id', 'requestedsongs.name', 'requestedsongs.vote', 'a.name as artist_name')
                ->orderByDesc('requestedsongs.vote')
                ->orderByDesc('requestedsongs.id')
                ->get()
                ->map(function ($row) use ($normalize, $uploadedByArtist, $uploadedTitles) {
                    $artistId = (int) ($row->artist_id ?? 0);
                    $titleKey = $normalize($row->name ?? '');
                    $isUploaded = false;
                    if ($titleKey !== '') {
                        if (isset($uploadedByArtist[$artistId]) && isset($uploadedByArtist[$artistId][$titleKey])) {
                            $isUploaded = true;
                        } elseif (isset($uploadedTitles[$titleKey])) {
                            $isUploaded = true;
                        }
                    }
                    return [
                        'id' => (int) $row->id,
                        'artist_id' => $artistId,
                        'artist_name' => (string) ($row->artist_name ?? ''),
                        'name' => (string) ($row->name ?? ''),
                        'vote' => (int) ($row->vote ?? 0),
                        'is_uploaded' => $isUploaded,
                    ];
                });
        }

        return Inertia::render('Admin/SongManagementWorkspace', [
            'languages' => $languages,
            'selectedMajor' => $selectedMajor,
            'selectedLanguage' => $selectedLanguage,
            'tab' => $tab,
            'overview' => $overview,
            'artists' => $artists,
            'artistOptions' => $artistOptions,
            'songs' => $songs,
            'requestedSongs' => $requestedSongs,
        ]);
    }

    public function storeArtist(Request $request)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $scope = $this->getAdminMajorScope($request);
        if ($selectedMajor === '' || (!$scope->contains('*') && !$scope->contains(strtolower($selectedMajor)))) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:artists,name'],
            'image_file' => ['nullable', 'image', 'max:4096'],
        ]);

        $imageUrl = $this->storeArtistImage($request->file('image_file'));

        Artist::create([
            'name' => trim($data['name']),
            'image_url' => $imageUrl,
            'major' => $selectedMajor,
        ]);

        return redirect()->back()->with('success', 'Artist created successfully.');
    }

    public function updateArtist(Request $request, Artist $artist)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $scope = $this->getAdminMajorScope($request);
        if ($selectedMajor === '' || (!$scope->contains('*') && !$scope->contains(strtolower($selectedMajor)))) {
            abort(403);
        }

        $artistMajor = trim((string) ($artist->major ?? ''));
        if ($artistMajor !== '' && $artistMajor !== $selectedMajor) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('artists', 'name')->ignore($artist->id)],
            'image_file' => ['nullable', 'image', 'max:4096'],
        ]);

        $imageUrl = $artist->image_url;
        $newImageUrl = $this->storeArtistImage($request->file('image_file'));
        if ($newImageUrl) {
            $imageUrl = $newImageUrl;
        }

        $artist->update([
            'name' => trim($data['name']),
            'image_url' => $imageUrl,
            'major' => $selectedMajor,
        ]);

        return redirect()->back()->with('success', 'Artist updated successfully.');
    }

    public function destroyArtist(Request $request, Artist $artist)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $scope = $this->getAdminMajorScope($request);
        if ($selectedMajor === '' || (!$scope->contains('*') && !$scope->contains(strtolower($selectedMajor)))) {
            abort(403);
        }

        $artistMajor = trim((string) ($artist->major ?? ''));
        if ($artistMajor !== '' && $artistMajor !== $selectedMajor) {
            abort(403);
        }

        $songExists = DB::table('songs')->where('artist_id', $artist->id)->exists();
        if ($songExists) {
            return redirect()->back()->withErrors(['artist' => 'Cannot delete artist because songs exist.']);
        }

        $artist->delete();

        return redirect()->back()->with('success', 'Artist deleted successfully.');
    }

    public function storeSong(Request $request)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $scope = $this->getAdminMajorScope($request);
        if ($selectedMajor === '' || (!$scope->contains('*') && !$scope->contains(strtolower($selectedMajor)))) {
            abort(403);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'artist_id' => ['required', 'integer'],
            'audio_file' => ['nullable', 'file', 'mimetypes:audio/mpeg,audio/mp3', 'max:51200'],
            'cover_file' => ['nullable', 'image', 'max:4096'],
            'lyric_file' => ['nullable', 'file', 'mimes:txt', 'max:10240'],
        ]);

        $artistId = (int) $data['artist_id'];
        $artistExists = Artist::query()->where('id', $artistId)->where('major', $selectedMajor)->exists();
        if (!$artistExists) {
            return redirect()->back()->withErrors(['artist_id' => 'Invalid artist for this channel.']);
        }

        $audioUrl = $this->storeSongFile($request->file('audio_file'), $selectedMajor, 'audio', 'audio');
        [$imageUrl, $thumbnailUrl] = $this->storeSongImagePair($request->file('cover_file'), $selectedMajor);
        $lyricUrl = $this->storeSongFile($request->file('lyric_file'), $selectedMajor, 'lyrics', 'lyric');

        Song::create([
            'title' => trim($data['title']),
            'artist_id' => $artistId,
            'major' => $selectedMajor,
            'audio_url' => $audioUrl,
            'image_url' => $imageUrl,
            'thumbnail_url' => $thumbnailUrl,
            'lyric_url' => $lyricUrl,
        ]);

        return redirect()->back()->with('success', 'Song created successfully.');
    }

    public function updateSong(Request $request, Song $song)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $scope = $this->getAdminMajorScope($request);
        if ($selectedMajor === '' || (!$scope->contains('*') && !$scope->contains(strtolower($selectedMajor)))) {
            abort(403);
        }

        $songMajor = trim((string) ($song->major ?? ''));
        if ($songMajor !== '' && $songMajor !== $selectedMajor) {
            abort(403);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'artist_id' => ['required', 'integer'],
            'audio_file' => ['nullable', 'file', 'mimetypes:audio/mpeg,audio/mp3', 'max:51200'],
            'cover_file' => ['nullable', 'image', 'max:4096'],
            'lyric_file' => ['nullable', 'file', 'mimes:txt', 'max:10240'],
        ]);

        $artistId = (int) $data['artist_id'];
        $artistExists = Artist::query()->where('id', $artistId)->where('major', $selectedMajor)->exists();
        if (!$artistExists) {
            return redirect()->back()->withErrors(['artist_id' => 'Invalid artist for this channel.']);
        }

        $audioUrl = $song->audio_url;
        $imageUrl = $song->image_url;
        $thumbnailUrl = $song->thumbnail_url;
        $lyricUrl = $song->lyric_url;

        $newAudioUrl = $this->storeSongFile($request->file('audio_file'), $selectedMajor, 'audio', 'audio');
        [$newImageUrl, $newThumbnailUrl] = $this->storeSongImagePair($request->file('cover_file'), $selectedMajor);
        $newLyricUrl = $this->storeSongFile($request->file('lyric_file'), $selectedMajor, 'lyrics', 'lyric');

        if ($newAudioUrl) $audioUrl = $newAudioUrl;
        if ($newImageUrl) $imageUrl = $newImageUrl;
        if ($newThumbnailUrl) $thumbnailUrl = $newThumbnailUrl;
        if ($newLyricUrl) $lyricUrl = $newLyricUrl;

        $song->update([
            'title' => trim($data['title']),
            'artist_id' => $artistId,
            'major' => $selectedMajor,
            'audio_url' => $audioUrl,
            'image_url' => $imageUrl,
            'thumbnail_url' => $thumbnailUrl,
            'lyric_url' => $lyricUrl,
        ]);

        return redirect()->back()->with('success', 'Song updated successfully.');
    }

    public function destroySong(Request $request, Song $song)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $scope = $this->getAdminMajorScope($request);
        if ($selectedMajor === '' || (!$scope->contains('*') && !$scope->contains(strtolower($selectedMajor)))) {
            abort(403);
        }

        $songMajor = trim((string) ($song->major ?? ''));
        if ($songMajor !== '' && $songMajor !== $selectedMajor) {
            abort(403);
        }

        $song->delete();

        return redirect()->back()->with('success', 'Song deleted successfully.');
    }

    private function getUploadsRelativePathFromUrl(?string $url): ?string
    {
        $raw = trim((string) ($url ?? ''));
        if ($raw === '') {
            return null;
        }

        $path = parse_url($raw, PHP_URL_PATH);
        $path = is_string($path) ? $path : '';
        if ($path === '') {
            return null;
        }

        $needle = '/uploads/';
        $pos = strpos($path, $needle);
        if ($pos === false) {
            return null;
        }

        $relative = substr($path, $pos + strlen($needle));
        $relative = ltrim($relative, '/');
        if ($relative === '' || str_contains($relative, '..')) {
            return null;
        }

        return $relative;
    }

    public function editSongLyric(Request $request, Song $song)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $scope = $this->getAdminMajorScope($request);
        if ($selectedMajor === '' || (!$scope->contains('*') && !$scope->contains(strtolower($selectedMajor)))) {
            abort(403);
        }

        $songMajor = trim((string) ($song->major ?? ''));
        if ($songMajor !== '' && $songMajor !== $selectedMajor) {
            abort(403);
        }

        $lyricText = '';
        $relative = $this->getUploadsRelativePathFromUrl($song->lyric_url);
        if ($relative && Storage::disk('uploads')->exists($relative)) {
            $lyricText = (string) Storage::disk('uploads')->get($relative);
        }

        return Inertia::render('Admin/SongLyricEdit', [
            'major' => $selectedMajor,
            'song' => [
                'id' => (int) $song->id,
                'title' => (string) ($song->title ?? ''),
                'artist_id' => (int) ($song->artist_id ?? 0),
                'lyric_url' => (string) ($song->lyric_url ?? ''),
            ],
            'lyricText' => $lyricText,
        ]);
    }

    public function updateSongLyric(Request $request, Song $song)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $scope = $this->getAdminMajorScope($request);
        if ($selectedMajor === '' || (!$scope->contains('*') && !$scope->contains(strtolower($selectedMajor)))) {
            abort(403);
        }

        $songMajor = trim((string) ($song->major ?? ''));
        if ($songMajor !== '' && $songMajor !== $selectedMajor) {
            abort(403);
        }

        $data = $request->validate([
            'lyric_text' => ['nullable', 'string'],
        ]);

        $text = (string) ($data['lyric_text'] ?? '');

        $relative = $this->getUploadsRelativePathFromUrl($song->lyric_url);
        if (!$relative) {
            $safeMajor = preg_replace('/[^a-z0-9_-]+/i', '-', strtolower(trim($selectedMajor)));
            $fileName = 'lyric_' . (int) $song->id . '_' . time() . '_' . uniqid() . '.txt';
            $relative = "songs/{$safeMajor}/lyrics/{$fileName}";
            Storage::disk('uploads')->put($relative, $text, 'public');
            $song->update([
                'lyric_url' => env('APP_URL') . Storage::disk('uploads')->url($relative),
            ]);
        } else {
            Storage::disk('uploads')->put($relative, $text, 'public');
        }

        return redirect()->to("/admin/songs/workspace?major=" . urlencode($selectedMajor) . "&tab=songs")->with('success', 'Lyrics updated successfully.');
    }

    public function destroyRequestedSong(Request $request, RequestedSong $requestedSong)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $scope = $this->getAdminMajorScope($request);
        if ($selectedMajor === '' || (!$scope->contains('*') && !$scope->contains(strtolower($selectedMajor)))) {
            abort(403);
        }

        $artistMajor = DB::table('artists')->where('id', $requestedSong->artist_id)->value('major');
        $artistMajor = trim((string) ($artistMajor ?? ''));
        if ($artistMajor !== '' && $artistMajor !== $selectedMajor) {
            abort(403);
        }

        $requestedSong->delete();

        return redirect()->back()->with('success', 'Requested song deleted successfully.');
    }
}
