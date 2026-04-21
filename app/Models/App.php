<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class App extends Model
{
    protected $table = 'apps';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'url',
        'cover',
        'icon',
        'type',
        'click',
        'show_on',
        'active_course',
        'student_learning',
        'major',
        'package_id',
        'platform',
        'latest_version_code',
        'latest_version_name',
        'min_version_code',
        'update_message',
        'force_update',
    ];
}
