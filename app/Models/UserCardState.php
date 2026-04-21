<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCardState extends Model
{
    use HasFactory;

    protected $table = 'user_card_states';

    protected $fillable = [
        'user_id',
        'card_id',
        'ef',
        'interval_',
        'repetitions',
        'due_at',
        'suspended',
        'paused_until',
    ];
}
