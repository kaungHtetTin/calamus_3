<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $table = 'lessons';
    public $timestamps = false;

    protected $fillable = [
        'category_id',
        'date',
        'isVideo',
        'isVip',
        'isChannel',
        'link',
        'download_url',
        'document_link',
        'title_mini',
        'title',
        'major',
        'thumbnail',
        'like_count',
        'comment_count',
        'share_count',
        'view_count',
        'duration',
        'notes',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'date' => 'integer',
        'isVideo' => 'integer',
        'isVip' => 'integer',
        'isChannel' => 'integer',
        'like_count' => 'integer',
        'comment_count' => 'integer',
        'share_count' => 'integer',
        'view_count' => 'integer',
        'duration' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(LessonCategory::class, 'category_id');
    }
}
