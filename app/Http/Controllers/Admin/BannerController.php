<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::query()
            ->orderByDesc('id')
            ->get(['id', 'title', 'major', 'image_url', 'link']);

        $languages = Language::query()
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name', 'display_name', 'image_path', 'primary_color']);

        return Inertia::render('Admin/Banners', [
            'banners' => $banners,
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
            'title' => ['required', 'string', 'max:255'],
            'major' => ['required', 'string', 'max:20'],
            'image_url' => ['nullable', 'string', 'max:500', 'required_without:image'],
            'link' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:4096'],
        ]);

        $link = isset($data['link']) ? trim((string) $data['link']) : null;
        if ($link === '') {
            $link = null;
        }

        $imageUrl = trim((string) ($data['image_url'] ?? ''));
        if ($request->hasFile('image')) {
            $storedPath = Storage::disk('uploads')->putFile('banners', $request->file('image'));
            $imageUrl = $this->toAbsoluteUrl($request, Storage::disk('uploads')->url($storedPath));
        } else {
            $imageUrl = $this->toAbsoluteUrl($request, $imageUrl);
        }

        Banner::create([
            'title' => trim((string) $data['title']),
            'major' => strtolower(trim((string) $data['major'])),
            'image_url' => $imageUrl,
            'link' => $link,
        ]);

        return redirect()->back(303);
    }

    public function update(Request $request, Banner $banner)
    {
        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'major' => ['sometimes', 'required', 'string', 'max:20'],
            'image_url' => ['sometimes', 'nullable', 'string', 'max:500', 'required_without:image'],
            'link' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:4096'],
        ]);

        $payload = [];

        if ($request->has('title')) {
            $payload['title'] = trim((string) $data['title']);
        }
        if ($request->has('major')) {
            $payload['major'] = strtolower(trim((string) $data['major']));
        }
        if ($request->has('image_url')) {
            $payload['image_url'] = trim((string) ($data['image_url'] ?? ''));
        }

        if ($request->has('link')) {
            $link = isset($data['link']) ? trim((string) $data['link']) : null;
            if ($link === '') {
                $link = null;
            }
            $payload['link'] = $link;
        }

        if ($request->hasFile('image')) {
            $storedPath = Storage::disk('uploads')->putFile('banners', $request->file('image'));
            $payload['image_url'] = $this->toAbsoluteUrl($request, Storage::disk('uploads')->url($storedPath));
        } elseif ($request->has('image_url')) {
            $payload['image_url'] = $this->toAbsoluteUrl($request, (string) ($payload['image_url'] ?? ''));
        }

        if ($payload !== []) {
            $banner->update($payload);
        }

        return redirect()->back(303);
    }

    public function destroy(Banner $banner)
    {
        $banner->delete();
        return redirect()->back(303);
    }

    private function toAbsoluteUrl(Request $request, string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $value)) {
            return $value;
        }

        $host = trim((string) $request->getSchemeAndHttpHost());
        if ($host === '') {
            return $value;
        }

        return rtrim($host, '/') . '/' . ltrim($value, '/');
    }
}
