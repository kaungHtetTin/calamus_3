<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpeakingDialogueTitle extends Model
{
    use HasFactory;

    protected $fillable = [
        'major',
        'title',
    ];

    public function dialogues()
    {
        return $this->hasMany(SpeakingDialogue::class);
    }
}
