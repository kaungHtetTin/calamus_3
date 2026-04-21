<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $table = 'conversations';
    public $timestamps = true; // Created_at and updated_at exist in schema? Legacy uses created_at. Updated_at?
    // Legacy schema check: c.created_at DESC.
    // Let's check schema for conversations.

    protected $fillable = [
        'user1_id',
        'user2_id',
        'major',
        'last_message_at',
    ];
}
