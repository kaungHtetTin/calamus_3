<?php

namespace App\Http\Controllers;

use App\Events\CommentCreated;
use App\Models\Comment;
use App\Models\Lesson;
use App\Models\Course;
use App\Models\Study;
use App\Models\VipUser;
use App\Models\Learner;
use App\Models\UserData;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class LessonController extends Controller
{
    use ApiResponse;

    /**
     * Get lesson details
     */
    public function show(Request $request)
    {
        $lessonId = (int)$request->input('id');
        
        // Use Sanctum guard to get user if token is provided, even if route is not protected by auth middleware
        $authUser = Auth::guard('sanctum')->user();
        $uid = $authUser ? (string)$authUser->user_id : '';

        if (!$lessonId) {
            return $this->errorResponse('Missing lesson ID', 400);
        }

        // Fetch lesson details from lesson table only
        $lesson = DB::table('lessons')
            ->select('lessons.*', 'lessons.isVip')
            ->where('lessons.id', $lessonId)
            ->first();

        if (!$lesson) {
            return $this->errorResponse('Lesson not found', 404);
        }

        // Check if user has learned this lesson
        $isLearned = 0;
        if (!empty($uid)) {
            $isLearned = Study::where('user_id', $uid)
                ->where('lesson_id', $lessonId)
                ->exists() ? 1 : 0;
        }

        $isLiked = 0;
        if (!empty($uid)) {
            $isLiked = DB::table('lesson_likes')
                ->where('lesson_id', $lessonId)
                ->where('user_id', $uid)
                ->exists() ? 1 : 0;
        }

        $lessonIsVip = (int)$lesson->isVip;
        $categoryId = (int)$lesson->category_id;

        // Fetch Course Details using the lesson's category_id
        $course = DB::table('lessons_categories')
            ->join('courses', 'courses.course_id', '=', 'lessons_categories.course_id')
            ->where('lessons_categories.id', $categoryId)
            ->select(
                'courses.course_id',
                'courses.title as course_title',
                'courses.teacher_id',
                'courses.is_vip'
            )
            ->first();

        if (!$course) {
            return $this->errorResponse('Course not found', 404);
        }

        $courseId = (int)$course->course_id;
        $courseIsVip = (int)$course->is_vip;

        // Check VIP access
        $hasVipAccess = false;
        if (!empty($uid)) {
            $hasVipAccess = VipUser::where('course_id', $courseId)
                ->where('user_id', $uid)
                ->exists();
        }

        // Access Logic
        $canAccess = false;
        if ($courseIsVip === 0) {
            $canAccess = true;
        } else {
            if ($lessonIsVip === 0) {
                $canAccess = true;
            } else {
                if ($hasVipAccess) {
                    $canAccess = true;
                }
            }
        }

        if (!$canAccess) {
            return $this->errorResponse('VIP Content - Subscription required', 403);
        }

        DB::table('lessons')->where('id', $lessonId)->increment('view_count');
        $lesson->view_count = (int) ($lesson->view_count ?? 0) + 1;

        $isVideo = (int)$lesson->isVideo;
        $resolvedLink = $isVideo === 1
            ? $lesson->link
            : ($lesson->document_link ?? null);

        return $this->successResponse([
            'lesson' => [
                'id' => (int)$lesson->id,
                'title' => $this->ensureUtf8($lesson->title),
                'description' => $this->ensureUtf8($lesson->description ?? ''),
                'isVideo' => $isVideo,
                'isVip' => $lessonIsVip,
                'duration' => (int)$lesson->duration,
                'link' => $resolvedLink,
                'vimeo' => (int)$lesson->isVideo === 1 ? $lesson->link : null,
                'documentUrl' => (int)$lesson->isVideo === 1 ? null : $resolvedLink,
                'viewCount' => (int)($lesson->view_count ?? 0),
                'likeCount' => (int)($lesson->like_count ?? 0),
                'isLiked' => $isLiked,
                'comments' => (int)($lesson->comment_count ?? 0),
                'shareCount' => (int)($lesson->share_count ?? 0),
                'thumbnail' => $lesson->thumbnail,
                'learned' => $isLearned,
                'hasAccess' => ($courseIsVip === 0) ? true : ($lessonIsVip === 0 || $hasVipAccess),
            ]
        ]);
    }

    /**
     * Get download URL for a lesson.
     */
    public function download(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->errorResponse('Not authenticated', 401);
        }

        $lessonId = (int)$request->input('id');
        $channel = strtolower(trim((string)($request->input('major') ?? $request->input('category') ?? '')));

        if ($lessonId <= 0) {
            return $this->errorResponse('Missing lesson ID', 400);
        }

        $uid = (string)($user->user_id ?? $user->id ?? '');
        if ($uid === '') {
            return $this->errorResponse('Invalid authenticated user', 401);
        }

        $lesson = Lesson::find($lessonId);

        if (!$lesson) {
            return $this->errorResponse('Lesson not found', 404);
        }

        if (empty($lesson->download_url)) {
            return $this->errorResponse('Download is not available for this lesson', 404);
        }

        $course = DB::table('lessons_categories')
            ->join('courses', 'courses.course_id', '=', 'lessons_categories.course_id')
            ->where('lessons_categories.id', (int)$lesson->category_id)
            ->select('courses.course_id', 'courses.is_vip', 'lessons_categories.major as category_major')
            ->first();

        if (!$course) {
            return $this->errorResponse('Course not found', 404);
        }

        if ($channel === '') {
            $channel = strtolower(trim((string)($course->category_major ?? '')));
        }
        if ($channel === '') {
            return $this->errorResponse('Missing major', 400);
        }

        $isVipUser = UserData::where('major', $channel)
            ->where('user_id', $uid)->where('is_vip', 1)->first();
          
        if (!$isVipUser) {
            return $this->errorResponse('VIP account required for download', 403);
        }

        $lessonIsVip = (int)$lesson->isVip;

        if ($lessonIsVip === 1) {
            $isPurchased = VipUser::where('course_id', (int)$course->course_id)
                ->where('user_id', $uid)
                ->exists();
            if (!$isPurchased) {
                return $this->errorResponse('Course purchase required for VIP lesson download', 403);
            }
        }

        return $this->successResponse([
            'lessonId' => (int)$lesson->id,
            'title' => $this->ensureUtf8($lesson->title ?? ''),
            'downloadUrl' => $lesson->download_url,
            'hasAccess' => true,
        ]);
    }

    /**
     * Render mini-program Vimeo player with access management.
     */
    public function vimeoPlayer(Request $request)
    {
        $lessonId = (int)($request->input('lessonId') ?? $request->input('id'));
        if ($lessonId <= 0) {
            return $this->renderVimeoPlayerError('Missing lesson ID', 400);
        }

        $lesson = DB::table('lessons')
            ->select('id', 'date', 'category_id', 'isVip', 'isVideo', 'link')
            ->where('id', $lessonId)
            ->first();

        if (!$lesson) {
            return $this->renderVimeoPlayerError('Lesson not found', 404);
        }

        if ((int)$lesson->isVideo !== 1 || empty($lesson->link)) {
            return $this->renderVimeoPlayerError('This lesson does not have a playable video', 400);
        }

        $token = trim((string)$request->bearerToken());
        if ($token === '') {
            $token = trim((string)$request->input('token', ''));
        }
        if ($token === '') {
            $token = trim((string)$request->input('authToken', ''));
        }

        $uid = $this->resolveUserIdFromAnyToken($token);

        $course = DB::table('lessons_categories')
            ->join('courses', 'courses.course_id', '=', 'lessons_categories.course_id')
            ->where('lessons_categories.id', (int)$lesson->category_id)
            ->select('courses.course_id', 'courses.is_vip')
            ->first();

        if (!$course) {
            return $this->renderVimeoPlayerError('Course not found', 404);
        }

        $lessonIsVip = (int)$lesson->isVip;
        $courseIsVip = (int)$course->is_vip;
        $hasVipAccess = false;
        
        // Access flow:
        // 1) Non-VIP lesson => allow
        // 2) VIP lesson in free course => allow
        // 3) VIP lesson in VIP course => require valid token + purchased course
        if ($lessonIsVip !== 0 && $courseIsVip !== 0) {
            if ($uid === '') {
                if ($token !== '') {
                    return $this->renderVimeoPlayerError('Invalid token', 401);
                }
                return $this->renderVimeoPlayerError('Login required to access this content', 401);
            }
            $hasVipAccess = VipUser::where('course_id', (int)$course->course_id)
                ->where('user_id', $uid)
                ->exists();
            if (!$hasVipAccess) {
                return $this->renderVimeoPlayerError('VIP Content - Subscription required', 403);
            }
        }

        return view('mini-program.vimeo-player.player', [
            'lesson' => $lesson,
        ]);
    }

    private function renderVimeoPlayerError(string $message, int $statusCode)
    {
        return response()->view('mini-program.vimeo-player.error', [
            'title' => 'Unable to open player',
            'message' => $message,
            'statusCode' => $statusCode,
        ], $statusCode);
    }

    private function resolveUserIdFromAnyToken(string $token): string
    {
        $normalized = trim($token);
        if ($normalized === '') {
            return '';
        }

        $accessToken = PersonalAccessToken::findToken($normalized);
        if ($accessToken && method_exists($accessToken->tokenable, 'getAttribute')) {
            return (string)($accessToken->tokenable->getAttribute('user_id') ?? '');
        }

        $legacyUser = Learner::query()
            ->select('user_id')
            ->where('auth_token', $normalized)
            ->orWhere('auth_token_mobile', $normalized)
            ->first();

        return $legacyUser ? (string)$legacyUser->user_id : '';
    }

    public function like(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->errorResponse('Not authenticated', 401);
        }

        $lessonId = (int)($request->input('lessonId') ?? $request->input('id'));
        if ($lessonId <= 0) {
            return $this->errorResponse('Invalid lesson ID', 400);
        }

        $uid = (string)($user->user_id ?? $user->id ?? '');
        if ($uid === '') {
            return $this->errorResponse('Invalid authenticated user', 401);
        }

        $lesson = Lesson::where('id', $lessonId)->first();
        if (!$lesson) {
            return $this->errorResponse('Lesson not found', 404);
        }

        $result = DB::transaction(function () use ($lessonId, $uid) {
            $alreadyLiked = DB::table('lesson_likes')
                ->where('lesson_id', $lessonId)
                ->where('user_id', $uid)
                ->exists();

            if ($alreadyLiked) {
                DB::table('lesson_likes')
                    ->where('lesson_id', $lessonId)
                    ->where('user_id', $uid)
                    ->delete();

                DB::table('lessons')->where('id', $lessonId)->decrement('like_count');

                $count = (int)DB::table('lessons')->where('id', $lessonId)->value('like_count');
                if ($count < 0) {
                    $count = 0;
                    DB::table('lessons')->where('id', $lessonId)->update(['like_count' => 0]);
                }

                return ['isLiked' => false, 'count' => $count];
            }

            DB::table('lesson_likes')->updateOrInsert(
                ['lesson_id' => $lessonId, 'user_id' => $uid],
                ['created_at' => now(), 'updated_at' => now()]
            );

            DB::table('lessons')->where('id', $lessonId)->increment('like_count');

            return [
                'isLiked' => true,
                'count' => (int)DB::table('lessons')->where('id', $lessonId)->value('like_count'),
            ];
        });

        return $this->successResponse([
            'success' => true,
            'isLiked' => $result['isLiked'],
            'count' => $result['count'],
        ]);
    }

    public function share(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->errorResponse('Not authenticated', 401);
        }

        $lessonId = (int)($request->input('lessonId') ?? $request->input('id'));
        if ($lessonId <= 0) {
            return $this->errorResponse('Invalid lesson ID', 400);
        }

        $lesson = Lesson::where('id', $lessonId)->first();
        if (!$lesson) {
            return $this->errorResponse('Lesson not found', 404);
        }

        DB::table('lessons')->where('id', $lessonId)->increment('share_count');

        // Determine if this lesson belongs to a "main" course or an "additional" course
        $course = DB::table('lessons_categories')
            ->join('courses', 'courses.course_id', '=', 'lessons_categories.course_id')
            ->where('lessons_categories.id', (int)$lesson->category_id)
            ->select('courses.course_id', 'courses.major')
            ->first();

        $shareLink = '';
        if ($course) {
            $isAdditionalCourse = strtolower(trim((string)$course->major)) === 'not';
            if ($isAdditionalCourse) {
                $shareLink = "http://www.calamuseducation.com/calamus/watch/{$lessonId}";
            } else {
                $courseId = (int)$course->course_id;
                $shareLink = "https://www.calamuseducation.com/calamus/course/{$courseId}/lesson/{$lessonId}";
            }
        }

        return $this->successResponse([
            'success' => true,
            'count' => (int)DB::table('lessons')->where('id', $lessonId)->value('share_count'),
            'link' => $shareLink,
        ]);
    }

    public function comment(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->errorResponse('Not authenticated', 401);
        }

        $lessonId = (int)($request->input('lessonId') ?? $request->input('id'));
        if ($lessonId <= 0) {
            return $this->errorResponse('Invalid lesson ID', 400);
        }

        $lesson = Lesson::where('id', $lessonId)->first();
        if (!$lesson) {
            return $this->errorResponse('Lesson not found', 404);
        }

        $body = trim((string)$request->input('body', ''));
        $image = (string)$request->input('image', '');
        $parent = (int)$request->input('parent', 0);

        if ($body === '' && $image === '') {
            return $this->errorResponse('Body or image is required', 400);
        }

        if ($parent > 0) {
            $parentExists = Comment::where('time', $parent)
                ->where('target_type', 'lesson')
                ->where('target_id', $lessonId)
                ->exists();

            if (!$parentExists) {
                return $this->errorResponse('Parent comment not found for this lesson', 404);
            }
        }

        $time = round(microtime(true) * 1000);
        $imagePath = '';

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

            $fileName = $time . '_' . $user->user_id . '.' . $ext;
            $path = 'comments';
            \Illuminate\Support\Facades\Storage::disk('uploads')->put($path . '/' . $fileName, $imageData);
            $imagePath = env('APP_URL') . \Illuminate\Support\Facades\Storage::disk('uploads')->url($path . '/' . $fileName);
        }

        $comment = new Comment();
        $comment->post_id = null;
        $comment->target_type = 'lesson';
        $comment->target_id = $lessonId;
        $comment->writer_id = $user->user_id;
        $comment->body = $body;
        $comment->image = $imagePath;
        $comment->time = $time;
        $comment->parent = $parent;
        $comment->likes = 0;
        $comment->save();

        DB::table('lessons')->where('id', $lessonId)->increment('comment_count');

        $parentComment = $parent > 0
            ? Comment::where('time', $parent)
                ->where('target_type', 'lesson')
                ->where('target_id', $lessonId)
                ->first()
            : null;

        CommentCreated::dispatch(
            $comment,
            'lesson',
            (string)$lessonId,
            null,
            $lesson,
            $parentComment
        );

        return $this->successResponse([
            'success' => true,
            'comment' => [
                'id' => $comment->id,
                'postId' => null,
                'lessonId' => (int)$lessonId,
                'targetType' => 'lesson',
                'targetId' => (int)$lessonId,
                'writerId' => $user->user_id,
                'writerName' => $user->learner_name ?? null,
                'writerImage' => $user->learner_image ?? null,
                'body' => $body,
                'image' => $imagePath,
                'time' => $time,
                'parent' => $parent,
                'likes' => 0,
                'isLiked' => 0,
                'child' => [],
            ],
        ]);
    }
    
    /**
     * Mark lesson as learned
     */
    public function markLearned(Request $request)
    {
        $user = Auth::user(); // Handled by TokenAuthMiddleware
        
        if (!$user) {
            return $this->errorResponse('Not authenticated', 401);
        }

        $lessonId = (int)$request->input('lessonId');
        if ($lessonId <= 0) {
            return $this->errorResponse('Invalid lesson ID', 400);
        }

        $lessonExists = Lesson::where('id', $lessonId)->exists();
        if (!$lessonExists) {
            return $this->errorResponse('Lesson not found', 404);
        }

        // Logic from Study::check
        $study = Study::where('user_id', $user->user_id)
            ->where('lesson_id', $lessonId)
            ->first();

        if ($study) {
            // Update frequent
            $study->frequent = $study->frequent + 1;
            $study->save();
        } else {
            // Add new
            $newStudy = new Study();
            $newStudy->user_id = $user->user_id;
            $newStudy->learner_id = $user->user_id; // Set for legacy compatibility
            $newStudy->lesson_id = $lessonId;
            $newStudy->frequent = 1;
            $newStudy->exercise_mark = 0;
            $newStudy->save();
        }

        return $this->successResponse(null, 200)->setData([
            'success' => true,
            'message' => 'Lesson marked as learned'
        ]);
    }

    private function ensureUtf8($str)
    {
        if ($str === null || $str === '') return (string)$str;
        if (!mb_check_encoding($str, 'UTF-8')) return mb_convert_encoding($str, 'UTF-8', 'auto');
        return $str;
    }
}
