<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Models\Language;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CommunityController extends Controller
{
    public function index()
    {
        $communities = Community::query()
            ->orderBy('sort_order')
            ->orderBy('major')
            ->orderByDesc('id')
            ->get([
                'id',
                'name',
                'platform',
                'url',
                'major',
                'active',
                'sort_order',
                'created_at',
                'updated_at',
            ]);

        $languages = Language::query()
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name', 'display_name', 'image_path', 'primary_color']);

        return Inertia::render('Admin/Communities', [
            'communities' => $communities,
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
            'name' => ['required', 'string', 'max:255'],
            'platform' => ['required', 'string', 'max:50'],
            'url' => ['required', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ]);

        Community::create([
            'major' => strtolower(trim((string) $data['major'])),
            'name' => trim((string) $data['name']),
            'platform' => strtolower(trim((string) $data['platform'])),
            'url' => trim((string) $data['url']),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'active' => (bool) ($data['active'] ?? true),
        ]);

        return redirect()->back(303);
    }

    public function update(Request $request, Community $community)
    {
        $data = $request->validate([
            'major' => ['sometimes', 'required', 'string', 'max:20'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'platform' => ['sometimes', 'required', 'string', 'max:50'],
            'url' => ['sometimes', 'required', 'string', 'max:500'],
            'sort_order' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'active' => ['sometimes', 'nullable', 'boolean'],
        ]);

        $payload = [];

        if ($request->has('major')) {
            $payload['major'] = strtolower(trim((string) $data['major']));
        }
        if ($request->has('name')) {
            $payload['name'] = trim((string) $data['name']);
        }
        if ($request->has('platform')) {
            $payload['platform'] = strtolower(trim((string) $data['platform']));
        }
        if ($request->has('url')) {
            $payload['url'] = trim((string) $data['url']);
        }
        if ($request->has('sort_order')) {
            $payload['sort_order'] = (int) ($data['sort_order'] ?? 0);
        }
        if ($request->has('active')) {
            $payload['active'] = (bool) ($data['active'] ?? false);
        }

        if ($payload !== []) {
            $community->update($payload);
        }

        return redirect()->back(303);
    }

    public function destroy(Community $community)
    {
        $community->delete();
        return redirect()->back(303);
    }
}

