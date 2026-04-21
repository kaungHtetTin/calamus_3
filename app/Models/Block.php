<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Block extends Model
{
    protected $table = 'blocks';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'blocked_user_id',
    ];
}
