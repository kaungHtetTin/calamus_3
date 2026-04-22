<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Teacher;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;

class InstructorController extends Controller
{
    use ApiResponse;

    /**
     * Get all instructors with stats
     */
    public function index()
    {
        try {
            $query = "
                SELECT 
                    t.id,
                    t.name,
                    t.profile,
                    t.rank,
                    t.description,
                    COUNT(DISTINCT c.course_id) as course_count,
                    COALESCE(AVG(c.rating), 0) as avg_rating,
                    COALESCE(SUM(student_counts.student_count), 0) as total_students
                FROM teachers t
                LEFT JOIN courses c ON c.teacher_id = t.id AND c.background_color != ''
                LEFT JOIN (
                    SELECT course_id, COUNT(*) as student_count
                    FROM vipusers
                    WHERE deleted_account = 0
                    GROUP BY course_id
                ) student_counts ON student_counts.course_id = c.course_id
                GROUP BY t.id, t.name, t.profile, t.rank, t.description
                HAVING course_count > 0
                ORDER BY avg_rating DESC, course_count DESC
            ";

            $result = DB::select($query);

            $instructors = array_map(function ($row) {
                return [
                    'id' => (int)$row->id,
                    'name' => $row->name,
                    'image' => $row->profile,
                    'rank' => $row->rank,
                    'bio' => $this->ensureUtf8($row->description),
                    'courseCount' => (int)$row->course_count,
                    'avgRating' => round((float)$row->avg_rating, 1),
                    'totalStudents' => (int)$row->total_students,
                ];
            }, $result);

            return $this->successResponse($instructors, 200, ['total' => count($instructors)]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch instructors', 500);
        }
    }

    /**
     * Get instructor detail
     */
    public function show(Request $request)
    {
        $id = $request->query('id');
        try {
            $instructor = Teacher::find($id);

            if (!$instructor) {
                return $this->errorResponse('Instructor not found', 404);
            }

            // Get total students
            $totalStudentsQuery = "
                SELECT count(*) as total_student 
                FROM vipusers 
                JOIN courses USING (course_id) 
                JOIN teachers ON teachers.id = courses.teacher_id 
                WHERE teachers.id = ?
            ";
            $totalResult = DB::selectOne($totalStudentsQuery, [$id]);
            $total = $totalResult->total_student;
            
            // Format student count (e.g. 1.2K)
            if ($total > 1000) {
                $totalStudents = round($total / 1000, 1) . "K";
            } else {
                $totalStudents = $total;
            }

            // Get courses (limit 5 per legacy logic)
            $coursesQuery = "
                SELECT 
                    courses.course_id,
                    courses.title,
                    courses.duration,
                    courses.description,
                    courses.rating,
                    courses.cover_url,
                    courses.web_cover,
                    courses.background_color,
                    courses.fee,
                    courses.major,
                    courses.lessons_count
                FROM courses 
                WHERE courses.teacher_id = ?
                ORDER BY courses.course_id DESC 
                LIMIT 5
            ";
            $courses = DB::select($coursesQuery, [$id]);

            $formattedCourses = array_map(function ($course) {
                // Get enrolled students for this course
                    $enrolled = DB::table('vipusers')
                    ->where('course_id', $course->course_id)
                    ->where('deleted_account', 0)
                    ->count();

                return [
                    'id' => (int)$course->course_id,
                    'title' => $course->title,
                    'description' => $course->description,
                    'duration' => (int)$course->duration,
                    'rating' => (float)$course->rating,
                    'coverUrl' => $course->cover_url,
                    'webCover' => $course->web_cover,
                    'backgroundColor' => $course->background_color,
                    'fee' => (int)$course->fee,
                    'major' => $course->major,
                    'lessonsCount' => (int)$course->lessons_count,
                    'enrolledStudents' => $enrolled
                ];
            }, $courses);

            $data = [
                'id' => (int)$instructor->id,
                'name' => $instructor->name,
                'bio' => $this->ensureUtf8($instructor->description), // description maps to bio
                'profileImage' => $instructor->profile,
                'email' => $instructor->email,
                'specialty' => $instructor->specialty, // assuming this column exists, legacy code used it
                'totalStudents' => $totalStudents,
                'coursesCount' => count($formattedCourses),
                'courses' => $formattedCourses
            ];

            return $this->successResponse($data);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch instructor', 500);
        }
    }

    private function ensureUtf8($string)
    {
        return mb_convert_encoding($string ?? '', 'UTF-8', 'UTF-8');
    }
}
