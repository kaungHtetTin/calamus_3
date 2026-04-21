<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GameWord extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'game_words';

    protected $fillable = [
        'major',
        'display_word',
        'display_image',
        'display_audio',
        'category',
        'a',
        'b',
        'c',
        'ans',
    ];
}
