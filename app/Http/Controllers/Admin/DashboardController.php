<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use Inertia\Inertia;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Display the admin dashboard.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $data = $this->dashboardService->getDashboardData();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $data['stats'],
            'recentUsers' => $data['recent_users'],
            'recentConversations' => $data['recent_conversations'] ?? [],
            'recentLessonCommentsByAdmin' => $data['recent_lesson_comments_by_admin'] ?? [],
        ]);
    }
}
