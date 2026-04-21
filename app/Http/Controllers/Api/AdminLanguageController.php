<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Language;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class AdminLanguageController extends Controller
{
    use ApiResponse;

    private function resolveAdminMajorScope(?Admin $admin)
    {
        $raw = collect((array) ($admin?->major_scope ?? []))
            ->map(function ($item) {
                return strtolower(trim((string) $item));
            })
            ->filter()
            ->unique()
            ->values();

        if ($raw->contains('*')) {
            return collect(['*']);
        }

        $languageCodes = Language::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['code'])
            ->map(function ($language) {
                return strtolower(trim((string) ($language->code ?: '')));
            })
            ->filter()
            ->unique()
            ->values();

        return $raw
            ->filter(function ($value) use ($languageCodes) {
                return $languageCodes->contains($value);
            })
            ->values();
    }

    public function index(Request $request)
    {
        $admin = $request->user();

        
        if (! $admin instanceof Admin) {
            return $this->errorResponse('Forbidden', 403);
        }
        // if (! $admin->hasPermission('administration') && ! $admin->hasPermission('course')) {
        //     return $this->errorResponse('Forbidden', 403);
        // }

        $scope = $this->resolveAdminMajorScope($admin);

        $languagesQuery = Language::query()
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->orderBy('id');

        if (! $scope->contains('*')) {
            if ($scope->isEmpty()) {
                return $this->successResponse([], 200, ['total' => 0, 'scope' => []]);
            }

            $languagesQuery->whereIn('code', $scope->all());
        }

        $languages = $languagesQuery->get();

        $data = $languages->map(function (Language $lang) {
            return [
                'id' => (int) $lang->id,
                'name' => $lang->name,
                'display_name' => $lang->display_name ?: $lang->name,
                'code' => $lang->code ?: '',
                'module_code' => $lang->module_code ?: '',
                'image_path' => $lang->image_path ?: null,
                'primary_color' => $lang->primary_color ?: null,
                'secondary_color' => $lang->secondary_color ?: null,
            ];
        });

        return $this->successResponse($data, 200, [
            'total' => $data->count(),
            'scope' => $scope->contains('*') ? ['*'] : $scope->values()->all(),
        ]);
    }
}
