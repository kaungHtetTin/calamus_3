<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserData extends Model
{
    use HasFactory;

    protected $table = 'user_data';

    protected $fillable = [
        'user_id',
        'major',
        'is_vip',
        'diamond_plan',
        'game_score',
        'speaking_level',
        'meta',
        'token',
        'login_time',
        'first_join',
        'last_active'
    ];

    protected $casts = [
        'meta' => 'array',
        'token' => 'array',
    ];

    public function learner()
    {
        return $this->belongsTo(Learner::class, 'user_id', 'user_id');
    }
}
