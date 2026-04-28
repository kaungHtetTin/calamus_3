<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Rating;
use App\Models\VipUser;
use App\Models\Study;
use App\Models\Teacher;
use App\Models\Certificate;
use App\Models\Learner;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use App\Models\Language;

class CourseController extends Controller
{
    use ApiResponse;

    /**
     * Get all courses
     */
    public function index(Request $request)
    {
        $query = Course::with('teacher')->where('active', 1);

        if ($request->has('major')) {
            $major = $request->input('major');
            $query->where('major', $major);
        } else {
            $query->where('major', '!=', 'not');
        }

        $courses = $query->get();

        $formattedCourses = $courses->map(function ($course) {
            $enrolledStudents = VipUser::where('course_id', $course->course_id)->count();
            return [
                'id' => (int)$course->course_id,
                'title' => $this->ensureUtf8($course->title),
                'description' => $this->ensureUtf8($course->description),
                'duration' => (int)$course->duration,
                'rating' => (float)$course->rating,
                'details' => $this->ensureUtf8($course->details),
                'coverUrl' => $course->cover_url,
                'webCover' => $course->web_cover,
                'backgroundColor' => $course->background_color,
                'fee' => (int)$course->fee,
                'major' => $course->major,
                'lessonsCount' => (int)$course->lessons_count,
                'instructor' => $this->ensureUtf8($course->teacher->name ?? ''),
                'instructorId' => (int)$course->teacher_id,
                'instructorImage' => $course->teacher->profile ?? null,
                'enrolledStudents' => $enrolledStudents
            ];
        });

        return $this->successResponse($formattedCourses, 200, ['total' => $formattedCourses->count()]);
    }

    /**
     * Get featured courses (Top rated)
     */
    public function featured()
    {
        // Legacy: Top 5 by rating
        $courses = Course::with('teacher')
            ->where('active', 1)
            ->orderBy('rating', 'desc')
            ->limit(5)
            ->get();

        $formattedCourses = $courses->map(function ($course) {
            $enrolledStudents = VipUser::where('course_id', $course->course_id)->count();

            return [
                'id' => (int)$course->course_id,
                'title' => $this->ensureUtf8($course->title),
                'description' => $this->ensureUtf8($course->description),
                'duration' => (int)$course->duration,
                'rating' => (float)$course->rating,
                'coverUrl' => $course->cover_url,
                'webCover' => $course->web_cover,
                'backgroundColor' => $course->background_color,
                'fee' => (int)$course->fee,
                'major' => $course->major,
                'lessonsCount' => (int)$course->lessons_count,
                'instructor' => $this->ensureUtf8($course->teacher->name ?? ''),
                'instructorId' => (int)$course->teacher_id,
                'instructorImage' => $course->teacher->profile ?? null,
                'enrolledStudents' => $enrolledStudents
            ];
        });

        return $this->successResponse($formattedCourses, 200, ['total' => $formattedCourses->count()]);
    }

    /**
     * Get new courses (Latest)
     */
    public function new()
    {
        // Legacy: Top 5 by course_id DESC
        $courses = Course::with('teacher')
            ->where('active', 1)
            ->orderBy('course_id', 'desc')
            ->limit(5)
            ->get();

        $formattedCourses = $courses->map(function ($course) {
            $enrolledStudents = VipUser::where('course_id', $course->course_id)->count();

            return [
                'id' => (int)$course->course_id,
                'title' => $this->ensureUtf8($course->title),
                'description' => $this->ensureUtf8($course->description),
                'duration' => (int)$course->duration,
                'rating' => (float)$course->rating,
                'coverUrl' => $course->cover_url,
                'webCover' => $course->web_cover,
                'backgroundColor' => $course->background_color,
                'fee' => (int)$course->fee,
                'major' => $course->major,
                'lessonsCount' => (int)$course->lessons_count,
                'instructor' => $this->ensureUtf8($course->teacher->name ?? ''),
                'instructorId' => (int)$course->teacher_id,
                'instructorImage' => $course->teacher->profile ?? null,
                'enrolledStudents' => $enrolledStudents
            ];
        });

        return $this->successResponse($formattedCourses, 200, ['total' => $formattedCourses->count()]);
    }

    /**
     * Get course details
     */
    public function show(Request $request)
    {
        $courseId = (int)$request->input('id');
        $authUser = auth('sanctum')->user();
        $uid = $authUser ? (string)$authUser->user_id : '';

        if ($courseId <= 0) {
            return $this->errorResponse('Invalid course ID', 400);
        }

        $course = Course::with('teacher')->where('course_id', $courseId)->first();

        if (!$course) {
            return $this->errorResponse('Course not found', 404);
        }

        $enrolledStudents = VipUser::where('course_id', $courseId)->count();

        $totalDuration = DB::table('lessons')
            ->join('lessons_categories', 'lessons_categories.id', '=', 'lessons.category_id')
            ->where('lessons_categories.course_id', $courseId)
            ->sum('duration');

        $courseIsVip = (int)$course->is_vip;
        $hasVipAccess = false;
        if (!empty($uid)) {
            $hasVipAccess = VipUser::where('course_id', $courseId)
                ->where('user_id', $uid)
                ->exists();
        }

        $progress = 0;
        $learnedCount = 0;
        if (!empty($uid)) {
            $learnedCount = DB::table('studies')
                ->join('lessons', 'lessons.id', '=', 'studies.lesson_id')
                ->join('lessons_categories', 'lessons_categories.id', '=', 'lessons.category_id')
                ->where('lessons_categories.course_id', $courseId)
                ->where('studies.user_id', $uid)
                ->count();
            
            $totalLessons = (int)$course->lessons_count;
            if ($totalLessons > 0) {
                $progress = round(($learnedCount / $totalLessons) * 100);
            }
        }

        // Fetch user rating if authenticated
        $userRating = null;
        if (!empty($uid)) {
            $rating = DB::table('ratings')
                ->where('course_id', $courseId)
                ->where('user_id', $uid)
                ->first();

            if ($rating) {
                $userRating = [
                    'id' => (int)$rating->id,
                    'star' => (int)$rating->star,
                    'review' => $this->ensureUtf8($rating->review),
                    'time' => (int)$rating->time,
                    'formattedTime' => \Carbon\Carbon::createFromTimestampMs($rating->time)->diffForHumans()
                ];
            }
        }

        $data = [
            'id' => (int)$course->course_id,
            'title' => $this->ensureUtf8($course->title),
            'description' => $this->ensureUtf8($course->description),
            'details'=> $this->ensureUtf8($course->details),
            'duration' => (int)$course->duration,
            'totalDuration' => (int)$totalDuration,
            'rating' => (float)$course->rating,
            'coverUrl' => $course->cover_url,
            'webCover' => $course->web_cover,
            'backgroundColor' => $course->background_color,
            'fee' => (int)$course->fee,
            'major' => $course->major,
            'lessonsCount' => (int)$course->lessons_count,
            'instructor' => $this->ensureUtf8($course->teacher->name ?? ''),
            'instructorId' => (int)$course->teacher_id,
            'instructorImage' => $course->teacher->profile ?? null,
            'enrolledStudents' => $enrolledStudents,
            'hasVipAccess' => $hasVipAccess,
            'progress' => $progress,
            'learnedCount' => $learnedCount,
            'userRating' => $userRating
        ];

        return $this->successResponse($data);
    }

    public function curriculum(Request $request)
    {
        $courseId = (int)$request->input('courseId');
        $authUser = auth('sanctum')->user();
        $uid = $authUser ? (string)$authUser->user_id : '';
        if ($courseId <= 0) {
            return $this->errorResponse('Missing course ID', 400);
        }
        $course = DB::table('courses')->where('course_id', $courseId)->first();
        if (!$course) {
            return $this->errorResponse('Course not found', 404);
        }
        $courseIsVip = (int)$course->is_vip;
        $hasVipAccess = false;
        if (!empty($uid)) {
            $hasVipAccess = VipUser::where('course_id', $courseId)->where('user_id', $uid)->exists();
        }

        $query = DB::table('study_plan as sp')
            ->join('lessons', 'lessons.id', '=', 'sp.lesson_id')
            ->join('lessons_categories', 'lessons_categories.id', '=', 'lessons.category_id')
            ->select(
                'lessons.id',
                'lessons.title as lesson_title',
                'lessons.duration',
                'lessons.isVip',
                'lessons.isVideo',
                'lessons.link',
                'lessons.thumbnail',
                'sp.day',
                'lessons_categories.category_title',
                'lessons_categories.image_url'
            )
            ->where('sp.course_id', $courseId)
            ->orderBy('sp.day')
            ->orderBy('sp.id');

        if (!empty($uid)) {
            $query->leftJoin('studies as s', function ($join) use ($uid) {
                $join->on('s.lesson_id', '=', 'sp.lesson_id')
                     ->where('s.user_id', '=', $uid);
            })->addSelect(DB::raw('CASE WHEN s.id IS NULL THEN 0 ELSE 1 END as learned'));
        } else {
            $query->addSelect(DB::raw('0 as learned'));
        }

        $plans = $query->get();

        $dayPlan = [];
        foreach ($plans as $row) {
            $dayIndex = max(0, ((int)$row->day) - 1);
            if (!isset($dayPlan[$dayIndex])) {
                $dayPlan[$dayIndex] = [];
            }
            $rowLessonIsVip = (int)$row->isVip;
            $hasAccess = ($courseIsVip === 0) ? true : ($rowLessonIsVip === 0 || $hasVipAccess);

            $dayPlan[$dayIndex][] = [
                'id' => (int)$row->id,
                'title' => $this->ensureUtf8($row->lesson_title),
                'duration' => (int)$row->duration,
                'isVip' => $rowLessonIsVip,
                'isVideo' => (int)$row->isVideo,
                'thumbnail' => $row->thumbnail,
                'imageUrl' => $row->image_url,
                'categoryTitle' => $this->ensureUtf8($row->category_title),
                'learned' => (int)$row->learned,
                'hasAccess' => $hasAccess,
                'hasDownloadAccess' => $hasVipAccess,
            ];
        }

        // Normalize to array of arrays in day order
        ksort($dayPlan);
        $curriculum = array_values($dayPlan);

        return $this->successResponse(['courseId' => (int)$courseId, 'curriculum' => $curriculum]);
    }

    /**
     * Get lesson categories by course
     */
    public function lessonCategories(Request $request)
    {
        $courseId = (int)($request->input('courseId') ?? $request->input('course_id'));
        $major = trim((string)$request->input('major', ''));


        if ($courseId <= 0) {
            return $this->errorResponse('Missing course ID', 400);
        }

        $courseExists = DB::table('courses')->where('course_id', $courseId)->exists();
        if (!$courseExists) {
            return $this->errorResponse('Course not found', 404);
        }

        $query = DB::table('lessons_categories as lc')
            ->leftJoin('lessons as l', 'l.category_id', '=', 'lc.id')
            ->select(
                'lc.id',
                'lc.course_id',
                'lc.category',
                'lc.category_title',
                'lc.image_url',
                'lc.major',
                'lc.sort_order',
                DB::raw('COUNT(l.id) as lessons_count')
            )
            ->where('lc.course_id', $courseId)
            ->groupBy('lc.id', 'lc.course_id', 'lc.category', 'lc.category_title', 'lc.image_url', 'lc.major', 'lc.sort_order')
            ->orderByDesc('lc.sort_order')
            ->orderBy('lc.id');

        if ($major !== '') {
            $query->where('lc.major', $major);
        }

        $categories = $query->get();

        $data = $categories->map(function ($cat) {
            return [
                'id' => (int)$cat->id,
                'courseId' => (int)$cat->course_id,
                'category' => $cat->category ?? '',
                'categoryTitle' => $this->ensureUtf8($cat->category_title),
                'imageUrl' => $cat->image_url ?? '',
                'major' => $cat->major ?? '',
                'sortOrder' => (int)($cat->sort_order ?? 0),
                'lessonsCount' => (int)$cat->lessons_count,
            ];
        });

        $meta = [
            'courseId' => (int)$courseId,
            'total' => $data->count(),
        ];

        if ($major !== '') {
            $meta['major'] = $major;
        }

        return $this->successResponse($data, 200, $meta);
    }

    /**
     * Get or generate certificate
     */
    public function certificate(Request $request)
    {
        $courseId = (int)$request->input('courseId');
        $userId = $request->input('userId');

        if (!$courseId || !$userId) {
            return $this->errorResponse('Missing course ID or user ID', 400);
        }

        $learner = Learner::where('user_id', $userId)->first();
        if (!$learner) {
            return $this->errorResponse('User not found.', 403);
        }

        // Check if course is completed
        $courseStats = DB::table('courses')
            ->select('courses.lessons_count', 'courses.title as course_title', 'courses.major', 'courses.certificate_title', 'courses.certificate_code')
            ->selectRaw('count(studies.id) as learned')
            ->join('lessons_categories', 'lessons_categories.course_id', '=', 'courses.course_id')
            ->join('lessons', 'lessons.category_id', '=', 'lessons_categories.id')
            ->join('studies', 'studies.lesson_id', '=', 'lessons.id')
            ->where('courses.course_id', $courseId)
            ->where('studies.user_id', $userId)
            ->groupBy('courses.course_id', 'courses.lessons_count', 'courses.title', 'courses.major', 'courses.certificate_title', 'courses.certificate_code')
            ->first();

        if (!$courseStats) {
             return $this->errorResponse('Access Denied! You need to learn the course completely first.', 403);
        }

        $lessonCount = (int)$courseStats->lessons_count;
        $learned = (int)$courseStats->learned;

        if ($learned < $lessonCount) {
            return $this->errorResponse('Access Denied! You need to learn the course completely first.', 403, [
                'learned' => $learned,
                'total' => $lessonCount
            ]);
        }

        // Get or Create Certificate
        $certificate = Certificate::where('course_id', $courseId)
            ->where('user_id', $userId)
            ->first();

        if (!$certificate) {
            $certificate = new Certificate();
            $certificate->course_id = $courseId;
            $certificate->user_id = $userId;
            $certificate->date = date('Y-m-d');
            $certificate->save();
        }

        // Encode ID (Simple base64 or custom as legacy? Legacy uses `DigitEncoder`.
        // I'll use a simple obfuscation or just ID if DigitEncoder logic is complex/unknown.
        // Legacy `classes/digitencoder.php` was used.
        // I'll simulate it or just return ID for now. User didn't provide DigitEncoder logic.
        // I'll return ID.
        $certificateIdEncoded = base64_encode($certificate->id); // Placeholder for DigitEncoder

        $major = $courseStats->major;
        $language = Language::where('code', $major)->first();
        $platform = $language ? $language->certificate_title : (($major == "english") ? "English for Myanmar" : "Korean for Myanmar");
        $seal = $language ? $language->seal : (($major == "english") ? "assets/images/ee_certificate_seal.png" : "assets/images/ko_certificate_seal.png");
        $baseUrl = url('/');

        $data = [
            'name' => $learner->learner_name,
            'course' => $this->ensureUtf8($courseStats->course_title),
            'major' => $major,
            'date' => $certificate->date,
            'ref' => $courseStats->certificate_code . '-' . str_pad($certificate->id, 5, '0', STR_PAD_LEFT),
            'url' => $baseUrl . '/certificate/view.php?id=' . $certificateIdEncoded, // Maintaining legacy URL structure?
            'download' => $baseUrl . '/certificate/download.php?id=' . $certificateIdEncoded,
            'platform' => $platform,
            'seal' => $seal
        ];

        return $this->successResponse($data);
    }

    private function ensureUtf8($string)
    {
        return mb_convert_encoding($string ?? '', 'UTF-8', 'UTF-8');
    }
}
