<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ApplicationsController extends Controller
{
    public function index()
    {
        $apps = App::query()
            ->orderByDesc('id')
            ->get([
                'id',
                'name',
                'description',
                'url',
                'cover',
                'icon',
                'type',
                'click',
                'show_on',
                'active_course',
                'student_learning',
                'major',
                'package_id',
                'platform',
                'latest_version_code',
                'latest_version_name',
                'min_version_code',
                'update_message',
                'force_update',
            ]);

        $languages = Language::query()
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name', 'display_name', 'image_path', 'primary_color']);

        return Inertia::render('Admin/Applications', [
            'apps' => $apps,
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
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'url' => ['required', 'string', 'max:500'],
            'major' => ['required', 'string', 'max:20'],
            'type' => ['nullable', 'string', 'max:50'],
            'click' => ['sometimes', 'integer', 'min:0'],
            'show_on' => ['sometimes', 'integer', 'min:0'],
            'active_course' => ['sometimes', 'integer', 'min:0'],
            'student_learning' => ['nullable', 'string', 'max:255'],
            'package_id' => ['nullable', 'string', 'max:150'],
            'platform' => ['nullable', 'string', 'in:android,ios'],
            'latest_version_code' => ['nullable', 'integer', 'min:0'],
            'latest_version_name' => ['nullable', 'string', 'max:50'],
            'min_version_code' => ['nullable', 'integer', 'min:0'],
            'update_message' => ['nullable', 'string'],
            'force_update' => ['sometimes', 'boolean'],
            'icon' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:4096'],
            'cover' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:4096'],
        ]);

        $payload = [
            'name' => trim((string) $data['name']),
            'description' => $data['description'] ?? '',
            'url' => trim((string) $data['url']),
            'major' => strtolower(trim((string) $data['major'])),
            'type' => array_key_exists('type', $data) ? (string) $data['type'] : null,
            'click' => array_key_exists('click', $data) ? (int) $data['click'] : 0,
            'show_on' => array_key_exists('show_on', $data) ? (int) $data['show_on'] : 1,
            'active_course' => array_key_exists('active_course', $data) ? (int) $data['active_course'] : 0,
            'student_learning' => $data['student_learning'] ?? null,
            'package_id' => $data['package_id'] ?? null,
            'platform' => $data['platform'] ?? 'android',
            'latest_version_code' => $data['latest_version_code'] ?? null,
            'latest_version_name' => $data['latest_version_name'] ?? null,
            'min_version_code' => $data['min_version_code'] ?? null,
            'update_message' => $data['update_message'] ?? null,
            'force_update' => array_key_exists('force_update', $data) ? (bool) $data['force_update'] : false,
        ];

        if ($request->hasFile('icon')) {
            $storedPath = Storage::disk('uploads')->putFile('apps/icons', $request->file('icon'));
            $payload['icon'] = $this->toAbsoluteUrl(Storage::disk('uploads')->url($storedPath));
        }
        if ($request->hasFile('cover')) {
            $storedPath = Storage::disk('uploads')->putFile('apps/covers', $request->file('cover'));
            $payload['cover'] = $this->toAbsoluteUrl(Storage::disk('uploads')->url($storedPath));
        }

        App::create($payload);

        return redirect()->back(303);
    }

    public function update(Request $request, App $app)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'url' => ['sometimes', 'required', 'string', 'max:500'],
            'major' => ['sometimes', 'required', 'string', 'max:20'],
            'type' => ['nullable', 'string', 'max:50'],
            'click' => ['sometimes', 'integer', 'min:0'],
            'show_on' => ['sometimes', 'integer', 'min:0'],
            'active_course' => ['sometimes', 'integer', 'min:0'],
            'student_learning' => ['nullable', 'string', 'max:255'],
            'package_id' => ['nullable', 'string', 'max:150'],
            'platform' => ['nullable', 'string', 'in:android,ios'],
            'latest_version_code' => ['nullable', 'integer', 'min:0'],
            'latest_version_name' => ['nullable', 'string', 'max:50'],
            'min_version_code' => ['nullable', 'integer', 'min:0'],
            'update_message' => ['nullable', 'string'],
            'force_update' => ['sometimes', 'boolean'],
            'icon' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:4096'],
            'cover' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:4096'],
            'remove_icon' => ['sometimes', 'boolean'],
            'remove_cover' => ['sometimes', 'boolean'],
        ]);

        $payload = [];
        foreach ([
            'name',
            'description',
            'url',
            'type',
            'click',
            'show_on',
            'active_course',
            'student_learning',
            'package_id',
            'platform',
            'latest_version_code',
            'latest_version_name',
            'min_version_code',
            'update_message',
            'force_update',
        ] as $field) {
            if ($request->has($field)) {
                $payload[$field] = $data[$field];
            }
        }

        if ($request->has('major')) {
            $payload['major'] = strtolower(trim((string) $data['major']));
        }

        if ($request->boolean('remove_icon')) {
            $payload['icon'] = null;
        }
        if ($request->boolean('remove_cover')) {
            $payload['cover'] = null;
        }

        if ($request->hasFile('icon')) {
            $storedPath = Storage::disk('uploads')->putFile('apps/icons', $request->file('icon'));
            $payload['icon'] = $this->toAbsoluteUrl(Storage::disk('uploads')->url($storedPath));
        }
        if ($request->hasFile('cover')) {
            $storedPath = Storage::disk('uploads')->putFile('apps/covers', $request->file('cover'));
            $payload['cover'] = $this->toAbsoluteUrl(Storage::disk('uploads')->url($storedPath));
        }

        if ($payload !== []) {
            $app->update($payload);
        }

        return redirect()->back(303);
    }

    public function destroy(App $app)
    {
        $app->delete();
        return redirect()->back(303);
    }

    private function toAbsoluteUrl(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $value)) {
            return $value;
        }

        $baseUrl = trim((string) config('app.url'));
        if ($baseUrl === '') {
            $baseUrl = trim((string) env('APP_URL'));
        }

        if ($baseUrl === '') {
            return $value;
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($value, '/');
    }
}
