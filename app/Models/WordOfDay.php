<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WordOfDay extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'word_of_days';

    protected $fillable = [
        'major',
        'word',
        'translation',
        'speech',
        'example',
        'thumb',
        'audio',
    ];

    public function scopeByMajor(Builder $query, string $major): Builder
    {
        $normalized = strtolower(trim($major));

        if ($normalized === 'ko') {
            $normalized = 'korea';
        }

        return $query->where('word_of_days.major', $normalized);
    }
}
