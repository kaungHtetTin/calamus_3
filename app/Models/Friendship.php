<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Friendship extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'friend_id'];

    public function user()
    {
        return $this->belongsTo(Learner::class, 'user_id', 'user_id');
    }

    public function friend()
    {
        return $this->belongsTo(Learner::class, 'friend_id', 'user_id');
    }
}
