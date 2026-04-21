<?php

namespace App\Events;

use App\Models\Post;
use App\Models\Learner;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostLiked
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Post $post,
        public Learner $liker
    ) {}
}
