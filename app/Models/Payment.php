<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Payment extends Model
{
    protected $table = 'payments';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'amount',
        'major',
        'courses',
        'meta',
        'screenshot',
        'approve',
        'activated',
        'transaction_id',
        'date'
    ];

    protected $casts = [
        'courses' => 'array',
        'meta' => 'array',
        'approve' => 'boolean',
        'activated' => 'boolean',
        'date' => 'datetime',
        'amount' => 'float'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
