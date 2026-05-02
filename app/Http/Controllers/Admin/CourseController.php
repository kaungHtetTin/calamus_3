<?php

namespace App\Http\Controllers\Admin;

use App\Events\CommentCreated;
use App\Events\CommentLiked;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Certificate;
use App\Models\Comment;
use App\Models\Learner;
use App\Models\Lesson;
use App\Models\LessonCategory;
use App\Models\Language;
use App\Models\Rating;
use App\Models\Teacher;
use App\Services\Admin\VimeoService;
use App\Services\NotificationDispatchService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $coursesQuery = Course::with('teacher:id,name')
            ->whereRaw("LOWER(COALESCE(major, '')) != ?", ['not']);
        $this->applyMajorScopeToCoursesQuery($request, $coursesQuery);

        return Inertia::render('Admin/Courses', [
            'courses' => $coursesQuery
                ->orderByDesc('sorting')
                ->orderByDesc('course_id')
                ->get(),
            'teachers' => Teacher::query()->select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $majorOptions = $this->resolveAdminMajorOptions($request);

        return Inertia::render('Admin/CourseCreate', [
            'teachers' => Teacher::query()->select('id', 'name')->orderBy('name')->get(),
            'majorOptions' => $majorOptions,
        ]);
    }

    public function edit(Request $request, Course $course)
    {
     
        $this->ensureCourseScope($request, $course);
        $majorOptions = $this->resolveAdminMajorOptions($request);
        $partialData = trim((string) $request->header('X-Inertia-Partial-Data', ''));
        $partialProps = $partialData === '' ? [] : array_values(array_filter(array_map('trim', explode(',', $partialData))));
        $shouldSendCourse = $partialData === '' || in_array('course', $partialProps, true);

        if ($shouldSendCourse) {
            $course->load('teacher:id,name');
        }
        $studentsPerPage = (int) $request->query('studentsPerPage', 25);
        if ($studentsPerPage < 10) $studentsPerPage = 10;
        if ($studentsPerPage > 200) $studentsPerPage = 200;

        $studentsQ = trim((string) $request->query('studentsQ', ''));
        $studentsPage = (int) $request->query('studentsPage', 1);
        if ($studentsPage < 1) $studentsPage = 1;

        $hasVipUsersTable = Schema::hasTable('vipusers');
        $vipCourseColumn = Schema::hasColumn('vipusers', 'course_id') ? 'course_id' : (Schema::hasColumn('vipusers', 'course') ? 'course' : null);
        $vipUserIdColumn = Schema::hasColumn('vipusers', 'user_id') ? 'user_id' : null;
        $vipPhoneColumn = Schema::hasColumn('vipusers', 'phone') ? 'phone' : null;
        $vipDateColumn = Schema::hasColumn('vipusers', 'date') ? 'date' : (Schema::hasColumn('vipusers', 'created_at') ? 'created_at' : null);
        $vipDeletedColumn = Schema::hasColumn('vipusers', 'deleted_account') ? 'deleted_account' : null;

        $enrolledTotal = 0;
        $enrolledDeleted = 0;
        if ($hasVipUsersTable && $vipCourseColumn) {
            $enrollmentTotals = DB::table('vipusers')
                ->where($vipCourseColumn, (int) $course->course_id)
                ->selectRaw(
                    $vipDeletedColumn
                        ? 'COUNT(*) as total, SUM(CASE WHEN ' . $vipDeletedColumn . ' = 1 THEN 1 ELSE 0 END) as deleted'
                        : 'COUNT(*) as total, 0 as deleted'
                )
                ->first();

            $enrolledTotal = (int) ($enrollmentTotals?->total ?? 0);
            $enrolledDeleted = (int) ($enrollmentTotals?->deleted ?? 0);
        }

        $vipQuery = $hasVipUsersTable && $vipCourseColumn
            ? DB::table('vipusers as vu')->where('vu.' . $vipCourseColumn, (int) $course->course_id)
            : DB::table(DB::raw('(select 1 as id) as vu'))->whereRaw('1 = 0');

        if ($studentsQ !== '') {
            $hasLearnersTable = Schema::hasTable('learners');
            $hasLearnerUserIdColumn = $hasLearnersTable && Schema::hasColumn('learners', 'user_id');
            $hasLearnerNameColumn = $hasLearnersTable && Schema::hasColumn('learners', 'learner_name');
            $hasLearnerEmailColumn = $hasLearnersTable && Schema::hasColumn('learners', 'learner_email');
            $canJoinByUserId = $vipUserIdColumn !== null && $hasLearnerUserIdColumn;
            $canSearchLearner = $canJoinByUserId && ($hasLearnerNameColumn || $hasLearnerEmailColumn);

            $needle = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $studentsQ) . '%';
            $vipQuery->where(function ($q) use (
                $needle,
                $vipUserIdColumn,
                $canSearchLearner,
                $canJoinByUserId,
                $hasLearnerNameColumn,
                $hasLearnerEmailColumn
            ) {
                $hasAnyCondition = false;
                if ($vipUserIdColumn) {
                    $q->where('vu.' . $vipUserIdColumn, 'like', $needle);
                    $hasAnyCondition = true;
                }

                if ($canSearchLearner) {
                    $q->orWhereExists(function ($sub) use ($needle, $hasLearnerNameColumn, $hasLearnerEmailColumn) {
                        $sub->from('learners as l')
                            ->selectRaw('1')
                            ->where(function ($join) {
                                $join->whereColumn('l.user_id', 'vu.user_id');
                            })
                            ->where(function ($match) use ($needle, $hasLearnerNameColumn, $hasLearnerEmailColumn) {
                                $hasMatch = false;
                                if ($hasLearnerNameColumn) {
                                    $match->where('l.learner_name', 'like', $needle);
                                    $hasMatch = true;
                                }
                                if ($hasLearnerEmailColumn) {
                                    $method = $hasMatch ? 'orWhere' : 'where';
                                    $match->{$method}('l.learner_email', 'like', $needle);
                                }
                            });
                    });
                }
            });
        }

        $studentsFilteredTotal = $studentsQ === ''
            ? $enrolledTotal
            : (int) (clone $vipQuery)->reorder()->count();

        $vipRows = (clone $vipQuery)
            ->select(array_values(array_filter([
                'vu.id',
                $vipUserIdColumn ? 'vu.' . $vipUserIdColumn . ' as user_id' : null,
                $vipPhoneColumn ? 'vu.' . $vipPhoneColumn . ' as phone' : null,
                $vipDateColumn ? 'vu.' . $vipDateColumn . ' as date' : null,
                $vipDeletedColumn ? 'vu.' . $vipDeletedColumn . ' as deleted_account' : null,
            ])))
            ->orderByDesc('vu.id')
            ->forPage($studentsPage, $studentsPerPage)
            ->get();

        $userIds = $vipRows
            ->pluck('user_id')
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '' && $value !== '0')
            ->unique()
            ->values();

        $learners = collect();
        if (Schema::hasTable('learners') && $userIds->isNotEmpty()) {
            $learners = DB::table('learners')
                ->select('user_id', 'learner_phone', 'learner_name', 'learner_email', 'learner_image')
                ->whereIn('user_id', $userIds->all())
                ->get();
        }

        $learnerByUserId = $learners
            ->filter(fn ($row) => trim((string) ($row->user_id ?? '')) !== '')
            ->keyBy(fn ($row) => trim((string) $row->user_id));

        $enrolledStudentsRows = $vipRows->map(function ($row) use ($learnerByUserId) {
            $userId = trim((string) ($row->user_id ?? ''));
            $learner = null;

            if ($userId !== '' && isset($learnerByUserId[$userId])) {
                $learner = $learnerByUserId[$userId];
            }

            $phoneNumber = trim((string) ($learner?->learner_phone ?? $userId));
            $phoneValue = null;
            if ($phoneNumber !== '' && ctype_digit($phoneNumber)) {
                $phoneValue = (int) $phoneNumber;
            }

            return [
                'id' => (int) $row->id,
                'user_id' => $userId,
                'phone' => $phoneValue,
                'date' => (string) ($row->date ?? ''),
                'deleted_account' => (int) ($row->deleted_account ?? 0),
                'learner_name' => (string) ($learner?->learner_name ?? ''),
                'learner_email' => (string) ($learner?->learner_email ?? ''),
                'learner_image' => $learner?->learner_image,
            ];
        })->values();

        $enrolledStudents = new LengthAwarePaginator(
            $enrolledStudentsRows,
            $studentsFilteredTotal,
            $studentsPerPage,
            $studentsPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return Inertia::render('Admin/CourseEdit', [
            'course' => $course,
            'teachers' => function () {
                return Teacher::query()->select('id', 'name')->orderBy('name')->get();
            },
            'majorOptions' => $majorOptions,
            'categories' => function () use ($course) {
                return LessonCategory::query()
                    ->where('course_id', $course->course_id)
                    ->with(['lessons' => function ($query) {
                        $query->orderBy('id');
                    }])
                    ->orderByDesc('sort_order')
                    ->orderBy('id')
                    ->get();
            },
            'dailyPlans' => function () use ($course) {
                $dailyPlanRows = DB::table('study_plan as sp')
                    ->join('lessons as l', 'l.id', '=', 'sp.lesson_id')
                    ->join('lessons_categories as lc', 'lc.id', '=', 'l.category_id')
                    ->select(
                        'sp.id',
                        'sp.day',
                        'sp.lesson_id',
                        'l.title as lesson_title',
                        'l.duration',
                        'l.isVip',
                        'l.isVideo',
                        'lc.category_title'
                    )
                    ->where('sp.course_id', (int) $course->course_id)
                    ->orderBy('sp.day')
                    ->orderBy('sp.id')
                    ->get();

                $dailyPlans = [];
                foreach ($dailyPlanRows as $row) {
                    $day = (int) $row->day;
                    if (!isset($dailyPlans[$day])) {
                        $dailyPlans[$day] = [
                            'day' => $day,
                            'items' => [],
                        ];
                    }
                    $dailyPlans[$day]['items'][] = [
                        'id' => (int) $row->id,
                        'lesson_id' => (int) $row->lesson_id,
                        'lesson_title' => (string) $row->lesson_title,
                        'duration' => (int) $row->duration,
                        'isVip' => (int) $row->isVip,
                        'isVideo' => (int) $row->isVideo,
                        'category_title' => (string) $row->category_title,
                    ];
                }

                return array_values($dailyPlans);
            },
            'reviews' => function () use ($course) {
                return DB::table('ratings as r')
                    ->leftJoin('learners as l', function ($join) {
                        $join->on('l.learner_phone', '=', 'r.user_id')
                            ->orOn('l.user_id', '=', 'r.user_id');
                    })
                    ->select(
                        'r.id',
                        'r.course_id',
                        'r.user_id',
                        'r.star',
                        'r.review',
                        'r.time',
                        'l.learner_name',
                        'l.learner_image'
                    )
                    ->where('r.course_id', (int) $course->course_id)
                    ->orderByDesc('r.time')
                    ->limit(300)
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id' => (int) $item->id,
                            'course_id' => (int) $item->course_id,
                            'user_id' => (string) $item->user_id,
                            'star' => (int) $item->star,
                            'review' => (string) ($item->review ?? ''),
                            'time' => (int) ($item->time ?? 0),
                            'learner_name' => (string) ($item->learner_name ?? ''),
                            'learner_image' => $item->learner_image,
                        ];
                    })
                    ->values();
            },
            'enrolledStudents' => $enrolledStudents,
            'enrolledStudentsFilters' => [
                'q' => $studentsQ,
                'perPage' => $studentsPerPage,
            ],
            'enrollmentStats' => [
                'total' => $enrolledTotal,
                'active' => $enrolledTotal - $enrolledDeleted,
                'deleted' => $enrolledDeleted,
            ],
            'reviewStats' => function () use ($course) {
                $reviewCounts = DB::table('ratings')
                    ->where('course_id', (int) $course->course_id)
                    ->select('star', DB::raw('COUNT(*) as total'))
                    ->groupBy('star')
                    ->pluck('total', 'star');
                $reviewTotal = (int) $reviewCounts->sum();
                $reviewAverage = $reviewTotal > 0
                    ? round((float) DB::table('ratings')->where('course_id', (int) $course->course_id)->avg('star'), 1)
                    : 0;
                $reviewBreakdown = collect([5, 4, 3, 2, 1])->map(function ($star) use ($reviewCounts, $reviewTotal) {
                    $count = (int) ($reviewCounts[$star] ?? 0);
                    return [
                        'star' => $star,
                        'count' => $count,
                        'percentage' => $reviewTotal > 0 ? round(($count / $reviewTotal) * 100, 1) : 0,
                    ];
                })->values();

                return [
                    'total' => $reviewTotal,
                    'average' => $reviewAverage,
                    'breakdown' => $reviewBreakdown,
                ];
            },
        ]);
    }

    public function certificate(Request $request)
    {
        $courseId = (int) ($request->query('courseId') ?? $request->query('course_id'));
        $userInput = trim((string) ($request->query('userId') ?? $request->query('user_id')));

        if (!$courseId || $userInput === '') {
            return Inertia::render('Admin/Certificate', [
                'error' => 'Missing course ID or user ID',
                'certificateData' => null,
            ]);
        }

        $course = Course::query()
            ->select('course_id', 'title', 'major', 'certificate_title', 'certificate_code')
            ->where('course_id', $courseId)
            ->first();
        if (!$course) {
            return Inertia::render('Admin/Certificate', [
                'error' => 'Course not found.',
                'certificateData' => null,
            ]);
        }
        $this->ensureCourseScope($request, $course);

        $learner = Learner::where('user_id', $userInput)
            ->orWhere('learner_phone', $userInput)
            ->first();
        if (!$learner) {
            return Inertia::render('Admin/Certificate', [
                'error' => 'Learner not found.',
                'certificateData' => null,
            ]);
        }

        $certificate = Certificate::where('course_id', $courseId)
            ->where('user_id', $learner->user_id)
            ->first();
        if (!$certificate) {
            $certificate = new Certificate();
            $certificate->course_id = $courseId;
            $certificate->user_id = $learner->user_id;
            $certificate->date = date('Y-m-d');
            $certificate->save();
        }

        $certificateIdEncoded = base64_encode((string) $certificate->id);
        $language = Language::where('code', $course->major)->first();
        $platform = $language
            ? $language->certificate_title
            : (($course->major === 'english') ? 'English for Myanmar' : 'Korean for Myanmar');
        $seal = $language
            ? $language->seal
            : (($course->major === 'english') ? 'assets/images/ee_certificate_seal.png' : 'assets/images/ko_certificate_seal.png');
        $certificateCode = trim((string) ($course->certificate_code ?? ''));
        if ($certificateCode === '') {
            $certificateCode = strtoupper(substr((string) $course->major, 0, 2) ?: 'CE');
        }

        return Inertia::render('Admin/Certificate', [
            'error' => null,
            'certificateData' => [
                'name' => (string) ($learner->learner_name ?? ''),
                'course' => (string) ($course->title ?? ''),
                'major' => (string) ($course->major ?? ''),
                'date' => (string) $certificate->date,
                'ref' => $certificateCode . $certificateIdEncoded,
                'url' => 'https://www.calamuseducation.com/qr.php?id=' . $certificateIdEncoded,
                'download' => url('/certificate/download.php?id=' . $certificateIdEncoded),
                'platform' => (string) $platform,
                'seal' => (string) $seal,
                'certificate_bg' => "https://www.calamuseducation.com/uploads/icons/certificate/certificate_background.png",
            ],
            'courseId' => $courseId,
            'userId' => (string) $learner->user_id,
        ]);
    }

    public function certificateImageProxy(Request $request)
    {
        $url = trim((string) $request->query('url', ''));
        if ($url === '') {
            abort(422, 'Missing url');
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            abort(422, 'Invalid url');
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)) {
            abort(422, 'Unsupported scheme');
        }

        $response = Http::timeout(20)->get($url);
        if (!$response->ok()) {
            abort(404, 'Image not found');
        }

        $contentType = strtolower((string) $response->header('Content-Type', ''));
        if ($contentType === '' || !str_starts_with($contentType, 'image/')) {
            abort(422, 'URL is not an image');
        }

        return response($response->body(), 200, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function updateReview(Request $request, Course $course, Rating $review)
    {
        $this->ensureCourseScope($request, $course);
        if ((int) $review->course_id !== (int) $course->course_id) {
            abort(404);
        }

        $data = $request->validate([
            'star' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['nullable', 'string', 'max:4000'],
        ]);

        $review->update([
            'star' => (int) $data['star'],
            'review' => trim((string) ($data['review'] ?? '')),
        ]);

        return redirect()->back()->with('success', 'Review updated successfully.');
    }

    public function destroyReview(Course $course, Rating $review)
    {
        $this->ensureCourseScope(request(), $course);
        if ((int) $review->course_id !== (int) $course->course_id) {
            abort(404);
        }

        $review->delete();

        return redirect()->back()->with('success', 'Review deleted successfully.');
    }

    public function storeStudyPlan(Request $request, Course $course)
    {
        $this->ensureCourseScope($request, $course);
        $maxDay = max(1, (int) ($course->duration ?? 1));
        $data = $request->validate([
            'day' => ['required', 'integer', 'min:1', 'max:' . $maxDay],
            'lesson_id' => ['required', 'integer'],
        ]);

        $lessonBelongsToCourse = Lesson::query()
            ->join('lessons_categories as lc', 'lc.id', '=', 'lessons.category_id')
            ->where('lessons.id', (int) $data['lesson_id'])
            ->where('lc.course_id', (int) $course->course_id)
            ->exists();

        if (!$lessonBelongsToCourse) {
            return redirect()->back()->withErrors([
                'lesson_id' => 'Selected lesson is not part of this course.',
            ]);
        }

        $exists = DB::table('study_plan')
            ->where('course_id', (int) $course->course_id)
            ->where('day', (int) $data['day'])
            ->where('lesson_id', (int) $data['lesson_id'])
            ->exists();

        if ($exists) {
            return redirect()->back()->withErrors([
                'lesson_id' => 'Selected lesson already exists on this day.',
            ]);
        }

        DB::table('study_plan')->insert([
            'course_id' => (int) $course->course_id,
            'lesson_id' => (int) $data['lesson_id'],
            'day' => (int) $data['day'],
        ]);

        return redirect()->back()->with('success', 'Daily plan entry created successfully.');
    }

    public function updateStudyPlan(Request $request, Course $course, int $studyPlanId)
    {
        $this->ensureCourseScope($request, $course);
        $maxDay = max(1, (int) ($course->duration ?? 1));
        $data = $request->validate([
            'day' => ['required', 'integer', 'min:1', 'max:' . $maxDay],
            'lesson_id' => ['required', 'integer'],
        ]);

        $plan = DB::table('study_plan')
            ->where('id', $studyPlanId)
            ->where('course_id', (int) $course->course_id)
            ->first();

        if (!$plan) {
            abort(404);
        }

        $lessonBelongsToCourse = Lesson::query()
            ->join('lessons_categories as lc', 'lc.id', '=', 'lessons.category_id')
            ->where('lessons.id', (int) $data['lesson_id'])
            ->where('lc.course_id', (int) $course->course_id)
            ->exists();

        if (!$lessonBelongsToCourse) {
            return redirect()->back()->withErrors([
                'lesson_id' => 'Selected lesson is not part of this course.',
            ]);
        }

        $exists = DB::table('study_plan')
            ->where('course_id', (int) $course->course_id)
            ->where('day', (int) $data['day'])
            ->where('lesson_id', (int) $data['lesson_id'])
            ->where('id', '!=', $studyPlanId)
            ->exists();

        if ($exists) {
            return redirect()->back()->withErrors([
                'lesson_id' => 'Selected lesson already exists on this day.',
            ]);
        }

        DB::table('study_plan')
            ->where('id', $studyPlanId)
            ->where('course_id', (int) $course->course_id)
            ->update([
                'day' => (int) $data['day'],
                'lesson_id' => (int) $data['lesson_id'],
            ]);

        return redirect()->back()->with('success', 'Daily plan entry updated successfully.');
    }

    public function destroyStudyPlan(Course $course, int $studyPlanId)
    {
        $this->ensureCourseScope(request(), $course);
        $deleted = DB::table('study_plan')
            ->where('id', $studyPlanId)
            ->where('course_id', (int) $course->course_id)
            ->delete();

        if (!$deleted) {
            abort(404);
        }

        return redirect()->back()->with('success', 'Daily plan entry deleted successfully.');
    }

    public function storeCategory(Request $request, Course $course)
    {
        $this->ensureCourseScope($request, $course);
        $data = $request->validate([
            'category' => ['required', 'string', 'max:100'],
            'category_title' => ['required', 'string', 'max:255'],
            'image_url' => ['nullable', 'string', 'max:255'],
            'category_image' => ['nullable', 'image', 'max:4096'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'category_major' => ['nullable', 'string', 'max:50'],
        ]);

        $imageUrl = $this->storeCategoryImage($request->file('category_image'));

        LessonCategory::create([
            'course_id' => (int) $course->course_id,
            'category' => trim($data['category']),
            'category_title' => trim($data['category_title']),
            'image_url' => $imageUrl ?: (isset($data['image_url']) ? trim((string) $data['image_url']) : ''),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'major' => trim((string) ($data['category_major'] ?? ($course->major ?? ''))),
        ]);

        return redirect()->back()->with('success', 'Lesson category created successfully.');
    }

    public function updateCategory(Request $request, Course $course, LessonCategory $category)
    {
        $this->ensureCourseScope($request, $course);
        if ((int) $category->course_id !== (int) $course->course_id) {
            abort(404);
        }

        $data = $request->validate([
            'category' => ['required', 'string', 'max:100'],
            'category_title' => ['required', 'string', 'max:255'],
            'image_url' => ['nullable', 'string', 'max:255'],
            'category_image' => ['nullable', 'image', 'max:4096'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'category_major' => ['nullable', 'string', 'max:50'],
        ]);

        $imageUrl = $this->storeCategoryImage($request->file('category_image'));

        $category->update([
            'category' => trim($data['category']),
            'category_title' => trim($data['category_title']),
            'image_url' => $imageUrl ?: (isset($data['image_url']) ? trim((string) $data['image_url']) : $category->image_url),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'major' => trim((string) ($data['category_major'] ?? ($course->major ?? $category->major ?? ''))),
        ]);

        return redirect()->back()->with('success', 'Lesson category updated successfully.');
    }

    public function destroyCategory(Course $course, LessonCategory $category)
    {
        $this->ensureCourseScope(request(), $course);
        if ((int) $category->course_id !== (int) $course->course_id) {
            abort(404);
        }

        Lesson::where('category_id', $category->id)->delete();
        $category->delete();
        Course::syncLessonsCount((int) $course->course_id);

        return redirect()->back()->with('success', 'Lesson category deleted successfully.');
    }

    public function updateLesson(Request $request, Course $course, LessonCategory $category, Lesson $lesson)
    {
        $this->ensureCourseScope($request, $course);
        if ((int) $category->course_id !== (int) $course->course_id || (int) $lesson->category_id !== (int) $category->id) {
            abort(404);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'title_mini' => ['nullable', 'string', 'max:255'],
            'duration' => ['nullable', 'integer', 'min:0'],
            'isVip' => ['required', 'boolean'],
            'isVideo' => ['required', 'boolean'],
            'video_file' => ['exclude_unless:isVideo,1', 'nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/x-matroska', 'max:512000'],
            'html_file' => ['exclude_unless:isVideo,0', 'nullable', 'file', 'mimes:html,htm', 'max:10240'],
            'thumbnail_image' => ['exclude_unless:isVideo,1', 'nullable', 'image', 'max:4096'],
            'link' => ['nullable', 'string', 'max:1000'],
            'download_url' => ['nullable', 'string', 'max:1000'],
            'thumbnail' => ['nullable', 'string', 'max:1000'],
            'major' => ['nullable', 'string', 'max:50'],
            'effective_major' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'date' => ['nullable', 'integer', 'min:0'],
        ]);

        $isVideo = (int) ($data['isVideo'] ?? $lesson->isVideo) === 1;
        $link = isset($data['link']) ? trim((string) $data['link']) : (string) ($lesson->link ?? '');
        $downloadUrl = isset($data['download_url']) ? trim((string) $data['download_url']) : (string) ($lesson->download_url ?? '');
        $documentLink = (string) ($lesson->document_link ?? '');

        if ($isVideo && $request->hasFile('video_file')) {
            $videoFile = $request->file('video_file');
            $vimeoService = new VimeoService();
            $link = $vimeoService->uploadVideo(
                $videoFile,
                trim((string) $data['title']),
                [
                    (string) ($course->major ?? ''),
                    (string) ($course->title ?? ''),
                    (string) ($category->category_title ?? ''),
                ]
            );
            $downloadUrl = $this->storeDownloadVideo($videoFile);
        }

        if (!$isVideo && $request->hasFile('html_file')) {
            $documentLink = $this->storeLessonHtml($request->file('html_file'));
        }

        if ($isVideo) {
            $documentLink = '';
        }

        $thumbnailUrl = $this->storeLessonThumbnail($request->file('thumbnail_image'))
            ?: (isset($data['thumbnail']) ? trim((string) $data['thumbnail']) : (string) ($lesson->thumbnail ?? ''));

        $lesson->update([
            'title' => trim($data['title']),
            'title_mini' => isset($data['title_mini']) ? trim((string) $data['title_mini']) : '',
            'duration' => (int) ($data['duration'] ?? 0),
            'isVip' => (int) $data['isVip'],
            'isVideo' => $isVideo ? 1 : 0,
            'link' => $link,
            'document_link' => $documentLink,
            'download_url' => $downloadUrl,
            'thumbnail' => $thumbnailUrl,
            'major' => trim((string) ($data['effective_major'] ?? ($data['major'] ?? $lesson->major ?? ''))),
            'notes' => $isVideo ? (isset($data['notes']) ? trim((string) $data['notes']) : '') : '',
            'date' => (int) ($data['date'] ?? 0),
        ]);

        return redirect()->back()->with('success', 'Lesson updated successfully.');
    }

    public function bulkUpdateCategoryLessonsVip(Request $request, Course $course, LessonCategory $category)
    {
       
        $this->ensureCourseScope($request, $course);
        if ((int) $category->course_id !== (int) $course->course_id) {
            abort(404);
        }

        $data = $request->validate([
            'vip' => ['required', 'in:0,1'],
        ]);

        $vipColumn = null;
        if (Schema::hasColumn('lessons', 'isVip')) {
            $vipColumn = 'isVip';
        } elseif (Schema::hasColumn('lessons', 'is_vip')) {
            $vipColumn = 'is_vip';
        }

        if (!$vipColumn) {
            abort(500, 'Lessons VIP column not found.');
        }

        DB::table('lessons')
            ->where('category_id', (int) $category->id)
            ->update([$vipColumn => (int) $data['vip']]);

        $message = ((int) $data['vip'] === 1 ? 'All lessons set to VIP.' : 'All lessons set to non-VIP.');

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'course_id' => (int) $course->course_id,
                'category_id' => (int) $category->id,
                'vip' => (int) $data['vip'],
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    public function storeLesson(Request $request, Course $course, LessonCategory $category, NotificationDispatchService $dispatch)
    {
        $this->ensureCourseScope($request, $course);
        if ((int) $category->course_id !== (int) $course->course_id) {
            abort(404);
        }

        $data = $request->validate([
            'lesson_type' => ['required', 'in:video,document,document-bulk'],
            'title' => ['required_if:lesson_type,video', 'nullable', 'string', 'max:255'],
            'title_mini' => ['nullable', 'string', 'max:255'],
            'duration' => ['nullable', 'integer', 'min:0'],
            'isVip' => ['required', 'boolean'],
            'notes' => ['nullable', 'string'],
            'video_file' => ['exclude_unless:lesson_type,video', 'required_if:lesson_type,video', 'file', 'mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/x-matroska', 'max:512000'],
            'html_file' => ['exclude_unless:lesson_type,document,document-bulk', 'nullable', 'file', 'mimes:html,htm', 'max:10240'],
            'html_files' => ['exclude_unless:lesson_type,document-bulk', 'nullable', 'array', 'min:1'],
            'html_files.*' => ['file', 'mimes:html,htm', 'max:10240'],
            'download_url' => ['nullable', 'string', 'max:1000'],
            'thumbnail_image' => ['exclude_unless:lesson_type,video', 'required_if:lesson_type,video', 'image', 'max:4096'],
            'thumbnail' => ['nullable', 'string', 'max:1000'],
            'date' => ['nullable', 'integer', 'min:0'],
            'effective_major' => ['nullable', 'string', 'max:50'],
        ]);

        $isVideo = $data['lesson_type'] === 'video';
        $documentSourceFile = null;
        if ($isVideo) {
            if (!$request->hasFile('video_file')) {
                throw ValidationException::withMessages([
                    'video_file' => 'The video file field is required.',
                ]);
            }
            if (!$request->hasFile('thumbnail_image')) {
                throw ValidationException::withMessages([
                    'thumbnail_image' => 'The thumbnail image field is required.',
                ]);
            }
        } else {
            if ($request->hasFile('html_file')) {
                $documentSourceFile = $request->file('html_file');
            } else {
                $bulkFiles = $request->file('html_files', []);
                $documentSourceFile = is_array($bulkFiles) && count($bulkFiles) ? $bulkFiles[0] : null;
            }
            if (!$documentSourceFile) {
                throw ValidationException::withMessages([
                    'html_file' => 'The html file field is required.',
                ]);
            }
        }

        $title = trim((string) $data['title']);
        if (!$isVideo && $title === '') {
            $originalName = (string) ($documentSourceFile ? $documentSourceFile->getClientOriginalName() : '');
            $title = trim((string) pathinfo($originalName, PATHINFO_FILENAME));
            if ($title === '') {
                $title = 'Document Lesson';
            }
        }

        $link = '';
        $downloadUrl = '';
        $documentLink = '';

        if ($isVideo) {
            $videoFile = $request->file('video_file');
            $vimeoService = new VimeoService();
            $link = $vimeoService->uploadVideo(
                $videoFile,
                $title,
                [
                    (string) ($course->major ?? ''),
                    (string) ($course->title ?? ''),
                    (string) ($category->category_title ?? ''),
                ]
            );

            $downloadUrl = $this->storeDownloadVideo($videoFile);
        } else {
            $link = '';
            $downloadUrl = isset($data['download_url']) ? trim((string) $data['download_url']) : '';
            $documentLink = $this->storeLessonHtml($documentSourceFile);
        }

        $thumbnailUrl = $this->storeLessonThumbnail($request->file('thumbnail_image'))
            ?: trim((string) ($data['thumbnail'] ?? ''));

        $lesson = Lesson::create([
            'category_id' => (int) $category->id,
            'date' => (int) round(microtime(true) * 1000),
            'isVideo' => $isVideo ? 1 : 0,
            'isVip' => (int) $data['isVip'],
            'isChannel' => 0,
            'link' => $link,
            'download_url' => $downloadUrl,
            'document_link' => $documentLink,
            'title_mini' => isset($data['title_mini']) ? trim((string) $data['title_mini']) : '',
            'title' => $title,
            'major' => trim((string) ($data['effective_major'] ?? ($course->major ?? ''))),
            'thumbnail' => $thumbnailUrl,
            'like_count' => 0,
            'comment_count' => 0,
            'share_count' => 0,
            'view_count' => 0,
            'duration' => (int) ($data['duration'] ?? 0),
            'notes' => $isVideo ? (isset($data['notes']) ? trim((string) $data['notes']) : '') : '',
        ]);

        $major = trim((string) ($lesson->major ?? ($course->major ?? '')));
        $dispatch->pushToUserTopicByMajor(
            $major,
            'New Lesson Added',
            $title !== '' ? $title : 'A new lesson is available.',
            [
                'type' => 'lesson.added',
                'major' => strtolower($major),
                'lessonId' => (string) ($lesson->id ?? ''),
                'courseId' => (string) ($course->course_id ?? ''),
            ]
        );

        return redirect()->back()->with('success', 'Lesson created successfully.');
    }

    public function destroyLesson(Course $course, LessonCategory $category, Lesson $lesson)
    {
        $this->ensureCourseScope(request(), $course);
        if ((int) $category->course_id !== (int) $course->course_id || (int) $lesson->category_id !== (int) $category->id) {
            abort(404);
        }

        $lesson->delete();

        return redirect()->back()->with('success', 'Lesson deleted successfully.');
    }

    public function editLessonHtml(Course $course, LessonCategory $category, Lesson $lesson)
    {
        $this->ensureCourseScope(request(), $course);
        if ((int) $category->course_id !== (int) $course->course_id || (int) $lesson->category_id !== (int) $category->id) {
            abort(404);
        }

        if ((int) $lesson->isVideo === 1) {
            abort(404);
        }

        $relativePath = $this->resolveUploadsRelativePathFromUrl((string) ($lesson->document_link ?? ''));
        if (!$relativePath || !str_starts_with($relativePath, 'lessons/html/')) {
            abort(404);
        }

        $content = Storage::disk('uploads')->exists($relativePath)
            ? Storage::disk('uploads')->get($relativePath)
            : '';

        return Inertia::render('Admin/LessonHtmlEdit', [
            'course' => [
                'course_id' => (int) $course->course_id,
                'title' => (string) $course->title,
            ],
            'category' => [
                'id' => (int) $category->id,
                'category_title' => (string) $category->category_title,
            ],
            'lesson' => [
                'id' => (int) $lesson->id,
                'title' => (string) $lesson->title,
                'title_mini' => (string) ($lesson->title_mini ?? ''),
                'document_link' => (string) ($lesson->document_link ?? ''),
            ],
            'filePath' => $relativePath,
            'htmlContent' => $content,
        ]);
    }

    public function updateLessonHtml(Request $request, Course $course, LessonCategory $category, Lesson $lesson)
    {
        $this->ensureCourseScope($request, $course);
        if ((int) $category->course_id !== (int) $course->course_id || (int) $lesson->category_id !== (int) $category->id) {
            abort(404);
        }

        if ((int) $lesson->isVideo === 1) {
            abort(404);
        }

        $data = $request->validate([
            'html_content' => ['required', 'string'],
        ]);

        $relativePath = $this->resolveUploadsRelativePathFromUrl((string) ($lesson->document_link ?? ''));
        if (!$relativePath || !str_starts_with($relativePath, 'lessons/html/')) {
            abort(404);
        }

        Storage::disk('uploads')->put($relativePath, $data['html_content']);

        return redirect()->back()->with('success', 'HTML file updated successfully.');
    }

    public function videoLessonDetail(Course $course, LessonCategory $category, Lesson $lesson)
    {
        $this->ensureCourseScope(request(), $course);
        if ((int) $category->course_id !== (int) $course->course_id || (int) $lesson->category_id !== (int) $category->id) {
            abort(404);
        }

        if ((int) $lesson->isVideo !== 1) {
            abort(404);
        }

        $comments = DB::table('comment as c')
            ->leftJoin('learners as l', 'l.user_id', '=', 'c.writer_id')
            ->leftJoin('comment_likes as cl', function ($join) {
                $join->on('cl.comment_id', '=', 'c.time')
                    ->where('cl.user_id', '=', 10000);
            })
            ->select(
                'c.id',
                'c.writer_id',
                'c.body',
                'c.time',
                'c.parent',
                'c.likes',
                'c.image',
                'l.learner_name',
                'l.learner_image',
                DB::raw('CASE WHEN cl.comment_id IS NULL THEN 0 ELSE 1 END as is_liked')
            )
            ->where('c.target_type', 'lesson')
            ->where('c.target_id', (int) $lesson->id)
            ->orderByDesc('c.time')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'writer_id' => (string) $row->writer_id,
                    'writer_name' => (string) ($row->learner_name ?: ((string) $row->writer_id === '10000' ? 'Admin' : 'Unknown')),
                    'writer_image' => $row->learner_image,
                    'body' => (string) ($row->body ?? ''),
                    'time' => (int) ($row->time ?? 0),
                    'parent' => (int) ($row->parent ?? 0),
                    'likes' => (int) ($row->likes ?? 0),
                    'isLiked' => (int) ($row->is_liked ?? 0),
                    'image' => (string) ($row->image ?? ''),
                ];
            })
            ->values();

        return Inertia::render('Admin/VideoLessonDetail', [
            'course' => [
                'course_id' => (int) $course->course_id,
                'title' => (string) $course->title,
            ],
            'category' => [
                'id' => (int) $category->id,
                'category_title' => (string) $category->category_title,
            ],
            'lesson' => [
                'id' => (int) $lesson->id,
                'title' => (string) $lesson->title,
                'title_mini' => (string) ($lesson->title_mini ?? ''),
                'link' => (string) ($lesson->link ?? ''),
                'download_url' => (string) ($lesson->download_url ?? ''),
                'duration' => (int) ($lesson->duration ?? 0),
                'isVip' => (int) ($lesson->isVip ?? 0),
                'like_count' => (int) ($lesson->like_count ?? 0),
                'comment_count' => (int) ($lesson->comment_count ?? 0),
                'share_count' => (int) ($lesson->share_count ?? 0),
            ],
            'comments' => $comments,
        ]);
    }

    public function storeVideoLessonComment(Request $request, Course $course, LessonCategory $category, Lesson $lesson)
    {
        $this->ensureCourseScope($request, $course);
        if ((int) $category->course_id !== (int) $course->course_id || (int) $lesson->category_id !== (int) $category->id || (int) $lesson->isVideo !== 1) {
            abort(404);
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'parent' => ['nullable', 'integer', 'min:0'],
        ]);

        $time = (int) round(microtime(true) * 1000);
        $parent = (int) ($data['parent'] ?? 0);
        $parentComment = null;
        if ($parent > 0) {
            $parentComment = Comment::query()
                ->where('target_type', 'lesson')
                ->where('target_id', (int) $lesson->id)
                ->where('time', $parent)
                ->first();
            if (!$parentComment) {
                throw ValidationException::withMessages([
                    'parent' => 'Parent comment not found.',
                ]);
            }
        }

        $comment = Comment::create([
            'post_id' => 0,
            'target_type' => 'lesson',
            'target_id' => (int) $lesson->id,
            'writer_id' => 10000,
            'body' => trim((string) $data['body']),
            'image' => '',
            'time' => $time,
            'parent' => $parent,
            'likes' => 0,
        ]);

        DB::table('lessons')->where('id', (int) $lesson->id)->increment('comment_count');
        CommentCreated::dispatch(
            $comment,
            'lesson',
            (string) $lesson->id,
            null,
            $lesson,
            $parentComment
        );

        return redirect()->back()->with('success', 'Comment created successfully.');
    }

    public function updateVideoLessonComment(Request $request, Course $course, LessonCategory $category, Lesson $lesson, Comment $comment)
    {
        $this->ensureCourseScope($request, $course);
        if ((int) $category->course_id !== (int) $course->course_id || (int) $lesson->category_id !== (int) $category->id || (int) $lesson->isVideo !== 1) {
            abort(404);
        }

        if ((string) $comment->target_type !== 'lesson' || (int) $comment->target_id !== (int) $lesson->id) {
            abort(404);
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $comment->update([
            'body' => trim((string) $data['body']),
            'writer_id' => 10000,
        ]);

        return redirect()->back()->with('success', 'Comment updated successfully.');
    }

    public function destroyVideoLessonComment(Course $course, LessonCategory $category, Lesson $lesson, Comment $comment)
    {
        $this->ensureCourseScope(request(), $course);
        if ((int) $category->course_id !== (int) $course->course_id || (int) $lesson->category_id !== (int) $category->id || (int) $lesson->isVideo !== 1) {
            abort(404);
        }

        if ((string) $comment->target_type !== 'lesson' || (int) $comment->target_id !== (int) $lesson->id) {
            abort(404);
        }

        $childCount = DB::table('comment')->where('parent', (int) $comment->time)->count();
        DB::table('comment')->where('parent', (int) $comment->time)->delete();
        DB::table('comment_likes')->where('comment_id', (int) $comment->time)->delete();
        $comment->delete();

        DB::table('lessons')
            ->where('id', (int) $lesson->id)
            ->update([
                'comment_count' => DB::raw('GREATEST(comment_count - ' . (1 + (int) $childCount) . ', 0)'),
            ]);

        return redirect()->back()->with('success', 'Comment deleted successfully.');
    }

    public function toggleVideoLessonCommentLike(
        Request $request,
        Course $course,
        LessonCategory $category,
        Lesson $lesson,
        Comment $comment
    ): JsonResponse {
        $this->ensureCourseScope($request, $course);
        if ((int) $category->course_id !== (int) $course->course_id || (int) $lesson->category_id !== (int) $category->id || (int) $lesson->isVideo !== 1) {
            abort(404);
        }

        if ((string) $comment->target_type !== 'lesson' || (int) $comment->target_id !== (int) $lesson->id) {
            abort(404);
        }

        $data = $request->validate([
            'liked' => ['nullable', 'boolean'],
        ]);

        $commentTime = (int) $comment->time;
        $userId = 10000;
        $exists = DB::table('comment_likes')
            ->where('comment_id', $commentTime)
            ->where('user_id', $userId)
            ->exists();

        $shouldLike = array_key_exists('liked', $data) ? (bool) $data['liked'] : !$exists;

        if ($shouldLike && !$exists) {
            DB::table('comment_likes')->insert([
                'comment_id' => $commentTime,
                'user_id' => $userId,
            ]);
            DB::table('comment')->where('id', (int) $comment->id)->increment('likes');
            $exists = true;

            $liker = Learner::where('user_id', $userId)->first();
            if (!$liker) {
                $liker = new Learner([
                    'user_id' => $userId,
                    'learner_name' => 'Admin',
                    'learner_image' => '',
                ]);
            }
            CommentLiked::dispatch($comment, $liker);
        } elseif (!$shouldLike && $exists) {
            DB::table('comment_likes')
                ->where('comment_id', $commentTime)
                ->where('user_id', $userId)
                ->delete();
            DB::table('comment')
                ->where('id', (int) $comment->id)
                ->update(['likes' => DB::raw('GREATEST(likes - 1, 0)')]);
            $exists = false;
        }

        $likesCount = (int) DB::table('comment')->where('id', (int) $comment->id)->value('likes');

        return response()->json([
            'success' => true,
            'isLiked' => $exists ? 1 : 0,
            'likesCount' => $likesCount,
        ]);
    }

    private function storeCategoryImage(?UploadedFile $file): ?string
    {
        if (!$file) {
            return null;
        }

        $fileName = time() . '_' . uniqid() . '.' . strtolower($file->getClientOriginalExtension());
        $path = 'icons';
        $storedPath = Storage::disk('uploads')->putFileAs($path, $file, $fileName);

        return env('APP_URL') . Storage::disk('uploads')->url($storedPath);
    }

    private function storeLessonThumbnail(?UploadedFile $file): ?string
    {
        if (!$file) {
            return null;
        }

        $fileName = time() . '_' . uniqid() . '.' . strtolower($file->getClientOriginalExtension());
        $path = 'icons';
        $storedPath = Storage::disk('uploads')->putFileAs($path, $file, $fileName);

        return env('APP_URL') . Storage::disk('uploads')->url($storedPath);
    }

    private function storeDownloadVideo(?UploadedFile $file): string
    {
        if (!$file) {
            return '';
        }

        $fileName = time() . '_' . uniqid() . '.' . strtolower($file->getClientOriginalExtension());
        $path = 'videos';
        $storedPath = Storage::disk('uploads')->putFileAs($path, $file, $fileName);

        return env('APP_URL') . Storage::disk('uploads')->url($storedPath);
    }

    private function storeLessonHtml(?UploadedFile $file): string
    {
        if (!$file) {
            return '';
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'html');
        $fileName = time() . '_' . uniqid() . '.' . $extension;
        $path = 'lessons/html';
        $storedPath = Storage::disk('uploads')->putFileAs($path, $file, $fileName);

        return env('APP_URL') . Storage::disk('uploads')->url($storedPath);
    }

    private function resolveUploadsRelativePathFromUrl(string $url): ?string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        if ($path === '') {
            return null;
        }

        $uploadsUrl = (string) Storage::disk('uploads')->url('');
        $uploadsPath = (string) parse_url($uploadsUrl, PHP_URL_PATH);
        if ($uploadsPath === '') {
            $uploadsPath = $uploadsUrl;
        }

        $normalizedUploadsPath = '/' . trim($uploadsPath, '/');
        $normalizedPath = '/' . ltrim($path, '/');

        if (!str_starts_with($normalizedPath, $normalizedUploadsPath . '/')) {
            return null;
        }

        $relativePath = ltrim(substr($normalizedPath, strlen($normalizedUploadsPath)), '/');
        if ($relativePath === '' || str_contains($relativePath, '..')) {
            return null;
        }

        return $relativePath;
    }

    public function store(Request $request)
    {
        $data = $this->validateCourse($request);
        $created = DB::transaction(function () use ($data) {
            $course = Course::create($data);
            $this->grantDiamondPlanVipAccess($course);
            return $course;
        });

        return redirect()
            ->route('admin.courses.edit', ['course' => $created->course_id])
            ->with('success', 'Course created successfully.');
    }

    private function grantDiamondPlanVipAccess(Course $course): void
    {

        $major = strtolower(trim((string) ($course->major ?? '')));
        if ($major === '') {
            return;
        }

        $eligibleUserIds = DB::table('user_data')
            ->where('major', $major)
            ->where('diamond_plan', 1)
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->map(function ($id) {
                return trim((string) $id);
            })
            ->filter()
            ->unique()
            ->values();

        if ($eligibleUserIds->isEmpty()) {
            return;
        }

        $courseId = (int) $course->course_id;
        $existingUserIds = DB::table('vipusers')
            ->where('course_id', $courseId)
            ->whereIn('user_id', $eligibleUserIds->all())
            ->pluck('user_id')
            ->map(function ($id) {
                return trim((string) $id);
            })
            ->filter()
            ->flip();

        $hasPhoneColumn = Schema::hasColumn('vipusers', 'phone');
        $hasDateColumn = Schema::hasColumn('vipusers', 'date');
        $hasDeletedAccountColumn = Schema::hasColumn('vipusers', 'deleted_account');
        $now = now()->toDateTimeString();
        $batch = [];

        foreach ($eligibleUserIds as $userId) {
            if ($existingUserIds->has($userId)) {
                continue;
            }

            $row = [
                'course_id' => $courseId,
                'user_id' => $userId,
            ];

            if ($hasPhoneColumn) {
                $row['phone'] = $userId;
            }
            if ($hasDateColumn) {
                $row['date'] = $now;
            }
            if ($hasDeletedAccountColumn) {
                $row['deleted_account'] = 0;
            }

            $batch[] = $row;
            if (count($batch) >= 500) {
                DB::table('vipusers')->insert($batch);
                $batch = [];
            }
        }

        if ($batch !== []) {
            DB::table('vipusers')->insert($batch);
        }
    }

    public function update(Request $request, Course $course)
    {
          
        $this->ensureCourseScope($request, $course);
        $data = $this->validateCourse($request);
      
        $course->update($data);
    
        return redirect()->back()->with('success', 'Course updated successfully.');
    }

    public function destroy(Course $course)
    {
        $this->ensureCourseScope(request(), $course);
        $course->delete();
        return redirect()->back()->with('success', 'Course deleted successfully.');
    }

    private function getAdminMajorScope(Request $request): \Illuminate\Support\Collection
    {
        $languageValues = Language::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['name', 'code'])
            ->map(function ($language) {
                return strtolower(trim((string) ($language->code ?: $language->name)));
            })
            ->filter()
            ->unique()
            ->values();

        $admin = $request->user('admin');
        $scope = collect((array) ($admin?->major_scope ?? []))
            ->map(function ($item) {
                return strtolower(trim((string) $item));
            })
            ->filter()
            ->unique()
            ->values();

        if ($scope->contains('*')) {
            return collect(['*']);
        }

        return $scope
            ->filter(function ($value) use ($languageValues) {
                return $languageValues->contains($value);
            })
            ->values();
    }

    private function applyMajorScopeToCoursesQuery(Request $request, Builder $query): void
    {
        $scope = $this->getAdminMajorScope($request);
        if ($scope->contains('*')) {
            return;
        }

        $values = $scope->all();
        if ($values === []) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereIn(DB::raw("LOWER(TRIM(COALESCE(major, '')))"), $values);
    }

    private function ensureCourseScope(Request $request, Course $course): void
    {
        $scope = $this->getAdminMajorScope($request);
        if ($scope->contains('*')) {
            return;
        }

        $major = strtolower(trim((string) ($course->major ?? '')));
        if ($major === 'not') {
            $requestedMajor = strtolower(trim((string) (
                $request->input('effective_major')
                ?? $request->input('category_major')
                ?? $request->input('major')
                ?? $request->query('major')
                ?? ''
            )));
            if ($requestedMajor !== '' && $scope->contains($requestedMajor)) {
                return;
            }
            abort(403);
        }

        if ($major === '' || !$scope->contains($major)) {
            abort(403);
        }
    }

    private function resolveAdminMajorOptions(Request $request): array
    {
        $scope = $this->getAdminMajorScope($request);
        $languageOptions = Language::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name', 'display_name', 'code'])
            ->map(function ($language) {
                $value = strtolower(trim((string) ($language->code ?: $language->name)));
                if ($value === '') {
                    return null;
                }
                return [
                    'value' => $value,
                    'label' => trim((string) ($language->display_name ?: $language->name ?: strtoupper($value))),
                ];
            })
            ->filter()
            ->unique('value')
            ->values();

        if ($scope->contains('*')) {
            return $languageOptions->all();
        }

        if ($scope->isEmpty()) {
            return [];
        }

        return $scope
            ->map(function ($value) use ($languageOptions) {
                return $languageOptions->firstWhere('value', $value);
            })
            ->filter()
            ->values()
            ->all();
    }

    private function validateCourse(Request $request): array
    {
        $allowedMajors = collect($this->resolveAdminMajorOptions($request))
            ->pluck('value')
            ->map(function ($value) {
                return strtolower(trim((string) $value));
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($allowedMajors === []) {
            throw ValidationException::withMessages([
                'major' => 'No major options found. Please configure languages first.',
            ]);
        }

        $data = $request->validate([
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'title' => ['required', 'string', 'max:255'],
            'certificate_title' => ['required', 'string', 'max:255'],
            'lessons_count' => ['required', 'integer', 'min:0'],
            'cover_url' => ['nullable', 'string', 'max:255', 'required_without:cover_image'],
            'web_cover' => ['nullable', 'string', 'max:255', 'required_without:web_cover_image'],
            'cover_image' => ['nullable', 'image', 'max:4096'],
            'web_cover_image' => ['nullable', 'image', 'max:4096'],
            'preview' => ['nullable', 'string', 'max:255'],
            'preview_video' => ['nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/x-matroska', 'max:512000'],
            'description' => ['required', 'string', 'max:255'],
            'details' => ['required', 'string'],
            'is_vip' => ['required', 'boolean'],
            'active' => ['required', 'boolean'],
            'duration' => ['required', 'integer', 'min:0', 'max:255'],
            'background_color' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'fee' => ['required', 'integer', 'min:0'],
            'enroll' => ['required', 'integer', 'min:0'],
            'rating' => ['required', 'numeric', 'min:0', 'max:5'],
            'major' => ['required', 'string', 'max:50', Rule::in($allowedMajors)],
            'sorting' => ['required', 'integer', 'min:0'],
            'certificate_code' => ['required', 'string', 'max:255'],
        ]);

        $coverUrl = trim((string) ($data['cover_url'] ?? ''));
        $webCoverUrl = trim((string) ($data['web_cover'] ?? ''));
        $previewUrl = trim((string) ($data['preview'] ?? ''));
        if ($request->hasFile('cover_image')) {
            $coverUrl = $this->storeCourseImage($request->file('cover_image'), 'courses/covers');
        }
        if ($request->hasFile('web_cover_image')) {
            $webCoverUrl = $this->storeCourseImage($request->file('web_cover_image'), 'courses/web-covers');
        }
        if ($request->hasFile('preview_video')) {
            $previewUrl = $this->storeCourseVideo($request->file('preview_video'), 'courses/previews');
        }

        return [
            'teacher_id' => (int) $data['teacher_id'],
            'title' => trim($data['title']),
            'certificate_title' => trim($data['certificate_title']),
            'lessons_count' => (int) $data['lessons_count'],
            'cover_url' => $coverUrl,
            'web_cover' => $webCoverUrl,
            'description' => trim($data['description']),
            'details' => trim($data['details']),
            'is_vip' => (int) $data['is_vip'],
            'active' => (int) $data['active'],
            'duration' => (int) $data['duration'],
            'background_color' => strtoupper(trim($data['background_color'])),
            'fee' => (int) $data['fee'],
            'enroll' => (int) $data['enroll'],
            'rating' => (float) $data['rating'],
            'major' => trim($data['major']),
            'sorting' => (int) $data['sorting'],
            'preview' => $previewUrl,
            'certificate_code' => trim($data['certificate_code']),
        ];
    }

    private function storeCourseImage(?UploadedFile $file, string $directory): string
    {
        if (!$file) {
            return '';
        }
        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: 'jpg'));
        $fileName = Str::uuid()->toString() . '.' . $extension;
        $path = trim($directory, '/') . '/' . $fileName;
        Storage::disk('uploads')->put($path, file_get_contents($file->getRealPath()));
        return env('APP_URL') . Storage::disk('uploads')->url($path);
    }

    private function storeCourseVideo(?UploadedFile $file, string $directory): string
    {
        if (!$file) {
            return '';
        }
        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: 'mp4'));
        $fileName = Str::uuid()->toString() . '.' . $extension;
        $path = trim($directory, '/') . '/' . $fileName;
        Storage::disk('uploads')->put($path, file_get_contents($file->getRealPath()));
        return env('APP_URL') . Storage::disk('uploads')->url($path);
    }
}
