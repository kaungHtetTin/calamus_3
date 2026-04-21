<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Artist extends Model
{
    protected $table = 'artists';
    protected $fillable = ['name', 'image_url', 'major'];

    public function songs()
    {
        return $this->hasMany(Song::class, 'artist_id');
    }

    public function requestedSongs()
    {
        return $this->hasMany(RequestedSong::class, 'artist_id');
    }
}
