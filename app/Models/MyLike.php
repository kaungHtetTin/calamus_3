<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MyLike extends Model
{
    protected $table = 'mylikes';
    public $timestamps = false;

    protected $fillable = [
        'content_id',
        'likes',
        'rowNo'
    ];
}
