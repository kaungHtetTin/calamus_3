<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\MiniProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MiniProgramController extends Controller
{
    public function index()
    {
        $miniPrograms = MiniProgram::query()
            ->orderByDesc('id')
            ->get(['id', 'title', 'link_url', 'image_url', 'major', 'created_at', 'updated_at']);

        $languages = Language::query()
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name', 'display_name', 'image_path', 'primary_color']);

        return Inertia::render('Admin/MiniPrograms', [
            'miniPrograms' => $miniPrograms,
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
            'major' => ['required', 'string', 'max:10'],
            'title' => ['required', 'string', 'max:225'],
            'link_url' => ['required', 'string', 'max:500'],
            'image_url' => ['required_without:image', 'nullable', 'string', 'max:500'],
            'image' => ['required_without:image_url', 'nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:4096'],
        ]);

        $major = strtolower(trim((string) $data['major']));
        $title = trim((string) $data['title']);
        $linkUrl = trim((string) $data['link_url']);

        $imageUrl = array_key_exists('image_url', $data) ? trim((string) $data['image_url']) : '';
        if ($request->hasFile('image')) {
            $storedPath = Storage::disk('uploads')->putFile('mini-programs/images', $request->file('image'));
            $imageUrl = $this->toAbsoluteUrl(Storage::disk('uploads')->url($storedPath));
        }

        MiniProgram::create([
            'major' => $major,
            'title' => $title,
            'link_url' => $linkUrl,
            'image_url' => $imageUrl,
        ]);

        return redirect()->back(303);
    }

    public function update(Request $request, MiniProgram $miniProgram)
    {
        $data = $request->validate([
            'major' => ['sometimes', 'required', 'string', 'max:10'],
            'title' => ['sometimes', 'required', 'string', 'max:225'],
            'link_url' => ['sometimes', 'required', 'string', 'max:500'],
            'image_url' => ['sometimes', 'nullable', 'string', 'max:500'],
            'image' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:4096'],
        ]);

        $payload = [];
        if ($request->has('major')) {
            $payload['major'] = strtolower(trim((string) $data['major']));
        }
        if ($request->has('title')) {
            $payload['title'] = trim((string) $data['title']);
        }
        if ($request->has('link_url')) {
            $payload['link_url'] = trim((string) $data['link_url']);
        }
        if ($request->has('image_url')) {
            $payload['image_url'] = trim((string) ($data['image_url'] ?? ''));
        }
        if ($request->hasFile('image')) {
            $storedPath = Storage::disk('uploads')->putFile('mini-programs/images', $request->file('image'));
            $payload['image_url'] = $this->toAbsoluteUrl(Storage::disk('uploads')->url($storedPath));
        }

        if ($payload !== []) {
            $miniProgram->update($payload);
        }

        return redirect()->back(303);
    }

    public function destroy(MiniProgram $miniProgram)
    {
        $miniProgram->delete();
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
