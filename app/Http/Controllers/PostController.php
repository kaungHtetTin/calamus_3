<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Services\PostService;
use App\Traits\ApiResponse;

class PostController extends Controller
{
    use ApiResponse;

    protected $postService;

    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }

    public function pinned()
    {
        try {
            $data = $this->postService->getPinnedPosts();
            return $this->successResponse($data, 200, ['total' => count($data)]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch pinned posts', 500);
        }
    }
}
