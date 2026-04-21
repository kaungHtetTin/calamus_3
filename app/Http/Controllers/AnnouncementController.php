<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    use ApiResponse;

    /**
     * Get announcements
     */
    public function index(Request $request)
    {
        $major = $request->query('major');
        
        $query = Announcement::query();
        
        if ($major) {
            $query->where('major', $major);
        }
        
        $announcements = $query->orderBy('id', 'desc')->get();
        
        return $this->successResponse($announcements);
    }
}
