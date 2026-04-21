<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpeakingDialogue extends Model
{
    use HasFactory;

    protected $fillable = [
        'major',
        'speaking_dialogue_title_id',
        'person_a_text',
        'person_a_translation',
        'person_b_text',
        'person_b_translation',
        'sort_order',
    ];

    public function title()
    {
        return $this->belongsTo(SpeakingDialogueTitle::class, 'speaking_dialogue_title_id');
    }
}
