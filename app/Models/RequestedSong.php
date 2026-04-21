<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestedSong extends Model
{
    use HasFactory;

    protected $table = 'requestedsongs';
    public $timestamps = false;

    protected $fillable = [
        'artist_id',
        'name',
        'vote',
        'is_voted'
    ];

    public function artist()
    {
        return $this->belongsTo(Artist::class, 'artist_id');
    }
}
