<?php

namespace App\Http\Controllers;

use App\Models\Learner;
use App\Models\Course;
use App\Models\VipUser;
use App\Models\Study;
use App\Models\Post;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Services\AccountDeletionService;

class UserController extends Controller
{
    use ApiResponse;

    /**
     * Get Public Profile
     */
    public function profile(Request $request)
    {
        $userId = $request->input('id');
        $page = (int)$request->input('page', 1);
        $tab = $request->input('tab', 'posts');
        $limit = 10;

        if (empty($userId)) {
            return $this->errorResponse('User ID is required', 400);
        }

        // Find user by numeric primary key or by user_id
        $user = null;
        if (ctype_digit($userId)) {
            $user = Learner::where('user_id', $userId)->first();
        } else {
            $user = Learner::where('user_id', $userId)->first();
        }

        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }

        $uid = $user->user_id;

        if ($tab === 'shared') {
            $posts = DB::table('posts as sp')
                ->leftJoin('posts as pp', 'pp.post_id', '=', 'sp.share')
                ->leftJoin('learners as ppu', 'ppu.user_id', '=', 'pp.user_id')
                ->where('sp.user_id', $uid)
                ->where('sp.hide', 0)
                ->where('sp.share', '>', 0)
                ->orderBy('sp.post_id', 'desc')
                ->paginate($limit, [
                    'sp.post_id as post_id',
                    'sp.user_id as user_id',
                    'sp.post_like as post_like',
                    'sp.comments as comments',
                    'sp.share_count as share_count',
                    'sp.view_count as view_count',
                    'sp.major as major',
                    'sp.share as share',

                    'pp.post_id as parent_post_id',
                    'pp.body as parent_body',
                    'pp.image as parent_image',
                    'pp.user_id as parent_user_id',
                    'pp.post_like as parent_post_like',
                    'pp.comments as parent_comments',
                    'pp.share_count as parent_share_count',
                    'pp.view_count as parent_view_count',
                    'pp.major as parent_major',

                    'ppu.learner_name as parent_user_name',
                    'ppu.learner_image as parent_user_image',
                ], 'page', $page);

            $formattedPosts = $posts->map(function ($row) use ($user) {
                return [
                    'postId' => (int)$row->post_id,
                    'userId' => (string)$user->user_id,
                    'userName' => $this->ensureUtf8($user->learner_name),
                    'userImage' => $user->learner_image,
                    'postLikes' => (int)$row->post_like,
                    'comments' => (int)$row->comments,
                    'shareCount' => (int)$row->share_count,
                    'viewCount' => (int)$row->view_count,
                    'category' => $row->major ?? null,
                    'share' => (int)$row->share,
                    'parentPost' => $row->parent_post_id ? [
                        'postId' => (int)$row->parent_post_id,
                        'body' => $this->ensureUtf8($row->parent_body),
                        'postImage' => $row->parent_image ?? '',
                        'userId' => (string)($row->parent_user_id ?? ''),
                        'userName' => $this->ensureUtf8($row->parent_user_name ?? 'Anonymous'),
                        'userImage' => $row->parent_user_image ?? 'https://www.calamuseducation.com/uploads/placeholder.png',
                        'postLikes' => (int)($row->parent_post_like ?? 0),
                        'comments' => (int)($row->parent_comments ?? 0),
                        'shareCount' => (int)($row->parent_share_count ?? 0),
                        'viewCount' => (int)($row->parent_view_count ?? 0),
                        'category' => $row->parent_major ?? null,
                    ] : null,
                ];
            });
        } else {
            // For "posts" tab, never include shared posts.
            $posts = Post::where('user_id', $uid)
                ->where('hide', 0)
                ->where('share', 0)
                ->orderBy('post_id', 'desc')
                ->paginate($limit, ['*'], 'page', $page);

            $formattedPosts = $posts->map(function ($post) use ($user) {
                return [
                    'postId' => (int)$post->post_id,
                    'body' => $this->ensureUtf8($post->body),
                    'postImage' => $post->image,
                    'userId' => (string)$user->user_id,
                    'userName' => $this->ensureUtf8($user->learner_name),
                    'userImage' => $user->learner_image,
                    'postLikes' => (int)$post->post_like,
                    'comments' => (int)$post->comments,
                    'shareCount' => (int)$post->share_count,
                    'viewCount' => (int)$post->view_count,
                ];
            });
        }

        $profileData = [
            'id' => (int)$user->id,
            'userId' => (string)$user->user_id,
            'name' => $this->ensureUtf8($user->learner_name),
            'email' => $user->learner_email,
            'gender' => $user->gender,
            'phone' => $user->learner_phone,
            'birthday' => [
                'day' => $user->bd_day !== null ? (int)$user->bd_day : null,
                'month' => $user->bd_month !== null ? (int)$user->bd_month : null,
                'year' => $user->bd_year !== null ? (int)$user->bd_year : null,
            ],
            'image' => $user->learner_image,
            'coverImage' => $user->cover_image,
            'bio' => $this->ensureUtf8($user->bio),
            'work' => $this->ensureUtf8($user->work),
            'education' => $this->ensureUtf8($user->education),
            'region' => $this->ensureUtf8($user->region),
            'posts' => $formattedPosts,
            'totalPosts' => $posts->total(),
            'page' => $page
        ];

        return $this->successResponse(
            $profileData,
            200,
            $this->paginate($posts->total(), $page, $limit)
        );
    }

    /**
     * Get My Learning Progress
     */
    public function myLearning(Request $request)
    {
        $user = $request->user(); // Auth middleware required
        if (!$user) {
            return $this->errorResponse('Not authenticated', 401);
        }

        $uid = $user->user_id;

        // Get Purchased Courses
        $purchasedCourses = VipUser::where('user_id', $uid)
            ->join('courses', 'courses.course_id', '=', 'vipusers.course_id')
            ->join('teachers', 'teachers.id', '=', 'courses.teacher_id')
            ->where('courses.active', 1)
            ->orderBy('courses.course_id', 'asc')
            ->orderBy('courses.major', 'asc')
            ->select('courses.*', 'teachers.name as teacher_name', 'teachers.profile as teacher_profile')
            ->get();
        
        $purchasedIds = $purchasedCourses->pluck('course_id')->toArray();

        // Get Free Courses
        $freeCourses = Course::join('teachers', 'teachers.id', '=', 'courses.teacher_id')
            ->where(function($q) {
                $q->where('is_vip', 0)->orWhereNull('is_vip');
            })
            ->whereNotIn('course_id', $purchasedIds)
            ->select('courses.*', 'teachers.name as teacher_name', 'teachers.profile as teacher_profile')
            ->orderBy('courses.major', 'asc')
            ->orderBy('courses.course_id', 'asc')
            ->get();

        // Get Progress Counts
        $studyCounts = Study::join('lessons', 'lessons.id', '=', 'studies.lesson_id')
            ->join('lessons_categories', 'lessons_categories.id', '=', 'lessons.category_id')
            ->where('studies.user_id', $uid)
            ->select('lessons_categories.course_id', DB::raw('count(studies.id) as count'))
            ->groupBy('lessons_categories.course_id')
            ->pluck('count', 'course_id');

        $formatCourse = function($course, $isPurchased) use ($studyCounts) {
            $courseId = $course->course_id;
            $total = (int)$course->lessons_count;
            $learned = $studyCounts[$courseId] ?? 0;
            $progress = $total > 0 ? round(($learned / $total) * 100) : 0;
            $enrolled = VipUser::where('course_id', $courseId)->count(); // Can optimize by batching if slow

            return [
                'id' => (int)$courseId,
                'title' => $this->ensureUtf8($course->title),
                'description' => $this->ensureUtf8($course->description),
                'duration' => (int)$course->duration,
                'rating' => (float)$course->rating,
                'coverUrl' => $course->cover_url,
                'webCover' => $course->web_cover,
                'backgroundColor' => $course->background_color,
                'fee' => (int)$course->fee,
                'major' => $course->major,
                'lessonsCount' => $total,
                'instructor' => $this->ensureUtf8($course->teacher_name),
                'instructorImage' => $course->teacher_profile,
                'enrolledStudents' => $enrolled,
                'isPurchased' => $isPurchased,
                'progress' => $progress,
                'learnedCount' => $learned
            ];
        };

        $myCourses = $purchasedCourses->map(fn($c) => $formatCourse($c, true));
        $otherCourses = $freeCourses->map(fn($c) => $formatCourse($c, false));

        return $this->successResponse([
            'myCourses' => $myCourses,
            'freeCourses' => $otherCourses
        ], 200, [
            'totalMyCourses' => $myCourses->count(),
            'totalFreeCourses' => $otherCourses->count()
        ]);
    }

    /**
     * Delete Account
     */
    public function deleteAccount(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->errorResponse('Not authenticated', 401);
        }

        $password = $request->input('password');
        if (empty($password)) {
            return $this->errorResponse('Password is required to delete account', 400);
        }

        if (!Hash::check($password, $user->password)) {
            return $this->errorResponse('Incorrect password', 400);
        }

        try {
            AccountDeletionService::deleteAccount($user);

            return $this->successResponse(['message' => 'Account deleted successfully']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete account: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update User Profile
     */
    public function update(Request $request)
    {
        $user = Auth::user(); // Handled by TokenAuthMiddleware

        if (!$user) {
            return $this->errorResponse('Not authenticated', 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'bio' => 'nullable|string',
            'work' => 'nullable|string',
            'education' => 'nullable|string',
            'region' => 'nullable|string',
            'gender' => 'nullable|string|in:male,female,other',
            'birthday' => 'nullable|array',
            'birthday.day' => 'nullable|integer|min:1|max:31',
            'birthday.month' => 'nullable|integer|min:1|max:12',
            'birthday.year' => 'nullable|integer|min:1900|max:2100',
            'birthdayDay' => 'nullable|integer|min:1|max:31',
            'birthdayMonth' => 'nullable|integer|min:1|max:12',
            'birthdayYear' => 'nullable|integer|min:1900|max:2100',
            'profileImage' => 'nullable|image|max:5120', // 5MB
            'coverImage' => 'nullable|image|max:5120', // 5MB
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 400);
        }

        // Update basic fields
        $user->learner_name = $request->input('name');
        if ($request->has('bio')) $user->bio = $request->input('bio');
        if ($request->has('work')) $user->work = $request->input('work');
        if ($request->has('education')) $user->education = $request->input('education');
        if ($request->has('region')) $user->region = $request->input('region');
        if ($request->has('gender')) $user->gender = $request->input('gender');

        $birthday = $request->input('birthday', []);
        if ($request->has('birthday.day') || $request->has('birthdayDay')) {
            $user->bd_day = (int)($birthday['day'] ?? $request->input('birthdayDay'));
        }
        if ($request->has('birthday.month') || $request->has('birthdayMonth')) {
            $user->bd_month = (int)($birthday['month'] ?? $request->input('birthdayMonth'));
        }
        if ($request->has('birthday.year') || $request->has('birthdayYear')) {
            $user->bd_year = (int)($birthday['year'] ?? $request->input('birthdayYear'));
        }

        // Handle File Uploads
        if ($request->hasFile('profileImage')) {
            $path = $this->uploadImage($request->file('profileImage'), 'users');
            if ($path) {
                $user->learner_image = $path;
            } else {
                return $this->errorResponse('Failed to upload profile image', 500);
            }
        }

        if ($request->hasFile('coverImage')) {
            $path = $this->uploadImage($request->file('coverImage'), 'users');
            if ($path) {
                $user->cover_image = $path;
            } else {
                return $this->errorResponse('Failed to upload cover image', 500);
            }
        }

        $user->save();

        return $this->successResponse([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => (int)$user->id,
                'name' => $user->learner_name,
                'image' => $user->learner_image,
                'coverImage' => $user->cover_image,
                'bio' => $user->bio,
                'gender' => $user->gender,
                'birthday' => [
                    'day' => $user->bd_day !== null ? (int)$user->bd_day : null,
                    'month' => $user->bd_month !== null ? (int)$user->bd_month : null,
                    'year' => $user->bd_year !== null ? (int)$user->bd_year : null,
                ],
            ]
        ]);
    }

    /**
     * Update Learner Bio
     */
    public function updateBio(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return $this->errorResponse('Not authenticated', 401);
        }

        $validator = Validator::make($request->all(), [
            'bio' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 400);
        }

        $user->bio = $request->input('bio');
        $user->save();

        return $this->successResponse([
            'success' => true,
            'message' => 'Bio updated successfully',
            'bio' => $user->bio,
        ]);
    }

    /**
     * Update Cover Photo
     */
    public function updateCoverPhoto(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return $this->errorResponse('Not authenticated', 401);
        }

        $validator = Validator::make($request->all(), [
            'coverImage' => 'required|image|max:5120',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 400);
        }

        $path = $this->uploadImage($request->file('coverImage'), 'users');
        if (!$path) {
            return $this->errorResponse('Failed to upload cover image', 500);
        }

        $user->cover_image = $path;
        $user->save();

        return $this->successResponse([
            'success' => true,
            'message' => 'Cover photo updated successfully',
            'coverImage' => $user->cover_image,
        ]);
    }

    /**
     * Change Password
     */
    public function changePassword(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return $this->errorResponse('Not authenticated', 401);
        }

        $validator = Validator::make($request->all(), [
            'currentPassword' => 'required|string',
            'newPassword' => 'required|string|max:256',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 400);
        }

        if (!Hash::check($request->input('currentPassword'), $user->password)) {
            return $this->errorResponse('Incorrect current password', 400);
        }

        $user->password = Hash::make($request->input('newPassword'));
        $user->save();

        return $this->successResponse(['success' => true, 'message' => 'Password changed successfully']);
    }

    /**
     * Update Credential Information (Email and Phone)
     */
    public function updateCredentials(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return $this->errorResponse('Not authenticated', 401);
        }

        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
            'email' => 'nullable|email|max:100|unique:learners,learner_email,' . $user->user_id . ',user_id',
            'phone' => 'nullable|string|max:32|unique:learners,learner_phone,' . $user->user_id . ',user_id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 400);
        }

        if (!Hash::check($request->input('password'), $user->password)) {
            return $this->errorResponse('Incorrect password', 400);
        }

        $updated = false;
        if ($request->has('email')) {
            $newEmail = trim((string)$request->input('email'));
            if ($newEmail !== $user->learner_email) {
                $exists = Learner::where('learner_email', $newEmail)
                    ->where('user_id', '!=', $user->user_id)
                    ->exists();
                if ($exists) {
                    return $this->errorResponse('This email is already associated with another account.', 400);
                }
                $user->learner_email = $newEmail;
                $updated = true;
            }
        }

        if ($request->has('phone')) {
            $newPhone = trim((string)$request->input('phone'));
            if ($newPhone !== $user->learner_phone) {
                $exists = Learner::where('learner_phone', $newPhone)
                    ->where('user_id', '!=', $user->user_id)
                    ->exists();
                if ($exists) {
                    return $this->errorResponse('This phone number is already associated with another account.', 400);
                }
                $user->learner_phone = $newPhone;
                $updated = true;
            }
        }

        if ($updated) {
            $user->save();
        }

        return $this->successResponse([
            'success' => true,
            'message' => 'Credentials updated successfully',
            'user' => [
                'email' => $user->learner_email,
                'phone' => $user->learner_phone,
            ]
        ]);
    }

    private function uploadImage($file, $folder)
    {
        $filename = uniqid() . '.' . $file->getClientOriginalExtension();
        $fullPath = \Illuminate\Support\Facades\Storage::disk('uploads')->putFileAs($folder, $file, $filename);
        return env('APP_URL')  . \Illuminate\Support\Facades\Storage::disk('uploads')->url($fullPath);
    }

    private function ensureUtf8($string)
    {
        return mb_convert_encoding($string ?? '', 'UTF-8', 'UTF-8');
    }
}
