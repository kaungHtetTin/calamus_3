<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Study extends Model
{
    protected $table = 'studies';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'learner_id',
        'lesson_id',
        'frequent',
        'exercise_mark'
    ];
}
