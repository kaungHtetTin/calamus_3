<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $table = 'languages';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'display_name',
        'certificate_title',
        'code',
        'module_code',
        'primary_color',
        'secondary_color',
        'image_path',
        'seal',
        'firebase_topic_user',
        'firebase_topic_admin',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
