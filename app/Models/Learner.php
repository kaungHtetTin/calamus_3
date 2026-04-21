<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Learner extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'learners';

    protected $primaryKey = 'user_id';

    public $timestamps = false;

    // Remove $incrementing = false to allow auto-incrementing primary key
    // public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'learner_name',
        'learner_email',
        'learner_phone',
        'password',
        'learner_image',
        'cover_image',
        'gender',
        'bd_day',
        'bd_month',
        'bd_year',
        'work',
        'education',
        'region',
        'bio',
        'otp',
        'auth_token',
        'auth_token_mobile',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'auth_token',
        'auth_token_mobile',
        'otp',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Get the friends for the learner.
     */
    public function friends()
    {
        return $this->belongsToMany(Learner::class, 'friendships', 'user_id', 'friend_id', 'user_id', 'user_id');
    }

    /**
     * Get the friend requests sent by the learner.
     */
    public function sentFriendRequests()
    {
        return $this->hasMany(FriendRequestList::class, 'sender_id', 'user_id');
    }

    /**
     * Get the friend requests received by the learner.
     */
    public function receivedFriendRequests()
    {
        return $this->hasMany(FriendRequestList::class, 'receiver_id', 'user_id');
    }

    /**
     * Get the user data.
     */
    public function userData()
    {
        return $this->hasMany(UserData::class, 'user_id', 'user_id');
    }
}
