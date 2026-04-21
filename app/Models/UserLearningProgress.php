<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLearningProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'language_id',
        'deck_id',
        'current_learning_day',
        'last_session_date',
        'total_learning_days',
        'streak_count',
        'longest_streak',
    ];
}
