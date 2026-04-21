<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $table = 'courses';
    protected $primaryKey = 'course_id';
    public $timestamps = false;

    protected $fillable = [
        'teacher_id',
        'title',
        'certificate_title',
        'lessons_count',
        'cover_url',
        'web_cover',
        'description',
        'details',
        'is_vip',
        'active',
        'duration',
        'background_color',
        'fee',
        'enroll',
        'rating',
        'major',
        'sorting',
        'preview',
        'certificate_code',
    ];

    protected $casts = [
        'teacher_id' => 'integer',
        'duration' => 'integer',
        'rating' => 'float',
        'fee' => 'integer',
        'lessons_count' => 'integer',
        'is_vip' => 'integer',
        'enroll' => 'integer',
        'sorting' => 'integer',
        'active' => 'boolean',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function vipUsers()
    {
        return $this->hasMany(VipUser::class, 'course_id', 'course_id');
    }

    public function lessonsCategories()
    {
        return $this->hasMany(LessonCategory::class, 'course_id', 'course_id');
    }
}
