<?php

namespace App\Http\Controllers;

use App\Events\PostLiked;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\MyLike;
use App\Models\Report;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DiscussionController extends Controller
{
    use ApiResponse;

    // ... index method exists ...

    public function index(Request $request)
    {
        try {
            $category = $request->query('category'); // Optional category
            $page = max(1, (int)$request->query('page', 1));
            $limit = 15;
            $offset = ($page - 1) * $limit;

            $user = auth('sanctum')->user();
            if ($user) {
                $userId = $user->user_id;
            }else{
                $userId = 0;
            }

            $postsQuery = DB::table('posts')
                ->leftJoin('learners', 'learners.user_id', '=', 'posts.user_id')
                ->select(
                    'posts.post_id as postId',
                    'posts.body',
                    'posts.image as postImage',
                    'posts.hide as hidden',
                    'posts.has_video',
                    'posts.vimeo',
                    'posts.post_like as postLikes',
                    'posts.comments',
                    'posts.share_count as shareCount',
                    'posts.view_count as viewCount',
                    'posts.show_on_blog',
                    'posts.blog_title',
                    'posts.major as category',
                    'posts.user_id as userId',
                    'learners.learner_name as userName',
                    'learners.learner_image as userImage'
                )
                ->where('posts.hide', 0)
                ->when($userId, function ($query) use ($userId) {
                    $query->whereNotExists(function ($subQuery) use ($userId) {
                        $subQuery->select(DB::raw(1))
                            ->from('hidden_posts')
                            ->whereColumn('hidden_posts.post_id', 'posts.post_id')
                            ->where('hidden_posts.user_id', (string)$userId);
                    });

                    // Exclude posts from users who are blocked by current user or have blocked current user
                    $query->whereNotExists(function ($subQuery) use ($userId) {
                        $subQuery->select(DB::raw(1))
                            ->from('blocks')
                            ->where(function ($q) use ($userId) {
                                $q->where('blocks.user_id', (string)$userId)
                                  ->whereColumn('blocks.blocked_user_id', 'posts.user_id')
                                  ->orWhere(function ($q2) use ($userId) {
                                      $q2->where('blocks.blocked_user_id', (string)$userId)
                                         ->whereColumn('blocks.user_id', 'posts.user_id');
                                  });
                            });
                    });

                    return $query;
                })
                ->when($category, function ($query) use ($category) {
                    return $query->where('posts.major', $category);
                })
                ->whereNotExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('lessons')
                        ->whereColumn('lessons.date', 'posts.post_id');
                })
                ->where('posts.share', 0)
                ->orderBy('posts.post_id', 'desc')
                ->limit($limit)
                ->offset($offset);

            $posts = $postsQuery->get();

            $postIds = $posts->pluck('postId')->toArray();
            $userLikes = [];

            if ($userId && !empty($postIds)) {
                $likesData = MyLike::whereIn('content_id', $postIds)->get();
                foreach ($likesData as $like) {
                    $likesArr = json_decode($like->likes, true);
                    if (is_array($likesArr)) {
                        $userIds = array_column($likesArr, 'user_id');
                        if (in_array($userId, $userIds)) {
                            $userLikes[$like->content_id] = true;
                        }
                    }
                }
            }

            $formattedPosts = $posts->map(function ($post) use ($userLikes, $category) {
                return [
                    'postId' => (int)$post->postId,
                    'body' => $this->ensureUtf8($post->body),
                    'postImage' => $post->postImage ?? '',
                    'hidden' => (int)$post->hidden,
                    'hasVideo' => (int)$post->has_video,
                    'vimeo' => $post->vimeo ?? '',
                    'postLikes' => (int)$post->postLikes,
                    'comments' => (int)$post->comments,
                    'shareCount' => (int)$post->shareCount,
                    'viewCount' => (int)$post->viewCount,
                    'isLiked' => isset($userLikes[$post->postId]) ? 1 : 0,
                    'showOnBlog' => (int)$post->show_on_blog,
                    'blogTitle' => $this->ensureUtf8($post->blog_title),
                    'category' => $post->category ?? $category,
                    'userId' => $post->userId ?? '',
                    'userName' => $this->ensureUtf8($post->userName ?? 'Anonymous'),
                    'userImage' => $post->userImage ?? 'https://www.calamuseducation.com/uploads/placeholder.png',
                    'vip' => (int)($post->vip ?? 0),
                ];
            });

            $total = DB::table('posts')
                ->where('hide', 0)
                ->when($userId, function ($query) use ($userId) {
                    $query->whereNotExists(function ($subQuery) use ($userId) {
                        $subQuery->select(DB::raw(1))
                            ->from('hidden_posts')
                            ->whereColumn('hidden_posts.post_id', 'posts.post_id')
                            ->where('hidden_posts.user_id', (string)$userId);
                    });

                    // Exclude posts from users who are blocked by current user or have blocked current user
                    $query->whereNotExists(function ($subQuery) use ($userId) {
                        $subQuery->select(DB::raw(1))
                            ->from('blocks')
                            ->where(function ($q) use ($userId) {
                                $q->where('blocks.user_id', (string)$userId)
                                  ->whereColumn('blocks.blocked_user_id', 'posts.user_id')
                                  ->orWhere(function ($q2) use ($userId) {
                                      $q2->where('blocks.blocked_user_id', (string)$userId)
                                         ->whereColumn('blocks.user_id', 'posts.user_id');
                                  });
                            });
                    });

                    return $query;
                })
                ->when($category, function ($query) use ($category) {
                    return $query->where('major', $category);
                })
                ->whereNotExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('lessons')
                        ->whereColumn('lessons.date', 'posts.post_id');
                })
                ->where('share', 0)
                ->count();

            return $this->successResponse(
                $formattedPosts,
                200,
                $this->paginate($total, $page, $limit)
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Server error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get Discussion Detail
     */
    public function show(Request $request)
    {
        try {
            $postId = (int)$request->query('postId');
            $user = auth('sanctum')->user();
            if ($user) {
                $userId = $user->user_id;
            }else{
                $userId = 0;
            }

            if (!$postId) {
                return $this->errorResponse('Post ID is required', 400);
            }

            $post = DB::table('posts')
                ->leftJoin('learners', 'learners.user_id', '=', 'posts.user_id')
                ->select(
                    'posts.post_id as postId',
                    'posts.body',
                    'posts.image as postImage',
                    'posts.hide as hidden',
                    'posts.has_video',
                    'posts.vimeo',
                    'posts.post_like as postLikes',
                    'posts.comments',
                    'posts.share_count as shareCount',
                    'posts.view_count as viewCount',
                    'posts.show_on_blog',
                    'posts.blog_title',
                    'posts.major as category',
                    'posts.user_id as userId',
                    'learners.learner_name as userName',
                    'learners.learner_image as userImage'
                )
                ->where('posts.post_id', $postId)
                ->where('posts.hide', 0)
                ->whereNotExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('lessons')
                        ->whereColumn('lessons.date', 'posts.post_id');
                })
                ->when($userId, function ($query) use ($userId) {
                    $query->whereNotExists(function ($subQuery) use ($userId) {
                        $subQuery->select(DB::raw(1))
                            ->from('hidden_posts')
                            ->whereColumn('hidden_posts.post_id', 'posts.post_id')
                            ->where('hidden_posts.user_id', (string)$userId);
                    });

                    // Exclude posts from users who are blocked by current user or have blocked current user
                    $query->whereNotExists(function ($subQuery) use ($userId) {
                        $subQuery->select(DB::raw(1))
                            ->from('blocks')
                            ->where(function ($q) use ($userId) {
                                $q->where('blocks.user_id', (string)$userId)
                                  ->whereColumn('blocks.blocked_user_id', 'posts.user_id')
                                  ->orWhere(function ($q2) use ($userId) {
                                      $q2->where('blocks.blocked_user_id', (string)$userId)
                                         ->whereColumn('blocks.user_id', 'posts.user_id');
                                  });
                            });
                    });

                    return $query;
                })
                ->first();

            if (!$post) {
                return $this->errorResponse('Post not found', 404);
            }

            // Check Like Status
            $isLiked = 0;
            if ($userId) {
                $like = MyLike::where('content_id', $postId)->first();
                if ($like) {
                    $likesArr = json_decode($like->likes, true);
                    if (is_array($likesArr)) {
                        $userIds = array_column($likesArr, 'user_id');
                        if (in_array($userId, $userIds)) {
                            $isLiked = 1;
                        }
                    }
                }
            }

            $formattedPost = [
                'postId' => (int)$post->postId,
                'body' => $this->ensureUtf8($post->body),
                'postImage' => $post->postImage ?? '',
                'hasVideo' => (int)$post->has_video,
                'vimeo' => $post->vimeo ?? '',
                'postLikes' => (int)$post->postLikes,
                'comments' => (int)$post->comments,
                'shareCount' => (int)$post->shareCount,
                'viewCount' => (int)$post->viewCount,
                'isLiked' => $isLiked,
                'showOnBlog' => (int)$post->show_on_blog,
                'blogTitle' => $this->ensureUtf8($post->blog_title),
                'category' => $post->category ?? '',
                'userId' => $post->userId ?? '',
                'userName' => $this->ensureUtf8($post->userName ?? 'Anonymous'),
                'userImage' => $post->userImage ?? 'https://www.calamuseducation.com/uploads/placeholder.png',
                'vip' => (int)($post->vip ?? 0),
            ];

            return $this->successResponse($formattedPost);

        } catch (\Exception $e) {
            return $this->errorResponse('Server error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create Post
     */
    public function create(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->errorResponse('Not authenticated', 401);
        }

        $body = trim($request->input('body', ''));
        $category = trim($request->input('category', 'english'));
        $image = $request->input('image', ''); // base64

        if (empty($body) && empty($image)) {
            return $this->errorResponse('Post cannot be empty', 400);
        }

        // Handle Image
        $imagePath = '';
        $postId = round(microtime(true) * 1000);

        if (!empty($image)) {
            // Check base64 format
            if (preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
                $ext = strtolower($type[1]);
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    return $this->errorResponse('Invalid image format', 400);
                }
                $imageData = base64_decode(substr($image, strpos($image, ',') + 1));
                if ($imageData === false) {
                    return $this->errorResponse('Failed to process image', 400);
                }
                if (strlen($imageData) > 5 * 1024 * 1024) {
                    return $this->errorResponse('Image too large', 400);
                }

                $fileName = $postId . '_' . $user->user_id . '.' . $ext;
                $path = 'posts'; 
                \Illuminate\Support\Facades\Storage::disk('uploads')->put($path . '/' . $fileName, $imageData);
                $imagePath = env('APP_URL') . \Illuminate\Support\Facades\Storage::disk('uploads')->url($path . '/' . $fileName);
            }
        }

        $post = new Post();
        $post->post_id = $postId;
        $post->user_id = $user->user_id;
        $post->learner_id = $user->user_id; // Set for legacy compatibility
        $post->body = $body;
        $post->major = $category;
        $post->image = $imagePath;
        $post->post_like = 0;
        $post->comments = 0;
        $post->hide = 0;
        $post->share = 0;
        // ... other defaults
        $post->save();

        return $this->successResponse(['success' => true, 'postId' => $postId]);
    }

    /**
     * Share an existing post by creating a new post record.
     *
     * Rule:
     * - Shared post row has `posts.share = originalPostId`
     * - Original post `share_count` is incremented
     */
    public function share(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->errorResponse('Not authenticated', 401);
        }

        $originalPostId = (int)$request->input('postId');
        if ($originalPostId <= 0) {
            return $this->errorResponse('Post ID required', 400);
        }

        // Only share visible posts.
        $originalPost = Post::where('post_id', $originalPostId)
            ->where('hide', 0)
            ->first();

        if (!$originalPost) {
            return $this->errorResponse('Post not found', 404);
        }

        $sharedPostId = round(microtime(true) * 1000);

        // Create a new shared post record (copy original content).
        $post = new Post();
        $post->post_id = $sharedPostId;
        $post->user_id = $user->user_id;
        $post->learner_id = $user->user_id; // legacy compatibility

        $post->body = $originalPost->body;
        $post->major = $originalPost->major;

        $post->blog_title = $originalPost->blog_title ?? '';
        $post->image = $originalPost->image ?? '';
        $post->video_url = $originalPost->video_url ?? '';
        $post->vimeo = $originalPost->vimeo ?? '';
        $post->has_video = (int)($originalPost->has_video ?? 0);

        // Social counters for the shared record itself start at 0.
        $post->post_like = 0;
        $post->comments = 0;
        $post->share_count = 0;
        $post->view_count = 0;

        $post->hide = 0;
        $post->share = $originalPostId; // <-- main rule requested
        $post->show_on_blog = (int)($originalPost->show_on_blog ?? 0);

        $post->save();

        // Increment original post's share counter.
        try {
            DB::table('posts')->where('post_id', $originalPostId)->increment('share_count');
        } catch (\Throwable $e) {
            // Sharing should still succeed even if share_count can't be incremented.
        }

        return $this->successResponse([
            'success' => true,
            'postId' => (int)$sharedPostId,
        ]);
    }

    /**
     * Update Post
     */
    public function update(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->errorResponse('Not authenticated', 401);
        }

        $postId = (int)$request->input('postId');
        if ($postId <= 0) {
            return $this->errorResponse('Post ID required', 400);
        }

        $hasBody = $request->has('body');
        $hasCategory = $request->has('category');
        $hasImage = $request->has('image');
        $hasRemoveImage = $request->has('removeImage');

        if (!$hasBody && !$hasCategory && !$hasImage && !$hasRemoveImage) {
            return $this->errorResponse('No fields to update', 400);
        }

        $post = Post::where('post_id', $postId)->first();
        if (!$post) {
            return $this->errorResponse('Post not found', 404);
        }

        if ((string)$post->user_id !== (string)$user->user_id) {
            return $this->errorResponse('You can only edit your own posts', 403);
        }

        if ($hasBody) {
            $post->body = trim((string)$request->input('body', ''));
        }

        if ($hasCategory) {
            $post->major = trim((string)$request->input('category', 'english'));
        }

        $removeImage = filter_var($request->input('removeImage', false), FILTER_VALIDATE_BOOLEAN);
        if ($removeImage) {
            $post->image = '';
        }

        if ($hasImage) {
            $image = (string)$request->input('image', '');
            if ($image !== '') {
                if (!preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
                    return $this->errorResponse('Invalid image format', 400);
                }

                $ext = strtolower($type[1]);
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    return $this->errorResponse('Invalid image format', 400);
                }

                $imageData = base64_decode(substr($image, strpos($image, ',') + 1));
                if ($imageData === false) {
                    return $this->errorResponse('Failed to process image', 400);
                }
                if (strlen($imageData) > 5 * 1024 * 1024) {
                    return $this->errorResponse('Image too large', 400);
                }

                $fileName = $postId . '_' . $user->user_id . '.' . $ext;
                $path = 'posts';
                \Illuminate\Support\Facades\Storage::disk('uploads')->put($path . '/' . $fileName, $imageData);
                $post->image = env('APP_URL') . \Illuminate\Support\Facades\Storage::disk('uploads')->url($path . '/' . $fileName);
            }
        }

        if (trim((string)$post->body) === '' && trim((string)$post->image) === '') {
            return $this->errorResponse('Post cannot be empty', 400);
        }

        $post->save();

        return $this->successResponse([
            'success' => true,
            'postId' => (int)$post->post_id,
            'body' => $this->ensureUtf8($post->body),
            'postImage' => $post->image ?? '',
            'category' => $post->major ?? '',
        ]);
    }

    /**
     * Delete Post
     */
    public function delete(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->errorResponse('Not authenticated', 401);
        }

        $postId = $request->input('postId');
        if (!$postId) {
            return $this->errorResponse('Post ID required', 400);
        }

        $post = Post::where('post_id', $postId)->first();
        if (!$post) {
            return $this->errorResponse('Post not found', 404);
        }

        if ((string)$post->user_id !== (string)$user->user_id) {
            return $this->errorResponse('You can only delete your own posts', 403);
        }

        // Delete post
        $post->delete();

        // Cleanup
        DB::table('hidden_posts')->where('post_id', $postId)->delete();
        \App\Services\NotificationCleanupService::forPost((int) $postId);
        // DB::table('report')->where('post_id', $postId)->delete(); // etc.

        return $this->successResponse(['success' => true]);
    }

    /**
     * Report Post
     */
    public function report(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->errorResponse('Not authenticated', 401);
        }

        $postId = $request->input('postId');
        if (!$postId) {
            return $this->errorResponse('Post ID required', 400);
        }

        $exists = Report::where('post_id', $postId)->exists();
        if ($exists) {
            return $this->successResponse(['success' => true, 'message' => 'Already reported']);
        }

        Report::create(['post_id' => $postId]);

        return $this->successResponse(['success' => true, 'message' => 'Reported successfully']);
    }

    /**
     * Like Post
     */
    public function like(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->errorResponse('Not authenticated', 401);
        }

        $postId = $request->input('postId');
        if (!$postId) {
            return $this->errorResponse('Post ID required', 400);
        }

        $userId = (string)$user->user_id;
        $post = Post::where('post_id', $postId)->first();
        if (!$post) {
            return $this->errorResponse('Post not found', 404);
        }

        // Logic from legacy: check MyLike
        // Sharding logic (rowNo) is complex in legacy (1000 likes per row).
        // Simplified implementation: Find row with space or create new.
        // Actually, just find ANY row with this user, if not found, find row with < 1000 likes, else create new.
        
        // 1. Check if user already liked
        $likesData = MyLike::where('content_id', $postId)->get();
        $foundLike = false;
        $targetRow = null;

        foreach ($likesData as $row) {
            $arr = json_decode($row->likes, true);
            if (is_array($arr)) {
                foreach ($arr as $k => $item) {
                    if ($item['user_id'] == $userId) {
                        // Found! Unlike.
                        array_splice($arr, $k, 1);
                        $row->likes = json_encode($arr);
                        $row->save();
                        $foundLike = true;
                        
                        // Decrement post like count
                        $post->decrement('post_like');
                        
                        return $this->successResponse([
                            'success' => true, 
                            'isLiked' => false, 
                            'count' => $post->post_like
                        ]);
                    }
                }
                if (count($arr) < 1000) {
                    $targetRow = $row;
                }
            }
        }

        // Not found, so Like.
        if ($targetRow) {
            $arr = json_decode($targetRow->likes, true);
            $arr[] = ['user_id' => $userId];
            $targetRow->likes = json_encode($arr);
            $targetRow->save();
        } else {
            // Create new row
            $newRowNo = $likesData->count(); // Next row index
            MyLike::create([
                'content_id' => $postId,
                'likes' => json_encode([['user_id' => $userId]]),
                'rowNo' => $newRowNo
            ]);
        }

        // Increment post like count
        $post->increment('post_like');

        $ownerId = $post->user_id ?? $post->learner_id;
        if ($ownerId && (string) $ownerId !== (string) $userId) {
            PostLiked::dispatch($post, $request->user());
        }

        return $this->successResponse([
            'success' => true, 
            'isLiked' => true, 
            'count' => $post->post_like
        ]);
    }

    /**
     * Hide Post
     */
    public function hide(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->errorResponse('Not authenticated', 401);
        }

        $postId = $request->input('postId');
        if (!$postId) {
            return $this->errorResponse('Post ID required', 400);
        }

        $post = Post::where('post_id', $postId)->first();
        if (!$post) {
            return $this->errorResponse('Post not found', 404);
        }

        // Add to hidden_posts table if not already there
        $userId = (string)$user->user_id;
        $exists = DB::table('hidden_posts')
            ->where('user_id', $userId)
            ->where('post_id', $postId)
            ->exists();

        if (!$exists) {
            DB::table('hidden_posts')->insert([
                'user_id' => $userId,
                'post_id' => $postId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $this->successResponse(['success' => true, 'message' => 'Post hidden successfully']);
    }

    /**
     * Get users who liked a post
     */
    public function likes(Request $request)
    {
        $postId = (int)$request->query('postId');
        $page = max(1, (int)$request->query('page', 1));
        $limit = max(1, min(100, (int)$request->query('limit', 20)));

        if ($postId <= 0) {
            return $this->errorResponse('Post ID is required', 400);
        }

        $likeRows = MyLike::where('content_id', $postId)->get();
        $likedUserIds = [];

        foreach ($likeRows as $row) {
            $arr = json_decode($row->likes, true);
            if (!is_array($arr)) {
                continue;
            }
            foreach ($arr as $item) {
                $uid = (string)($item['user_id'] ?? '');
                if ($uid !== '') {
                    $likedUserIds[$uid] = true; // dedupe
                }
            }
        }

        $allIds = array_keys($likedUserIds);
        $total = count($allIds);
        $offset = ($page - 1) * $limit;
        $pagedIds = array_slice($allIds, $offset, $limit);

        if (empty($pagedIds)) {
            return $this->successResponse([], 200, $this->paginate($total, $page, $limit));
        }

        $learners = DB::table('learners')
            ->select('user_id', 'learner_name', 'learner_image')
            ->whereIn('user_id', $pagedIds)
            ->get()
            ->keyBy('user_id');

        $data = [];
        foreach ($pagedIds as $uid) {
            $learner = $learners->get($uid);
            $data[] = [
                'userId' => (string)$uid,
                'userName' => $this->ensureUtf8($learner->learner_name ?? 'Unknown User'),
                'userImage' => $learner->learner_image ?? null,
            ];
        }

        return $this->successResponse($data, 200, $this->paginate($total, $page, $limit));
    }

    private function ensureUtf8($string)
    {
        return mb_convert_encoding($string ?? '', 'UTF-8', 'UTF-8');
    }
}
