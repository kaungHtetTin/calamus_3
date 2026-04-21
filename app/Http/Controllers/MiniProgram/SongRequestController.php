<?php

namespace App\Http\Controllers\MiniProgram;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Models\RequestedSong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SongRequestController extends Controller
{
    /**
     * Show the song request mini program
     */
    public function index(Request $request)
    {
        $major = $request->query('major', 'korea');
        $userId = $request->query('userId');

        // Fetch artists for this major, but no need to load requested songs here
        $artists = Artist::where('major', $major)->orderBy('name', 'asc')->get();

        return view('mini-program.song-request.index', [
            'artists' => $artists,
            'major' => $major,
            'userId' => $userId,
        ]);
    }

    /**
     * Show a specific artist's song requests
     */
    public function showArtist(Request $request, $id)
    {
        $major = $request->query('major', 'korea');
        $userId = $request->query('userId');

        $artist = Artist::with(['requestedSongs' => function($query) {
            $query->orderBy('vote', 'desc');
        }])->findOrFail($id);

        return view('mini-program.song-request.artist', [
            'artist' => $artist,
            'major' => $major,
            'userId' => $userId,
        ]);
    }

    /**
     * Vote for a requested song
     */
    public function vote(Request $request)
    {
        $request->validate([
            'songId' => 'required|integer',
            'userId' => 'required|string',
        ]);

        $songId = $request->input('songId');
        $userId = $request->input('userId');

        $song = RequestedSong::findOrFail($songId);
        
        // Handle is_voted field which is stored as JSON/text
        $voters = json_decode($song->is_voted, true);
        if (!is_array($voters)) {
            $voters = [];
        }

        if (in_array($userId, $voters)) {
            return response()->json([
                'success' => false, 
                'message' => 'You have already voted for this song'
            ]);
        }

        $voters[] = $userId;
        
        DB::table('requestedsongs')
            ->where('id', $songId)
            ->update([
                'is_voted' => json_encode($voters),
                'vote' => DB::raw('vote + 1')
            ]);

        $newCount = RequestedSong::find($songId)->vote;

        return response()->json([
            'success' => true, 
            'newVoteCount' => $newCount
        ]);
    }

    /**
     * Request a new song
     */
    public function store(Request $request)
    {
        $request->validate([
            'artistId' => 'required|integer',
            'songName' => 'required|string|max:100',
            'userId' => 'required|string',
        ]);

        $artistId = $request->input('artistId');
        $songName = $request->input('songName');
        $userId = $request->input('userId');

        // Check if this song already exists for this artist
        $existing = RequestedSong::where('artist_id', $artistId)
            ->where('name', $songName)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false, 
                'message' => 'This song has already been requested. You can vote for it!'
            ]);
        }

        $song = RequestedSong::create([
            'artist_id' => $artistId,
            'name' => $songName,
            'vote' => 1,
            'is_voted' => json_encode([$userId])
        ]);

        return response()->json([
            'success' => true, 
            'message' => 'Song requested successfully!'
        ]);
    }
}
