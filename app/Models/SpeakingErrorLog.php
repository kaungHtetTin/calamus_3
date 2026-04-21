<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpeakingErrorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'major',
        'dialogue_id',
        'error_text',
    ];

    public function dialogue()
    {
        return $this->belongsTo(SpeakingDialogue::class, 'dialogue_id');
    }

    public function learner()
    {
        return $this->belongsTo(Learner::class, 'user_id', 'user_id');
    }
}
