<?php

namespace App\Events;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Lesson;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Comment $comment,
        public string $targetType,
        public string $targetId,
        public ?Post $post = null,
        public ?Lesson $lesson = null,
        public ?Comment $parentComment = null
    ) {}
}
