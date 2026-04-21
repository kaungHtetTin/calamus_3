<?php

namespace App\Http\Controllers\MiniProgram;

use App\Http\Controllers\Controller;
use App\Models\MiniProgram;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class MiniProgramController extends Controller
{
    use ApiResponse;

    /**
     * Get mini programs
     */
    public function index(Request $request)
    {
        $major = $request->query('major');

        $query = MiniProgram::query();

        if ($major) {
            $query->where('major', $major);
        }

        $miniPrograms = $query->orderBy('id', 'asc')->get();

        $formattedPrograms = $miniPrograms->map(function ($program) {
            return [
                'id' => (int)$program->id,
                'title' => $program->title,
                'linkUrl' => $program->link_url,
                'imageUrl' => $program->image_url,
                'major' => $program->major,
                'createdAt' => $program->created_at,
                'updatedAt' => $program->updated_at,
            ];
        });

        return $this->successResponse($formattedPrograms, 200, ['total' => $formattedPrograms->count()]);
    }
}
