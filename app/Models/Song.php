<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Song extends Model
{
    protected $table = 'songs';
    public $timestamps = true;

    protected $fillable = [
        'title',
        'artist_id',
        'major',
        'asset_slug',
        'legacy_song_id',
        'audio_url',
        'image_url',
        'thumbnail_url',
        'lyric_url',
        'legacy_meta',
    ];

    public function artistRef()
    {
        return $this->belongsTo(Artist::class, 'artist_id');
    }

    public function getArtistAttribute()
    {
        return $this->artistRef?->name ?? '';
    }

    public function getUrlAttribute()
    {
        return $this->asset_slug ?? '';
    }

    public function getTypeAttribute()
    {
        return $this->major ?? '';
    }
}
