<?php

namespace App\Http\Controllers;

use App\Events\CommentCreated;
use App\Events\CommentLiked;
use App\Models\Comment;
use App\Models\Learner;
use App\Models\Post;
use App\Models\Lesson;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    use ApiResponse;

    /**
     * Get comments for a post
     */
    public function index(Request $request)
    {
        $target = $this->resolveCommentTarget($request);
        $page = (int)$request->input('page', 1);
        $limit = (int)$request->input('limit', 20);
        $user = auth('sanctum')->user();
        $currentUserId = $user ? $user->user_id : null;

        if (!$target) {
            return $this->errorResponse('Target is required (postId or lessonId)', 400);
        }

        // 0. Total comments count (including replies)
        $totalComments = DB::table('comment')
            ->where('target_type', $target['type'])
            ->where('target_id', $target['id'])
            ->count();

        // 1. Paginate parent comments only
        $parentCommentsQuery = DB::table('comment')
            ->select(
                'comment.id',
                'comment.post_id as postId',
                'comment.target_type as targetType',
                'comment.target_id as targetId',
                'comment.writer_id as writerId',
                'learners.learner_name as writerName',
                'learners.learner_image as writerImage',
                'comment.time',
                'comment.parent',
                'comment.likes',
                'comment.body',
                'comment.image'
            )
            ->leftJoin('learners', 'learners.user_id', '=', 'comment.writer_id')
            ->where('comment.target_type', $target['type'])
            ->where('comment.target_id', $target['id'])
            ->where('comment.parent', 0)
            ->orderBy('comment.time', 'desc');

        $paginatedParents = $parentCommentsQuery->paginate($limit, ['*'], 'page', $page);

        if ($paginatedParents->isEmpty()) {
            return $this->successResponse([
                'comments' => [],
                'totalComments' => $totalComments,
                'pagination' => [
                    'currentPage' => $paginatedParents->currentPage(),
                    'lastPage' => $paginatedParents->lastPage(),
                    'total' => $paginatedParents->total(), // This is total parent comments
                ]
            ]);
        }

        // 2. Get all replies for the parents on this page
        $parentTimes = $paginatedParents->pluck('time')->toArray();
        $replies = DB::table('comment')
            ->select(
                'comment.id',
                'comment.post_id as postId',
                'comment.target_type as targetType',
                'comment.target_id as targetId',
                'comment.writer_id as writerId',
                'learners.learner_name as writerName',
                'learners.learner_image as writerImage',
                'comment.time',
                'comment.parent',
                'comment.likes',
                'comment.body',
                'comment.image'
            )
            ->leftJoin('learners', 'learners.user_id', '=', 'comment.writer_id')
            ->whereIn('comment.parent', $parentTimes)
            ->orderBy('comment.time', 'asc') // Replies usually chronological
            ->get();

        // 3. Collect all comment IDs (parents + replies) to check likes efficiently
        $allCommentTimes = array_merge($parentTimes, $replies->pluck('time')->toArray());
        $likedCommentTimes = [];
        if ($currentUserId) {
            $likedCommentTimes = DB::table('comment_likes')
                ->where('user_id', $currentUserId)
                ->whereIn('comment_id', $allCommentTimes)
                ->pluck('comment_id')
                ->map(fn($id) => (int)$id) // Ensure they are integers
                ->toArray();
        }

        // 4. Format and organize
        $formatComment = function ($comment) use ($likedCommentTimes) {
            $c = (array)$comment;
            $c['isLiked'] = in_array((int)$comment->time, $likedCommentTimes) ? 1 : 0;
            $c['postId'] = ($c['targetType'] === 'post') ? (int)$c['targetId'] : null;
            $c['lessonId'] = ($c['targetType'] === 'lesson') ? (int)$c['targetId'] : null;
            if (!isset($c['writerName']) || trim((string) $c['writerName']) === '') {
                $c['writerName'] = ((string) ($c['writerId'] ?? '') === '10000') ? 'Admin' : 'Unknown';
            }
            if (!isset($c['writerImage']) || trim((string) $c['writerImage']) === '') {
                $c['writerImage'] = 'https://www.calamuseducation.com/uploads/placeholder.png';
            }
            $c['child'] = [];
            return $c;
        };

        $repliesGrouped = [];
        foreach ($replies as $reply) {
            $repliesGrouped[$reply->parent][] = $formatComment($reply);
        }

        $finalComments = [];
        foreach ($paginatedParents as $parent) {
            $p = $formatComment($parent);
            $p['child'] = $repliesGrouped[$parent->time] ?? [];
            $finalComments[] = $p;
        }

        return $this->successResponse(
            $finalComments,
            200,
            array_merge(
                $this->paginate($paginatedParents->total(), $page, $limit),
                ['totalComments' => $totalComments]
            )
        );
    }

    /**
     * Store a new comment
     */
    public function store(Request $request)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return $this->errorResponse('Not authenticated', 401);
        }

        $target = $this->resolveCommentTarget($request);
        $body = trim($request->input('body', ''));
        $image = $request->input('image', ''); // base64 image (optional)
        $parent = (int)$request->input('parent', 0); // parent comment's time

        if (!$target || (empty($body) && empty($image))) {
            return $this->errorResponse('Target and body or image are required', 400);
        }

        if ($target['type'] === 'post') {
            $post = Post::where('post_id', $target['id'])->first();
            if (!$post) {
                return $this->errorResponse('Post not found', 404);
            }
        } else {
            $lesson = Lesson::where('id', $target['id'])->first();
            if (!$lesson) {
                return $this->errorResponse('Lesson not found', 404);
            }
        }

        $time = round(microtime(true) * 1000);
        $imagePath = '';

        if ($parent > 0) {
            $parentExists = Comment::where('time', $parent)
                ->where('target_type', $target['type'])
                ->where('target_id', $target['id'])
                ->exists();
            if (!$parentExists) {
                return $this->errorResponse('Parent comment not found for this target', 404);
            }
        }

        if (!empty($image)) {
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

                $fileName = $time . '_' . $user->user_id . '.' . $ext;
                $path = 'comments';
                \Illuminate\Support\Facades\Storage::disk('uploads')->put($path . '/' . $fileName, $imageData);
                $imagePath = env('APP_URL')  . \Illuminate\Support\Facades\Storage::disk('uploads')->url($path . '/' . $fileName);
            } else {
                return $this->errorResponse('Invalid image format', 400);
            }
        }

        $comment = new Comment();
        $comment->post_id = $target['type'] === 'post' ? $target['id'] : null; // backward compatibility
        $comment->target_type = $target['type'];
        $comment->target_id = $target['id'];
        $comment->writer_id = $user->user_id;
        $comment->body = $body;
        $comment->image = $imagePath; // Non-nullable field, keep empty string when no image
        $comment->time = $time;
        $comment->parent = $parent;
        $comment->likes = 0;
        $comment->save();

        // Increment target comments count
        if ($target['type'] === 'post') {
            DB::table('posts')->where('post_id', $target['id'])->increment('comments');
        } else {
            DB::table('lessons')->where('id', $target['id'])->increment('comment_count');
        }

        $parentComment = $parent > 0
            ? Comment::where('time', $parent)
                ->where('target_type', $target['type'])
                ->where('target_id', $target['id'])
                ->first()
            : null;

        if ($target['type'] === 'post') {
            $post = Post::where('post_id', $target['id'])->first();
            CommentCreated::dispatch(
                $comment,
                $target['type'],
                (string) $target['id'],
                $post,
                null,
                $parentComment
            );
        } else {
            $lesson = Lesson::where('id', $target['id'])->first();
            CommentCreated::dispatch(
                $comment,
                $target['type'],
                (string) $target['id'],
                null,
                $lesson,
                $parentComment
            );
        }

        return $this->successResponse([
            'success' => true,
            'comment' => [
                'id' => $comment->id,
                'postId' => $target['type'] === 'post' ? (int)$target['id'] : null,
                'lessonId' => $target['type'] === 'lesson' ? (int)$target['id'] : null,
                'targetType' => $target['type'],
                'targetId' => (int)$target['id'],
                'writerId' => $user->user_id,
                'writerName' => $user->learner_name,
                'writerImage' => $user->learner_image,
                'body' => $body,
                'image' => $imagePath,
                'time' => $time,
                'parent' => $parent,
                'likes' => 0,
                'isLiked' => 0,
                'child' => []
            ]
        ]);
    }

    /**
     * Delete a comment
     */
    public function destroy(Request $request)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return $this->errorResponse('Not authenticated', 401);
        }

        $commentId = (int)($request->input('commentId')); // this is the 'time' field

        if (!$commentId) {
            return $this->errorResponse('Comment ID is required', 400);
        }

        $comment = Comment::where('time', $commentId)->first();
        if (!$comment) {
            return $this->errorResponse('Comment not found', 404);
        }

        if ((string)$comment->writer_id !== (string)$user->user_id) {
            return $this->errorResponse('You can only delete your own comments', 403);
        }

        $targetType = $comment->target_type ?: 'post';
        $targetId = (int)($comment->target_id ?: $comment->post_id);

        // Count how many comments we are deleting (the comment itself + its replies)
        $childCount = Comment::where('parent', $commentId)->count();
        $totalToDelete = 1 + $childCount;

        // Delete replies first
        Comment::where('parent', $commentId)->delete();
        // Delete the comment itself
        $comment->delete();

        // Decrement target comments count
        if ($targetType === 'post') {
            $post = Post::where('post_id', $targetId)->first();
            if ($post) {
                $newCount = max(0, $post->comments - $totalToDelete);
                $post->update(['comments' => $newCount]);
            }
        } else {
            $lesson = Lesson::where('id', $targetId)->first();
            if ($lesson) {
                $newCount = max(0, (int)$lesson->comment_count - $totalToDelete);
                $lesson->update(['comment_count' => $newCount]);
            }
        }

        // Cleanup notifications (new Laravel notifications table)
        $this->deleteNotificationsForComment($commentId);
        // Also cleanup likes for this comment if any
        DB::table('comment_likes')->where('comment_id', $commentId)->delete();

        return $this->successResponse(['success' => true]);
    }

    /**
     * Like/Unlike a comment
     */
    public function like(Request $request)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return $this->errorResponse('Not authenticated', 401);
        }

        $commentId = (int)($request->input('commentId')); // the 'time' field
        if (!$commentId) {
            return $this->errorResponse('Comment ID is required', 400);
        }

        $comment = Comment::where('time', $commentId)->first();
        if (!$comment) {
            return $this->errorResponse('Comment not found', 404);
        }

        $userId = $user->user_id;
        $isLiked = DB::table('comment_likes')
            ->where('comment_id', $commentId)
            ->where('user_id', $userId)
            ->exists();

        if ($isLiked) {
            // Unlike
            DB::table('comment_likes')
                ->where('comment_id', $commentId)
                ->where('user_id', $userId)
                ->delete();
            
            $comment->decrement('likes');
            $liked = false;
        } else {
            // Like
            DB::table('comment_likes')->insert([
                'comment_id' => $commentId,
                'user_id' => $userId
            ]);
            
            $comment->increment('likes');
            $liked = true;

            if ($comment->writer_id != $userId) {
                CommentLiked::dispatch($comment, $user);
            }
        }

        return $this->successResponse([
            'success' => true,
            'isLiked' => $liked,
            'likesCount' => $comment->likes
        ]);
    }

    /**
     * Update a comment
     */
    public function update(Request $request)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return $this->errorResponse('Not authenticated', 401);
        }

        $commentId = (int)($request->input('commentId')); // this is the 'time' field
        $body = trim($request->input('body', ''));

        if (!$commentId || empty($body)) {
            return $this->errorResponse('Comment ID and body are required', 400);
        }

        $comment = Comment::where('time', $commentId)->first();
        if (!$comment) {
            return $this->errorResponse('Comment not found', 404);
        }

        if ((string)$comment->writer_id !== (string)$user->user_id) {
            return $this->errorResponse('You can only update your own comments', 403);
        }

        $comment->update(['body' => $body]);

        return $this->successResponse([
            'success' => true,
            'message' => 'Comment successfully updated'
        ]);
    }

    /**
     * Get users who liked a comment
     */
    public function likes(Request $request)
    {
        $commentId = (int)$request->query('commentId'); // comment time field
        $page = max(1, (int)$request->query('page', 1));
        $limit = max(1, min(100, (int)$request->query('limit', 20)));

        if ($commentId <= 0) {
            return $this->errorResponse('Comment ID is required', 400);
        }

        $baseQuery = DB::table('comment_likes')->where('comment_id', $commentId);
        $total = (int)$baseQuery->count();

        if ($total === 0) {
            return $this->successResponse([], 200, $this->paginate($total, $page, $limit));
        }

        $offset = ($page - 1) * $limit;
        $likes = DB::table('comment_likes')
            ->where('comment_id', $commentId)
            ->select('user_id')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $userIds = $likes->pluck('user_id')->filter()->unique()->values()->all();

        if (empty($userIds)) {
            return $this->successResponse([], 200, $this->paginate($total, $page, $limit));
        }

        $learners = DB::table('learners')
            ->select('user_id', 'learner_name', 'learner_image')
            ->whereIn('user_id', $userIds)
            ->get()
            ->keyBy('user_id');

        $data = [];
        foreach ($userIds as $uid) {
            $learner = $learners->get($uid);
            $data[] = [
                'userId' => (string)$uid,
                'userName' => $learner ? $learner->learner_name : 'Unknown User',
                'userImage' => $learner ? $learner->learner_image : null,
            ];
        }

        return $this->successResponse($data, 200, $this->paginate($total, $page, $limit));
    }

    private function deleteNotificationsForComment(int $commentId): void
    {
        \App\Services\NotificationCleanupService::forComment($commentId);
    }

    private function resolveCommentTarget(Request $request): ?array
    {
        $targetType = trim((string)$request->input('targetType', ''));
        $targetId = (int)$request->input('targetId', 0);
        $lessonId = (int)$request->input('lessonId', 0);
        $postId = (int)$request->input('postId', 0);

        if ($targetType !== '' && $targetId > 0) {
            if (!in_array($targetType, ['post', 'lesson'])) {
                return null;
            }
            return ['type' => $targetType, 'id' => $targetId];
        }

        if ($lessonId > 0) {
            return ['type' => 'lesson', 'id' => $lessonId];
        }

        if ($postId > 0) {
            return ['type' => 'post', 'id' => $postId];
        }

        return null;
    }
}
