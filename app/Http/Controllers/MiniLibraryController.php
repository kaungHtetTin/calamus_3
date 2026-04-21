<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LibraryBook;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;

class MiniLibraryController extends Controller
{
    use ApiResponse;

    public function books(Request $request)
    {
        try {
            $major = strtolower(trim($request->query('major', 'english')));
            $category = trim($request->query('category', ''));

            $allowedMajors = ['english', 'korea', 'korean', 'chinese', 'japanese', 'russian'];
            if (!in_array($major, $allowedMajors, true)) {
                $major = 'english';
            }

            if ($category === '') {
                return $this->errorResponse('Category is required', 400); // Legacy returns 200 with error field, but standard is 400. I'll stick to legacy error response shape but maybe 200 code if strict compatibility needed. Legacy code: echo json_encode(['success' => false, 'error' => '...']); exit(); - this is 200 OK by default unless http_response_code is set.
                // The legacy code doesn't set 400. It just echoes error.
                // My ApiResponse trait sets 400 for errorResponse by default.
                // I will use errorResponse but check if client relies on 200. Usually 400 is better.
            }

            $books = LibraryBook::where('category', $category)
                ->where('major', $major)
                ->orderBy('created_at', 'desc')
                ->get();

            $data = $books->map(function ($book) use ($major) {
                return [
                    'id' => (int)$book->id,
                    'title' => $book->title ?? '',
                    'pdfPath' => $book->pdf_file ?? '',
                    'coverImage' => $book->cover_image ?? null,
                    'category' => $book->category ?? '',
                    'major' => $book->major ?? $major,
                    'createdAt' => $book->created_at,
                ];
            });

            return $this->successResponse(
                $data,
                200,
                [
                    'major' => $major,
                    'category' => $category,
                    'total' => $data->count()
                ]
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Server error', 500);
        }
    }

    public function categories(Request $request)
    {
        try {
            $major = strtolower(trim($request->query('major', 'english')));
            $allowedMajors = ['english', 'korea', 'korean', 'chinese', 'japanese', 'russian'];
            if (!in_array($major, $allowedMajors, true)) {
                $major = 'english';
            }

            $categories = LibraryBook::select('category as name', DB::raw('COUNT(*) as bookCount'))
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->where('major', $major)
                ->groupBy('category')
                ->orderBy('name', 'asc')
                ->get();

            $data = $categories->map(function ($cat) {
                return [
                    'name' => $cat->name,
                    'bookCount' => (int)$cat->bookCount,
                ];
            });

            return $this->successResponse(
                $data,
                200,
                [
                    'major' => $major,
                    'total' => $data->count()
                ]
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Server error', 500);
        }
    }
}
