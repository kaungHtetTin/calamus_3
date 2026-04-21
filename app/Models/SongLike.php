<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SongLike extends Model
{
    use HasFactory;

    protected $table = 'song_likes';

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'song_id',
    ];

    public function song()
    {
        return $this->belongsTo(Song::class, 'song_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
