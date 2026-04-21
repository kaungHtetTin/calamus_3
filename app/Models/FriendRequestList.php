<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FriendRequestList extends Model
{
    use HasFactory;

    protected $table = 'friend_request_lists';

    protected $fillable = ['sender_id', 'receiver_id'];

    public function sender()
    {
        return $this->belongsTo(Learner::class, 'sender_id', 'user_id');
    }

    public function receiver()
    {
        return $this->belongsTo(Learner::class, 'receiver_id', 'user_id');
    }
}
