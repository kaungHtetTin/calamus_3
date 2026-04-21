<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SaveReply;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SaveReplyController extends Controller
{
    public function index()
    {
        $saveReplies = SaveReply::query()
            ->orderByDesc('id')
            ->get(['id', 'title', 'message']);

        return Inertia::render('Admin/SaveReplies', [
            'saveReplies' => $saveReplies,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        SaveReply::create([
            'title' => trim((string) $data['title']),
            'message' => trim((string) $data['message']),
            'major' => $this->fallbackMajor(),
        ]);

        return redirect()->back(303);
    }

    public function update(Request $request, SaveReply $saveReply)
    {
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

        if ($payload !== []) {
            $saveReply->update($payload);
        }

        return redirect()->back(303);
    }

    public function destroy(SaveReply $saveReply)
    {
        $saveReply->delete();

        return redirect()->back(303);
    }

    public function options(): JsonResponse
    {
        $replies = SaveReply::query()
            ->orderBy('title')
            ->orderByDesc('id')
            ->get(['id', 'title', 'message']);

        return response()->json([
            'data' => $replies,
        ]);
    }

    private function fallbackMajor(): string
    {
        $major = trim((string) SaveReply::query()->value('major'));
        return $major !== '' ? $major : 'english';
    }
}
