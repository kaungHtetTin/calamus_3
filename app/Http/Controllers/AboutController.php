<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Teacher;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\VipUser;
use App\Models\Learner;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;

class AboutController extends Controller
{
    use ApiResponse;

    /**
     * Get stats for About Us page
     */
    public function stats()
    {
        try {
            $instructors = Teacher::count();
            $courses = Course::where('background_color', '!=', '')->count();
            $lectures = Lesson::count();
            $enrollments = VipUser::count();
            $members = Learner::count();

          

            return $this->successResponse([
                'instructors' => $instructors,
                'courses' => $courses,
                'lessons' => $lectures,
                'enrollments' => $enrollments,
                'languages' => 2,
                'members' => $members,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Server error'.$e->getMessage(), 500);
        }
    }
}
