<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivationMessage extends Model
{
    use HasFactory;

    protected $table = 'activation_messages';

    protected $fillable = [
        'message',
        'major',
    ];
}
