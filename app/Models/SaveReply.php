<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaveReply extends Model
{
    use HasFactory;

    protected $table = 'save_replies';

    public $timestamps = false;

    protected $fillable = [
        'title',
        'message',
        'major',
    ];
}
