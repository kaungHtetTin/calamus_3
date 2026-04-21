<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $table = 'comment';
    public $timestamps = false; // Legacy uses 'time' as bigint

    protected $fillable = [
        'post_id',
        'target_type',
        'target_id',
        'writer_id',
        'body',
        'image',
        'time',
        'parent',
        'likes',
    ];

    protected $casts = [
        'time' => 'integer',
        'likes' => 'integer',
        'parent' => 'integer',
        'target_id' => 'integer',
    ];
}
