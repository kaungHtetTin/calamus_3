<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $table = 'announcements';
    public $timestamps = true;

    protected $fillable = [
        'link',
        'major',
        'is_seen'
    ];

    protected $casts = [
        'is_seen' => 'array'
    ];
}
