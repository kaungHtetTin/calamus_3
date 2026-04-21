<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $table = 'ratings';
    public $timestamps = false;

    protected $fillable = ['user_id', 'course_id', 'star', 'review', 'time'];

    public function learner()
    {
        return $this->belongsTo(Learner::class, 'user_id', 'learner_phone');
    }
}
