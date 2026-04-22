<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Teacher;
use App\Models\VipUser;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;

class StatController extends Controller
{
    use ApiResponse;

    public function home()
    {
        try {
            // Total courses
            $totalCourses = Course::where('background_color', '!=', '')->count();

            // Total lessons (video vs document)
            $lessonStats = DB::table('lessons')
                ->select(DB::raw('COUNT(*) as total'), DB::raw('SUM(CASE WHEN isVideo = 1 THEN 1 ELSE 0 END) as video_count'), DB::raw('SUM(CASE WHEN isVideo = 0 THEN 1 ELSE 0 END) as document_count'))
                ->first();
            
            $totalLessons = (int)$lessonStats->total;
            $videoLessons = (int)$lessonStats->video_count;
            $documentLessons = (int)$lessonStats->document_count;

            // Total instructors
            $totalInstructors = DB::table('courses')
                ->where('background_color', '!=', '')
                ->distinct('teacher_id')
                ->count('teacher_id');

            // Total enrolled students
            $totalStudents = DB::table('vipusers')->distinct('phone')->count('phone');

            // Average rating
            $avgRating = DB::table('courses')
                ->where('rating', '>', 0)
                ->where('background_color', '!=', '')
                ->avg('rating');
            $avgRating = round((float)$avgRating, 1);

            // Rating count
            $ratingCount = DB::table('courses')
                ->where('rating', '>', 0)
                ->where('background_color', '!=', '')
                ->count();

            // Top instructors
            $topInstructorsQuery = "
                SELECT 
                    t.id,
                    t.name,
                    t.profile,
                    COUNT(c.course_id) as course_count,
                    AVG(c.rating) as avg_rating
                FROM teachers t
                JOIN courses c ON c.teacher_id = t.id
                WHERE c.background_color != ''
                GROUP BY t.id, t.name, t.profile
                ORDER BY avg_rating DESC, course_count DESC
                LIMIT 5
            ";
            $instructorsData = DB::select($topInstructorsQuery);

            $topInstructors = array_map(function ($instructor) {
                return [
                    'id' => (int)$instructor->id,
                    'name' => $instructor->name,
                    'image' => $instructor->profile,
                    'courseCount' => (int)$instructor->course_count,
                ];
            }, $instructorsData);

            return $this->successResponse([
                'totalCourses' => $totalCourses,
                'totalLessons' => $totalLessons,
                'videoLessons' => $videoLessons,
                'documentLessons' => $documentLessons,
                'totalInstructors' => $totalInstructors,
                'totalStudents' => $totalStudents,
                'rating' => [
                    'average' => $avgRating,
                    'count' => $ratingCount,
                ],
                'topInstructors' => $topInstructors,
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch stats. Error: '.$e->getMessage(), 500);
        }
    }
}
