<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    protected static function booted()
    {
        static::created(function (Lesson $lesson) {
            static::syncCourseLessonsCountByCategoryId((int) $lesson->category_id);
        });

        static::deleted(function (Lesson $lesson) {
            static::syncCourseLessonsCountByCategoryId((int) $lesson->category_id);
        });

        static::updated(function (Lesson $lesson) {
            if (! $lesson->wasChanged('category_id')) {
                return;
            }

            static::syncCourseLessonsCountByCategoryId((int) $lesson->getOriginal('category_id'));
            static::syncCourseLessonsCountByCategoryId((int) $lesson->category_id);
        });
    }

    private static function syncCourseLessonsCountByCategoryId(int $categoryId): void
    {
        if ($categoryId <= 0) {
            return;
        }

        $courseId = (int) DB::table('lessons_categories')
            ->where('id', $categoryId)
            ->value('course_id');

        if ($courseId <= 0) {
            return;
        }

        Course::syncLessonsCount($courseId);
    }
}
