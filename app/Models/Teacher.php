<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    protected $table = 'teachers';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'profile',
        'rank',
        'facebook',
        'telegram',
        'youtube',
        'description',
        'qualification',
        'experience',
        'total_course',
    ];

    protected $casts = [
        'rank' => 'integer',
        'experience' => 'integer',
        'total_course' => 'integer',
    ];
}
