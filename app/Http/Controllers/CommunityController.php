<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class CommunityController extends Controller
{
    use ApiResponse;

    /**
     * Get community links filtered by major
     */
    public function index(Request $request)
    {

       
        $major = $request->query('major');

        $query = Community::where('active', 1);

        if ($major) {
            $query->where('major', $major);
        }

        $communities = $query->orderBy('sort_order')
            ->orderBy('major')
            ->get();

        return $this->successResponse($communities);
    }
}
