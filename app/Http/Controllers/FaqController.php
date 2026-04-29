<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class FaqController extends Controller
{
    use ApiResponse;

    public function get(Request $request)
    {
        if (!Schema::hasTable('faqs')) {
            return $this->successResponse([]);
        }

        $query = Faq::query()->orderBy('sort_order')->orderBy('id');

        if (Schema::hasColumn('faqs', 'active')) {
            $query->where('active', 1);
        }

        $select = ['id', 'question', 'answer', 'sort_order'];
        if (Schema::hasColumn('faqs', 'active')) {
            $select[] = 'active';
        }

        $rows = $query->get($select);

        return $this->successResponse($rows);
    }
}
