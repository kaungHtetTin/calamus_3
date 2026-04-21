<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    protected $table = 'certificates';
    public $timestamps = false;

    protected $fillable = [
        'course_id',
        'user_id', // Note: This stores learner_phone as integer? or string? Schema says int(11). But learners.learner_phone is usually varchar.
        // Legacy code: $phoneEscaped used in INSERT.
        // Let's check if user_id in certificates matches learners.id or learners.learner_phone.
        // Legacy api/courses/get-certificate.php: $phone = $_GET['userId']; ... $Certificate->store($courseId, $phoneEscaped);
        // And $Certificate->detail($courseId, $phoneEscaped);
        // If schema says int(11), and phone is string like "09...", it might be stored as int if it fits.
        // But if phone has spaces or +, it might break if strictly int.
        // However, schema says `user_id` int(11).
        // I will treat it as whatever legacy does.
        'date'
    ];
}
