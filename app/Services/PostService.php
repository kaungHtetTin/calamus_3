<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PostService
{
    /**
     * Get pinned posts for the blog
     * 
     * @return \Illuminate\Support\Collection
     */
    public function getPinnedPosts()
    {
        $pinnedPosts = DB::table('posts')
            ->leftJoin('learners', 'learners.user_id', '=', 'posts.user_id')
            ->select(
                'posts.post_id as postId',
                'posts.body',
                'posts.image as postImage',
                'posts.blog_title as blogTitle',
                'posts.major',
                'posts.user_id as userId',
                'learners.learner_name as userName',
                'learners.learner_image as userImage'
            )
            ->where('posts.show_on_blog', 1)
            ->orderBy('posts.post_id', 'desc')
            ->limit(6)
            ->get();

        return $pinnedPosts->map(function ($post) {
            return [
                'postId' => (int)$post->postId,
                'body' => $this->ensureUtf8($post->body),
                'postImage' => $post->postImage ?? '',
                'blogTitle' => $this->ensureUtf8($post->blogTitle),
                'major' => $post->major ?? '',
                'userId' => $post->userId ?? '',
                'userName' => $this->ensureUtf8($post->userName ?? 'Anonymous'),
                'userImage' => $post->userImage ?? \Illuminate\Support\Facades\Storage::disk('uploads')->url('placeholder.png'),
            ];
        });
    }

    private function ensureUtf8($string)
    {
        return mb_convert_encoding($string ?? '', 'UTF-8', 'UTF-8');
    }
}
