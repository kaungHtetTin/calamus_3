<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SaveReply;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AdminSaveReplyController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $page = (int) $request->query('page', 1);
        if ($page < 1) $page = 1;

        $limit = (int) $request->query('limit', 50);
        if ($limit < 1) $limit = 50;
        if ($limit > 200) $limit = 200;

        $q = trim((string) $request->query('q', ''));

        $query = SaveReply::query();
        if ($q !== '') {
            $needle = mb_strtolower($q);
            $query->where(function ($sub) use ($needle) {
                $sub->whereRaw('LOWER(title) LIKE ?', ['%' . $needle . '%'])
                    ->orWhereRaw('LOWER(message) LIKE ?', ['%' . $needle . '%']);
            });
        }

        $total = (int) (clone $query)->count();
        $items = $query
            ->orderByDesc('id')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get(['id', 'title', 'message']);

        return $this->successResponse($items, 200, $this->paginate($total, $page, $limit));
    }

    public function show(int $id)
    {
        $item = SaveReply::query()->find($id, ['id', 'title', 'message']);
        if (! $item) {
            return $this->errorResponse('Not found.', 404);
        }

        return $this->successResponse($item);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $payload = [
            'title' => trim((string) $data['title']),
            'message' => trim((string) $data['message']),
        ];

        if (Schema::hasColumn('save_replies', 'major')) {
            $payload['major'] = $this->fallbackMajor();
        }

        $item = SaveReply::query()->create($payload);

        return $this->successResponse($item->only(['id', 'title', 'message']), 201);
    }

    public function update(Request $request, int $id)
    {
        $item = SaveReply::query()->find($id);
        if (! $item) {
            return $this->errorResponse('Not found.', 404);
        }

        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:120'],
            'message' => ['sometimes', 'required', 'string', 'max:5000'],
        ]);

        $payload = [];
        if ($request->has('title')) {
            $payload['title'] = trim((string) $data['title']);
        }
        if ($request->has('message')) {
            $payload['message'] = trim((string) $data['message']);
        }

        if ($payload === []) {
            return $this->successResponse($item->only(['id', 'title', 'message']));
        }

        $item->update($payload);

        return $this->successResponse($item->only(['id', 'title', 'message']));
    }

    public function destroy(int $id)
    {
        $item = SaveReply::query()->find($id);
        if (! $item) {
            return $this->errorResponse('Not found.', 404);
        }

        $item->delete();

        return $this->successResponse(['deleted' => true]);
    }

    private function fallbackMajor(): string
    {
        $major = trim((string) SaveReply::query()->value('major'));
        return $major !== '' ? $major : 'english';
    }
}

