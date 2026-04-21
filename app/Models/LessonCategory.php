<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonCategory extends Model
{
    protected $table = 'lessons_categories';
    public $timestamps = false;

    protected $fillable = [
        'course_id',
        'category',
        'category_title',
        'image_url',
        'sort_order',
        'major',
    ];

    protected $casts = [
        'course_id' => 'integer',
        'sort_order' => 'integer',
    ];

    public function lessons()
    {
        return $this->hasMany(Lesson::class, 'category_id', 'id');
    }
}
