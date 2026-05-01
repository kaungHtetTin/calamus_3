<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    use ApiResponse;

    public function first(Request $request)
    {
        $data = $request->validate([
            'major' => ['nullable', 'string', 'max:20'],
            'major_scope' => ['nullable', 'string', 'max:500'],
            'majorScope' => ['nullable', 'string', 'max:500'],
        ]);

        $majors = [];

        if (!empty($data['major'] ?? null)) {
            $majors[] = strtolower(trim((string) $data['major']));
        }

        $scopeRaw = $data['major_scope'] ?? ($data['majorScope'] ?? null);
        if (!empty($scopeRaw)) {
            $parts = preg_split('/[,\s]+/', (string) $scopeRaw);
            foreach ($parts as $p) {
                $v = strtolower(trim((string) $p));
                if ($v !== '') {
                    $majors[] = $v;
                }
            }
        }

        $majors = array_values(array_unique(array_filter($majors)));
        if ($majors === []) {
            return $this->errorResponse('major is required', 422);
        }

        $banner = Banner::query()
            ->whereIn('major', $majors)
            ->orderByDesc('id')
            ->first(['id', 'title', 'major', 'image_url', 'link']);

        $payload = $banner
            ? [
                'id' => (int) $banner->id,
                'title' => (string) $banner->title,
                'major' => (string) $banner->major,
                'image_url' => (string) $banner->image_url,
                'link' => $banner->link !== null ? (string) $banner->link : null,
            ]
            : null;

        return $this->successResponse([
            'banner' => $payload,
        ]);
    }
}

