<?php

namespace App\Http\Controllers\MiniProgram;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class ExamController extends Controller
{
    /**
     * Show the main exam list for a major
     */
    public function index(Request $request)
    {
        $major = $request->query('major', 'korea');
        $userId = $request->query('userId');

        $viewName = "mini-program.exams.{$major}.main";

        if (!View::exists($viewName)) {
            abort(404, "Exam list for {$major} not found.");
        }

        return view($viewName, [
            'major' => $major,
            'userId' => $userId,
        ]);
    }

    /**
     * Show a specific exam
     */
    public function show(Request $request, $major, $category, $id)
    {
        $userId = $request->query('userId');
        
        // Simple and standard naming: category as folder, id as filename
        $viewName = "mini-program.exams.{$major}.{$category}.{$id}";
        
        if (!View::exists($viewName)) {
            abort(404, "Exam {$category} {$id} for {$major} not found.");
        }

        return view($viewName, [
            'major' => $major,
            'category' => $category,
            'id' => $id,
            'userId' => $userId,
        ]);
    }
}
