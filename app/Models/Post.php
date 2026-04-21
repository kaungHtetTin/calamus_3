<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $table = 'posts';
    public $timestamps = false;

    // Note: post_id seems to be the main identifier used in legacy code
    // but 'id' exists in schema.
    
    protected $fillable = [
        'post_id',
        'learner_id',
        'user_id',
        'body',
        'blog_title',
        'post_like',
        'comments',
        'image',
        'video_url',
        'vimeo',
        'has_video',
        'share',
        'view_count',
        'share_count',
        'show_on_blog',
        'hide',
        'major'
    ];
}
