<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Language;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::query()
            ->orderByDesc('id')
            ->get(['id', 'link', 'major', 'created_at', 'updated_at']);

        $languages = Language::query()
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name', 'display_name', 'image_path', 'primary_color']);

        return Inertia::render('Admin/Announcements', [
            'announcements' => $announcements,
            'languageOptions' => $languages->map(function ($l) {
                return [
                    'id' => (int) $l->id,
                    'code' => (string) ($l->code ?: $l->name),
                    'name' => (string) ($l->display_name ?: $l->name),
                    'image_path' => (string) ($l->image_path ?? ''),
                    'primary_color' => (string) ($l->primary_color ?? ''),
                ];
            })->values(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'major' => ['required', 'string', 'max:20'],
            'link' => ['required', 'string', 'max:500'],
        ]);

        Announcement::create([
            'major' => strtolower(trim((string) $data['major'])),
            'link' => trim((string) $data['link']),
            'is_seen' => [],
        ]);

        return redirect()->back(303);
    }

    public function update(Request $request, Announcement $announcement)
    {
        $data = $request->validate([
            'major' => ['sometimes', 'required', 'string', 'max:20'],
            'link' => ['sometimes', 'required', 'string', 'max:500'],
        ]);

        $payload = [];
        if ($request->has('major')) {
            $payload['major'] = strtolower(trim((string) $data['major']));
        }
        if ($request->has('link')) {
            $payload['link'] = trim((string) $data['link']);
        }

        if ($payload !== []) {
            $announcement->update($payload);
        }

        return redirect()->back(303);
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();
        return redirect()->back(303);
    }
}
