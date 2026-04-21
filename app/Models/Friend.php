<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Friend extends Model
{
    protected $table = 'friends';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'korea',
        'korea_count',
        'english',
        'english_count',
        'chinese',
        'chinese_count',
        'japanese',
        'japanese_count',
        'russian',
        'russian_count'
    ];
}
