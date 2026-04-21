<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Course;
use App\Models\PackagePlan;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminPackagePlanController extends Controller
{
    use ApiResponse;

    private function loadCoursesById(array $courseIds): array
    {
        $courseIds = array_values(array_unique(array_filter(array_map('intval', $courseIds), fn ($v) => $v > 0)));
        if ($courseIds === []) {
            return [];
        }

        if (!Schema::hasTable('courses') || !Schema::hasColumn('courses', 'course_id')) {
            return [];
        }

        $select = ['course_id'];
        if (Schema::hasColumn('courses', 'title')) $select[] = 'title';
        if (Schema::hasColumn('courses', 'major')) $select[] = 'major';
        if (Schema::hasColumn('courses', 'fee')) $select[] = 'fee';
        if (Schema::hasColumn('courses', 'is_vip')) $select[] = 'is_vip';
        if (Schema::hasColumn('courses', 'active')) $select[] = 'active';
        if (Schema::hasColumn('courses', 'sorting')) $select[] = 'sorting';

        return Course::query()
            ->whereIn('course_id', $courseIds)
            ->get($select)
            ->keyBy('course_id')
            ->map(function ($c) {
                return [
                    'courseId' => (int) ($c->course_id ?? 0),
                    'title' => (string) ($c->title ?? ''),
                    'major' => (string) ($c->major ?? ''),
                    'fee' => $c->fee ?? null,
                    'isVip' => isset($c->is_vip) ? (int) ($c->is_vip ?? 0) : null,
                    'active' => isset($c->active) ? (int) ($c->active ?? 0) : null,
                    'sorting' => isset($c->sorting) ? (int) ($c->sorting ?? 0) : null,
                ];
            })
            ->all();
    }

    private function getPivotColumns(): ?array
    {
        if (!Schema::hasTable('package_plan_courses')) {
            return null;
        }

        $planColumn = Schema::hasColumn('package_plan_courses', 'package_plan_id')
            ? 'package_plan_id'
            : (Schema::hasColumn('package_plan_courses', 'plan_id') ? 'plan_id' : null);
        $courseColumn = Schema::hasColumn('package_plan_courses', 'course_id')
            ? 'course_id'
            : (Schema::hasColumn('package_plan_courses', 'course') ? 'course' : null);

        if (!$planColumn || !$courseColumn) {
            return null;
        }

        return [$planColumn, $courseColumn];
    }

    private function getCourseIdsByPlanId(array $planIds): array
    {
        $result = [];
        foreach ($planIds as $planId) {
            $result[(int) $planId] = [];
        }

        if ($planIds === []) {
            return $result;
        }

        $pivotColumns = $this->getPivotColumns();
        if ($pivotColumns) {
            [$planColumn, $courseColumn] = $pivotColumns;
            $rows = DB::table('package_plan_courses')
                ->whereIn($planColumn, $planIds)
                ->get([$planColumn . ' as plan_id', $courseColumn . ' as course_id']);

            foreach ($rows as $row) {
                $pid = (int) ($row->plan_id ?? 0);
                $cid = (int) ($row->course_id ?? 0);
                if ($pid > 0 && $cid > 0) {
                    $result[$pid][] = $cid;
                }
            }

            foreach ($result as $pid => $ids) {
                $ids = array_values(array_unique(array_map('intval', $ids)));
                sort($ids);
                $result[$pid] = $ids;
            }

            return $result;
        }

        $plans = PackagePlan::query()->whereIn('id', $planIds)->get(['id', 'courses']);
        foreach ($plans as $plan) {
            $ids = collect($plan->courses ?? [])
                ->map(fn ($v) => (int) $v)
                ->filter(fn ($v) => $v > 0)
                ->unique()
                ->values()
                ->all();
            sort($ids);
            $result[(int) $plan->id] = $ids;
        }

        return $result;
    }

    public function index(Request $request)
    {
        $admin = $request->user();
        if (! $admin instanceof Admin) {
            return $this->errorResponse('Forbidden', 403);
        }

        if (!Schema::hasTable('package_plans')) {
            return $this->successResponse([], 200, ['total' => 0]);
        }

        $major = strtolower(trim((string) $request->query('major', '')));
        $active = $request->query('active', null);
        $activeFilter = $active === null ? null : filter_var($active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        $query = PackagePlan::query()->orderBy('sort_order')->orderBy('id');
        if ($major !== '') {
            $query->whereRaw("LOWER(TRIM(COALESCE(major, ''))) = ?", [$major]);
        }
        if ($activeFilter !== null) {
            $query->where('active', $activeFilter ? 1 : 0);
        }

        $plans = $query->get(['id', 'major', 'name', 'description', 'price', 'active', 'sort_order']);
        $planIds = $plans->pluck('id')->map(fn ($v) => (int) $v)->all();
        $courseIdsByPlanId = $this->getCourseIdsByPlanId($planIds);

        $allCourseIds = [];
        foreach ($courseIdsByPlanId as $ids) {
            foreach ($ids as $id) {
                $allCourseIds[] = (int) $id;
            }
        }
        $coursesById = $this->loadCoursesById($allCourseIds);

        $data = $plans->map(function (PackagePlan $plan) use ($courseIdsByPlanId, $coursesById) {
            $planId = (int) $plan->id;
            $courseIds = $courseIdsByPlanId[$planId] ?? [];
            return [
                'id' => $planId,
                'major' => (string) ($plan->major ?? ''),
                'name' => (string) ($plan->name ?? ''),
                'description' => (string) ($plan->description ?? ''),
                'price' => (float) ($plan->price ?? 0),
                'active' => (bool) ($plan->active ?? false),
                'sortOrder' => (int) ($plan->sort_order ?? 0),
                'courseIds' => $courseIds,
                'courses' => collect($courseIds)->map(function ($courseId) use ($coursesById) {
                    $courseId = (int) $courseId;
                    return $coursesById[$courseId] ?? ['courseId' => $courseId];
                })->values()->all(),
            ];
        })->values()->all();

        return $this->successResponse($data, 200, ['total' => count($data)]);
    }

    public function show(Request $request, int $planId)
    {
        $admin = $request->user();
        if (! $admin instanceof Admin) {
            return $this->errorResponse('Forbidden', 403);
        }

        if (!Schema::hasTable('package_plans')) {
            return $this->errorResponse('Package plans table not found.', 422);
        }

        $plan = PackagePlan::query()->find($planId);
        if (!$plan) {
            return $this->errorResponse('Package plan not found.', 404);
        }

        $courseIdsByPlanId = $this->getCourseIdsByPlanId([(int) $plan->id]);
        $courseIds = $courseIdsByPlanId[(int) $plan->id] ?? [];
        $coursesById = $this->loadCoursesById($courseIds);

        $data = [
            'id' => (int) $plan->id,
            'major' => (string) ($plan->major ?? ''),
            'name' => (string) ($plan->name ?? ''),
            'description' => (string) ($plan->description ?? ''),
            'price' => (float) ($plan->price ?? 0),
            'active' => (bool) ($plan->active ?? false),
            'sortOrder' => (int) ($plan->sort_order ?? 0),
            'courseIds' => $courseIds,
            'courses' => collect($courseIds)->map(function ($courseId) use ($coursesById) {
                $courseId = (int) $courseId;
                return $coursesById[$courseId] ?? ['courseId' => $courseId];
            })->values()->all(),
        ];

        return $this->successResponse($data);
    }
}

