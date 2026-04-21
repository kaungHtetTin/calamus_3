<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\LessonCategory;
use App\Models\Lesson;
use App\Models\VipUser;
use App\Models\UserData;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdditionalLessonController extends Controller
{
    use ApiResponse;

    /**
     * Get courses for additional lessons
     */
    public function getCourses(Request $request)
    {
        $channel = strtolower(trim((string) $request->input('channel', 'english')));

        $courses = Course::query()
            ->from('courses as c')
            ->join('lessons_categories as lc', function ($join) use ($channel) {
                $join->on('lc.course_id', '=', 'c.course_id')
                    ->where('lc.major', '=', $channel);
            })
            ->whereRaw("LOWER(COALESCE(c.major, '')) = ?", ['not'])
            ->where('c.course_id', '!=', 9)
            ->distinct()
            ->orderByDesc('c.sorting')
            ->orderByDesc('c.course_id')
            ->get([
                'c.course_id',
                'c.title',
            ]);

        $formattedCourses = [];

        foreach ($courses as $course) {
            $categories = LessonCategory::where('course_id', $course->course_id)
                ->where('major', $channel)
                ->orderByDesc('sort_order')
                ->orderBy('id')
                ->get();

            if ($categories->isNotEmpty()) {
                $formattedCategories = $categories->map(function ($cat) {
                    return [
                        'id' => (int)$cat->id,
                        'courseId' => (int)$cat->course_id,
                        'category' => $cat->category ?? '', // Check if column exists, legacy used 'category'
                        'categoryTitle' => $this->ensureUtf8($cat->category_title),
                        'imageUrl' => $cat->image_url ?? '',
                        'sortOrder' => (int)($cat->sort_order ?? 0),
                        'major' => $cat->major ?? '',
                    ];
                });

                $formattedCourses[] = [
                    'courseId' => (int)$course->course_id,
                    'title' => $this->ensureUtf8($course->title),
                    'categories' => $formattedCategories,
                ];
            }
        }

        return $this->successResponse(
            $formattedCourses,
            200,
            [
                'channel' => $channel,
                'total' => count($formattedCourses)
            ]
        );
    }

    /**
     * Get lessons for a category
     */
    public function getLessons(Request $request)
    {
        $categoryId = (int)$request->input('categoryId');
        
        $authUser = auth('sanctum')->user();
        $uid = $authUser ? (string)$authUser->user_id : '';

        if ($categoryId <= 0) {
            return $this->errorResponse('Category ID is required', 400);
        }

        // Get category details
        $category = LessonCategory::find($categoryId);

        if (!$category) {
            return $this->errorResponse('Category not found', 404);
        }

        $courseId = (int)$category->course_id;

        // Get Course Info (VIP status)
        $course = Course::find($courseId);
        $courseIsVip = $course ? (int)$course->is_vip : 1; // Default to 1 if not found

        // Check VIP Access to Course
        $hasVipAccess = false;
        if (!empty($uid)) {
            $hasVipAccess = VipUser::where('course_id', $courseId)
                ->where('user_id', $uid)
                ->exists();
        }

        // Fetch Lessons
        $lessons = Lesson::where('category_id', $categoryId)->get();
        
        $major =  $category->major;

        // Check a bluemake user
        $isVipUser = UserData::where('major', $major)
            ->where('user_id', $uid)->where('is_vip', 1)->first();
        $downloadAccess = $isVipUser ? true : false;

        $learnedLookup = [];
        if (!empty($uid) && $lessons->isNotEmpty()) {
            $learnedIds = DB::table('studies')
                ->where('user_id', $uid)
                ->whereIn('lesson_id', $lessons->pluck('id')->all())
                ->pluck('lesson_id')
                ->all();
            $learnedLookup = array_fill_keys(array_map('intval', $learnedIds), true);
        }

        $formattedLessons = [];
        foreach ($lessons as $lesson) {
            $lessonIsVip = (int)$lesson->isVip;
            // Access Logic
            $hasAccess = false;
            if ($courseIsVip === 0) {
                $hasAccess = true;
            } else {
                if ($lessonIsVip === 0) {
                    $hasAccess = true;
                } else {
                    if ($hasVipAccess) {
                        $hasAccess = true;
                    }
                }

                if(!$hasVipAccess) $downloadAccess = false;
            }

            $formattedLessons[] = [
                'id' => (int)$lesson->id,
                'title' => $this->ensureUtf8($lesson->title),
                'titleMini' => $this->ensureUtf8($lesson->title_mini ?? ''),
                'cate' => $category->category ?? '',
                'isVideo' => (int)$lesson->isVideo,
                'isVip' => $lessonIsVip,
                'learned' => isset($learnedLookup[(int)$lesson->id]) ? 1 : 0,
                'date' => $lesson->date, // Keep as is
                'thumbnail' => $lesson->thumbnail,
                'imageUrl' => $category->image_url,
                'duration' => (int)$lesson->duration,
                'hasAccess' => $hasAccess,
                'hasDownloadAccess' => (bool) $downloadAccess,
                'downloadAccess' => $downloadAccess,
            ];
        }

        return $this->successResponse(
            [
                'category' => [
                    'id' => (int)$category->id,
                    'courseId' => (int)$category->course_id,
                    'categoryTitle' => $this->ensureUtf8($category->category_title),
                    'imageUrl' => $category->image_url,
                    'major' => $category->major,
                ],
                'lessons' => $formattedLessons,
            ],
            200,
            ['totalLessons' => count($formattedLessons)]
        );
    }

    private function ensureUtf8($str)
    {
        if ($str === null || $str === '') return (string)$str;
        if (!mb_check_encoding($str, 'UTF-8')) return mb_convert_encoding($str, 'UTF-8', 'auto');
        return $str;
    }
}
