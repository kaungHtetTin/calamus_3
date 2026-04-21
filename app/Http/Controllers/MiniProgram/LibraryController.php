<?php

namespace App\Http\Controllers\MiniProgram;

use App\Http\Controllers\Controller;
use App\Models\LibraryBook;
use Illuminate\Http\Request;

class LibraryController extends Controller
{
    /**
     * Show categories for a specific major
     */
    public function index(Request $request)
    {
        $major = $request->query('major', 'korea');
        $userId = $request->query('userId');

        $categories = LibraryBook::where('major', $major)
            ->distinct()
            ->pluck('category');

        return view('mini-program.library.index', [
            'major' => $major,
            'userId' => $userId,
            'categories' => $categories,
        ]);
    }

    /**
     * Show books under a specific category
     */
    public function category(Request $request, $major, $category)
    {
        $userId = $request->query('userId');

        $books = LibraryBook::where('major', $major)
            ->where('category', $category)
            ->get();

        return view('mini-program.library.books', [
            'major' => $major,
            'category' => $category,
            'userId' => $userId,
            'books' => $books,
        ]);
    }
}
