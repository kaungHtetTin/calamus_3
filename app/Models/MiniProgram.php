<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MiniProgram extends Model
{
    use HasFactory;

    protected $table = 'mini_programs';

    protected $fillable = [
        'title',
        'link_url',
        'image_url',
        'major',
    ];
}
