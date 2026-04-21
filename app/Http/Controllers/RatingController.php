<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rating;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;

class RatingController extends Controller
{
    use ApiResponse;

    /**
     * Get rating breakdown for a course (star counts and percentages)
     */
    public function breakdown(Request $request)
    {
        try {
            $courseId = $request->query('courseId') ?? $request->query('course_id');
            if (!$courseId) {
                return $this->errorResponse('Course ID is required', 400);
            }

            // Get counts for each star (1-5)
            $stats = Rating::where('course_id', $courseId)
                ->select('star', DB::raw('count(*) as count'))
                ->groupBy('star')
                ->get()
                ->pluck('count', 'star')
                ->toArray();

            $total = array_sum($stats);
            $breakdown = [];

            for ($i = 5; $i >= 1; $i--) {
                $count = $stats[$i] ?? 0;
                $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                $breakdown[] = [
                    'star' => $i,
                    'count' => $count,
                    'percentage' => $percentage
                ];
            }

            // Get average and total
            $average = $total > 0 ? round(Rating::where('course_id', $courseId)->avg('star'), 1) : 0;

            return $this->successResponse([
                'courseId' => (int)$courseId,
                'averageRating' => (float)$average,
                'totalRatings' => $total,
                'breakdown' => $breakdown
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch rating breakdown', 500);
        }
    }

    /**
     * Get all ratings for a course
     */
    public function index(Request $request)
    {
        try {
            $courseId = $request->query('courseId') ?? $request->query('course_id');
            if (!$courseId) {
                return $this->errorResponse('Course ID is required', 400);
            }

            $limit = min(50, max(1, (int)$request->query('limit', 20)));
            $page = max(1, (int)$request->query('page', 1));
            $sort = $request->query('sort', 'recent');

           
            $query = Rating::where('course_id', $courseId)
                ->leftjoin('learners', 'learners.user_id', '=', 'ratings.user_id')
                ->select(
                    'ratings.*',
                    'learners.learner_name',
                    'learners.learner_image'
                );

            // Sorting logic
            switch ($sort) {
                case 'highest':
                    $query->orderBy('ratings.star', 'desc')->orderBy('ratings.time', 'desc');
                    break;
                case 'lowest':
                    $query->orderBy('ratings.star', 'asc')->orderBy('ratings.time', 'desc');
                    break;
                case 'recent':
                default:
                    $query->orderBy('ratings.time', 'desc');
                    break;
            }

            $paginated = $query->paginate($limit, ['*'], 'page', $page);

            $formatted = $paginated->map(function ($review) {
                return [
                    'id' => (int)$review->id,
                    'courseId' => (int)$review->course_id,
                    'learnerName' => $this->ensureUtf8($review->learner_name),
                    'learnerImage' => $review->learner_image,
                    'star' => (int)$review->star,
                    'review' => $this->ensureUtf8($review->review),
                    'time' => (int)$review->time,
                    'formattedTime' => $this->formatDateTime((int)$review->time)
                ];
            });

            return $this->successResponse(
                $formatted,
                200,
                $this->paginate($paginated->total(), $page, $limit)
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch reviews', 500);
        }
    }

    /**
     * Create or update a rating
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return $this->errorResponse('Not authenticated', 401);
            }

            $courseId = $request->input('courseId') ?? $request->input('courseId');
            $star = (int)$request->input('star');
            $reviewText = $request->input('review', '');

            if (!$courseId || !$star) {
                return $this->errorResponse('Course ID and star rating are required', 400);
            }

            // Legacy uses user_id as user_id in ratings table
            $userId = $user->user_id;

            $rating = Rating::updateOrCreate(
                ['user_id' => $userId, 'course_id' => $courseId],
                [
                    'star' => $star,
                    'review' => $reviewText,
                    'time' => round(microtime(true) * 1000) // Milliseconds timestamp
                ]
            );

            // Update course average rating (Legacy pattern)
            $avgRating = Rating::where('course_id', $courseId)->avg('star');
            DB::table('courses')->where('course_id', $courseId)->update(['rating' => round($avgRating, 1)]);

            return $this->successResponse([
                'message' => 'Rating saved successfully',
                'data' => $rating
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to save rating: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete a rating
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return $this->errorResponse('Not authenticated', 401);
            }

            $rating = Rating::find($id);
            if (!$rating) {
                return $this->errorResponse('Rating not found', 404);
            }

            // Ensure user owns the rating
            if ((string)$rating->user_id !== (string)$user->user_id) {
                return $this->errorResponse('Unauthorized', 403);
            }

            $courseId = $rating->course_id;
            $rating->delete();

            // Recalculate course average rating
            $avgRating = Rating::where('course_id', $courseId)->avg('star') ?: 0;
            DB::table('courses')->where('course_id', $courseId)->update(['rating' => round($avgRating, 1)]);

            return $this->successResponse(['message' => 'Rating deleted successfully']);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete rating', 500);
        }
    }

    /**
     * Delete a rating (body-based endpoint for clients that do not use DELETE with path params)
     */
    public function delete(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return $this->errorResponse('Not authenticated', 401);
            }

            $id = (int)($request->input('id') ?? $request->input('ratingId'));
            if ($id <= 0) {
                return $this->errorResponse('Rating ID is required', 400);
            }

            $rating = Rating::find($id);
            if (!$rating) {
                return $this->errorResponse('Rating not found', 404);
            }

            if ((string)$rating->user_id !== (string)$user->user_id) {
                return $this->errorResponse('Unauthorized', 403);
            }

            $courseId = $rating->course_id;
            $rating->delete();

            $avgRating = Rating::where('course_id', $courseId)->avg('star') ?: 0;
            DB::table('courses')->where('course_id', $courseId)->update(['rating' => round($avgRating, 1)]);

            return $this->successResponse([
                'message' => 'Rating deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete rating', 500);
        }
    }

    public function latest(Request $request)
    {
        try {
            $limit = (int)$request->query('limit', 6);
            $limit = min(20, max(1, $limit));

            $reviews = DB::table('ratings')
                ->join('learners', 'learners.learner_phone', '=', 'ratings.user_id')
                ->join('courses', 'courses.course_id', '=', 'ratings.course_id')
                ->where('ratings.review', '!=', '')
                ->whereNotNull('ratings.review')
                ->orderBy('ratings.time', 'desc')
                ->limit($limit)
                ->select(
                    'ratings.id',
                    'ratings.course_id',
                    'ratings.user_id',
                    'ratings.time',
                    'ratings.star',
                    'ratings.review',
                    'learners.learner_name',
                    'learners.learner_phone',
                    'learners.learner_image',
                    'courses.title as course_title',
                    'courses.web_cover as course_image',
                    'courses.major as course_major'
                )
                ->get();

            $formattedReviews = $reviews->map(function ($review) {
                return [
                    'id' => (int)$review->id,
                    'courseId' => (int)$review->course_id,
                    'courseTitle' => $this->ensureUtf8($review->course_title),
                    'courseImage' => $review->course_image,
                    'courseMajor' => $review->course_major,
                    'learnerName' => $this->ensureUtf8($review->learner_name),
                    'learnerImage' => $review->learner_image,
                    'learnerPhone' => $review->learner_phone,
                    'star' => (int)$review->star,
                    'review' => $this->ensureUtf8($review->review),
                    'time' => (int)$review->time,
                    'formattedTime' => $this->formatDateTime((int)$review->time)
                ];
            });

            return $this->successResponse($formattedReviews);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch latest reviews', 500);
        }
    }

    private function ensureUtf8($string)
    {
        return mb_convert_encoding($string ?? '', 'UTF-8', 'UTF-8');
    }

    private function formatDateTime($time)
    {
        $time = $time / 1000;
        return date('M d, Y', $time);
    }
}
