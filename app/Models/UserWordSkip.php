<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserWordSkip extends Model
{
    use HasFactory;

    protected $table = 'user_word_skips';

    protected $fillable = [
        'user_id',
        'card_id',
        'language_id',
        'reason',
        'skipped_at',
    ];

    public $timestamps = false;
}
