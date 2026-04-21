<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\UserData;
use App\Models\LessonCategory;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;

class VideoChannelController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            $channel = strtolower(trim((string)$request->query('channel', '')));
            $user = auth('sanctum')->user();
            $userId = $user ? $user->user_id : null;

            if ($channel === '') {
                return $this->errorResponse('Missing channel parameter', 400);
            }

            $language = Language::where('code', $channel)->first();
            if (!$language) {
                return $this->errorResponse('Language not found', 404);
            }

            // Get video channels/categories
            // Legacy query: SELECT * FROM lessons_categories WHERE course_id=9 AND major='$channel' ORDER BY sort_order DESC
            $videoChannels = LessonCategory::where('course_id', 9)
                ->where('major', $channel)
                ->orderBy('sort_order', 'desc')
                ->get();

            $uid = $user ? (string)$user->user_id : '';
            $isVipUser = UserData::where('major', $channel)
            ->where('user_id', $uid)->where('is_vip', 1)->first();
            $downloadAccess = $isVipUser ? true : false;

            $formattedCategories = [];
            foreach ($videoChannels as $cat) {
                // Get lessons for each category
                $lessons = DB::table('lessons')
                    ->select(
                        'lessons.id',
                        'lessons.title as lesson_title',
                        'lessons.duration',
                        'lessons.isVip',
                        'lessons.thumbnail',
                        'lessons.view_count',
                        'lessons.share_count',
                        'lessons.like_count',
                        'lessons.comment_count'
                    )
                    ->where('lessons.category_id', $cat->id)
                    ->orderBy('lessons.id', 'desc')
                    ->get();

                $formattedLessons = [];
                $learnedLessonMap = [];

                if (!empty($userId)) {
                    $lessonIds = $lessons->pluck('id')->all();
                    if (!empty($lessonIds)) {
                        $learnedLessonMap = DB::table('studies')
                            ->where('user_id', $userId)
                            ->whereIn('lesson_id', $lessonIds)
                            ->pluck('lesson_id')
                            ->flip()
                            ->all();
                    }
                }

                foreach ($lessons as $index => $lesson) {
                    $formattedLessons[] = [
                        'id' => (int)$lesson->id,
                        'index' => $index,
                        'title' => $this->ensureUtf8($lesson->lesson_title),
                        'duration' => (int)$lesson->duration,
                        'formattedDuration' => $this->formatVideoDuration((int)$lesson->duration),
                        'thumbnail' => $lesson->thumbnail,
                        'viewCount' => (int)$lesson->view_count,
                        'formattedViewCount' => $this->formatViewCount((int)$lesson->view_count),
                        'likeCount' => (int)$lesson->like_count,
                        'commentCount' => (int)$lesson->comment_count,
                        'shareCount' => (int)$lesson->share_count,
                        'isLearned' => isset($learnedLessonMap[$lesson->id]) ? 1 : 0,
                        'hasAccess' => true,
                        'hasDownloadAccess' => (bool) $downloadAccess,
                        'downloadAccess' => $downloadAccess,
                    ];
                }

                $formattedCategories[] = [
                    'id' => (int)$cat->id,
                    'category' => $cat->category,
                    'title' => $this->ensureUtf8($cat->category_title),
                    'lessonsCount' => count($formattedLessons),
                    'lessons' => $formattedLessons,
                ];
            }

            $formattedApp = [
                'id' => (int)$language->id,
                'name' => $this->ensureUtf8($language->display_name ?: $language->name),
                'description' => $this->ensureUtf8($language->certificate_title ?? ''),
                'icon' => $language->image_path ?? null,
            ];

            return $this->successResponse(
                [
                    'app' => $formattedApp,
                    'categories' => $formattedCategories,
                ],
                200,
                ['totalCategories' => count($formattedCategories)]
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Server error', 500);
        }
    }

    public function show(Request $request)
    {
        
        try {
            $lessonId = (int)$request->query('id');

            $user = auth('sanctum')->user();
            $userId = $user ? $user->user_id : null;

            if ($lessonId <= 0) {
                return $this->errorResponse('Missing or invalid video id', 400);
            }

            // Get lesson details
            $lesson = DB::table('lessons')
                ->join('lessons_categories as lc', 'lc.id', '=', 'lessons.category_id')
                ->join('courses as c', 'c.course_id', '=', 'lc.course_id')
                ->select(
                    'lessons.id',
                    'lessons.title as lesson_title',
                    'lessons.duration',
                    'lessons.isVip',
                    'lessons.thumbnail',
                    'lessons.category_id',
                    'lessons.link',
                    'lessons.view_count',
                    'lessons.share_count',
                    'lessons.like_count',
                    'lessons.comment_count',
                    'lc.course_id',
                )
                ->where('lessons.id', $lessonId)
                ->whereRaw("LOWER(COALESCE(c.major, '')) = ?", ['not'])
                ->first();

            if (!$lesson) {
                return $this->errorResponse('Video not found', 404);
            }

            // Get category details
            $category = LessonCategory::find($lesson->category_id);

            // Get all videos in same category for related list
            $allLessons = DB::table('lessons')
                ->join('lessons_categories as lc', 'lc.id', '=', 'lessons.category_id')
                ->join('courses as c', 'c.course_id', '=', 'lc.course_id')
                ->select(
                    'lessons.id',
                    'lessons.title as lesson_title',
                    'lessons.duration',
                    'lessons.thumbnail',
                    'lessons.view_count',
                    'lessons.link'
                )
                ->where('lessons.category_id', $lesson->category_id)
                ->whereRaw("LOWER(COALESCE(c.major, '')) = ?", ['not'])
                ->orderBy('lessons.id', 'desc')
                ->get();

            $learnedLessonMap = [];
            if (!empty($userId) && $allLessons->isNotEmpty()) {
                $lessonIds = $allLessons->pluck('id')->all();
                $learnedLessonMap = DB::table('studies')
                    ->where('user_id', $userId)
                    ->whereIn('lesson_id', $lessonIds)
                    ->pluck('lesson_id')
                    ->flip()
                    ->all();
            }

            $formattedRelated = [];
            $currentIndex = 0;
            
            // Re-index to find current index and format
            $allLessons = $allLessons->values();
            foreach ($allLessons as $index => $item) {
                if ($item->id == $lessonId) {
                    $currentIndex = $index;
                }
                $formattedRelated[] = [
                    'id' => (int)$item->id,
                    'title' => $this->ensureUtf8($item->lesson_title),
                    'duration' => (int)$item->duration,
                    'formattedDuration' => $this->formatVideoDuration((int)$item->duration),
                    'thumbnail' => $item->thumbnail,
                    'viewCount' => (int)$item->view_count,
                    'formattedViewCount' => $this->formatViewCount((int)$item->view_count),
                    'vimeoId' => $item->link,
                    'isLearned' => isset($learnedLessonMap[$item->id]) ? 1 : 0,
                ];
            }

            $prevVideo = $currentIndex > 0 ? $formattedRelated[$currentIndex - 1] : null;
            $nextVideo = $currentIndex < count($formattedRelated) - 1 ? $formattedRelated[$currentIndex + 1] : null;

            $isLiked = 0;
            if (!empty($userId)) {
                $isLiked = DB::table('lesson_likes')
                    ->where('lesson_id', (int)$lesson->id)
                    ->where('user_id', (string)$userId)
                    ->exists() ? 1 : 0;
            }

            $currentVideo = [
                'id' => (int)$lesson->id,
                'title' => $this->ensureUtf8($lesson->lesson_title),
                'duration' => (int)$lesson->duration,
                'formattedDuration' => $this->formatVideoDuration((int)$lesson->duration),
                'thumbnail' => $lesson->thumbnail,
                'viewCount' => (int)$lesson->view_count,
                'formattedViewCount' => $this->formatViewCount((int)$lesson->view_count),
                'likeCount' => (int)$lesson->like_count,
                'isLiked' => $isLiked,
                'commentCount' => (int)$lesson->comment_count,
                'shareCount' => (int)$lesson->share_count,
                'vimeoId' => $lesson->link,
                'userId' => $userId,
                'isLearned' => isset($learnedLessonMap[$lesson->id]) ? 1 : 0,
                'category' => $category ? $this->ensureUtf8($category->category_title) : '',
            ];

            return $this->successResponse(
                [
                    'currentVideo' => $currentVideo,
                    'prevVideo' => $prevVideo,
                    'nextVideo' => $nextVideo,
                    'relatedVideos' => $formattedRelated,
                ],
                200,
                ['totalRelated' => count($formattedRelated)]
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Server error', 500);
        }
    }

    private function ensureUtf8($string)
    {
        return mb_convert_encoding($string ?? '', 'UTF-8', 'UTF-8');
    }

    private function formatVideoDuration($duration)
    {
        if ($duration < 60) {
            if ($duration < 10) {
                return "00:0" . $duration;
            } else {
                return "00:" . $duration;
            }
        } else if ($duration < 3600) {
            $min = floor($duration / 60);
            $sec = $duration % 60;

            if ($min < 10) $min = "0" . $min;
            if ($sec < 10) $sec = "0" . $sec;
            return $min . ":" . $sec;
        } else {
            // Fallback for > 1 hour (legacy didn't handle explicitly but returning nothing is bad)
            return gmdate("H:i:s", $duration);
        }
    }

    private function formatViewCount($count)
    {
        if ($count <= 0) {
            return "No view";
        } else if ($count == 1) {
            return "1 view";
        } else if ($count > 1 && $count < 999) {
            return $count . " views";
        } else if ($count > 999 && $count < 999999) {
            $count = round($count / 1000, 1);
            return $count . "k views";
        } else {
            $count = round($count / 1000000, 1);
            return $count . "M views";
        }
    }
}
