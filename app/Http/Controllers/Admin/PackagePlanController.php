<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Language;
use App\Models\PackagePlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PackagePlanController extends Controller
{
    public function index()
    {
        $plans = PackagePlan::orderBy('sort_order')->orderBy('id')->get();
        $languages = Language::query()
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name', 'display_name', 'image_path', 'primary_color']);

        $courses = Course::query()
            ->where('active', 1)
            ->orderBy('sorting')
            ->orderBy('course_id')
            ->get(['course_id', 'title', 'major', 'fee', 'is_vip']);

        $courseIdsByPlanId = $this->getCourseIdsByPlanId($plans->pluck('id')->all());

        $plansForUi = $plans->map(function (PackagePlan $plan) use ($courseIdsByPlanId) {
            $courseIds = $courseIdsByPlanId[$plan->id] ?? [];
            return [
                'id' => (int) $plan->id,
                'major' => (string) ($plan->major ?? ''),
                'name' => (string) ($plan->name ?? ''),
                'description' => (string) ($plan->description ?? ''),
                'price' => (float) ($plan->price ?? 0),
                'active' => (bool) ($plan->active ?? false),
                'sort_order' => (int) ($plan->sort_order ?? 0),
                'course_ids' => $courseIds,
            ];
        });

        return Inertia::render('Admin/PackagePlans', [
            'packagePlans' => $plansForUi->values(),
            'languageOptions' => $languages->map(function ($l) {
                return [
                    'id' => (int) $l->id,
                    'code' => (string) ($l->code ?: $l->name),
                    'name' => (string) ($l->display_name ?: $l->name),
                    'image_path' => (string) ($l->image_path ?? ''),
                    'primary_color' => (string) ($l->primary_color ?? ''),
                ];
            })->values(),
            'courseOptions' => $courses->map(function ($c) {
                return [
                    'id' => (int) $c->course_id,
                    'title' => (string) $c->title,
                    'major' => (string) $c->major,
                    'fee' => (float) ($c->fee ?? 0),
                    'is_vip' => (int) ($c->is_vip ?? 0),
                ];
            })->values(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'major' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
            'course_ids' => ['nullable', 'array'],
            'course_ids.*' => ['integer', 'min:1'],
        ]);

        $major = strtolower(trim((string) $data['major']));
        $courseIds = collect($data['course_ids'] ?? [])
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => $v > 0)
            ->unique()
            ->values()
            ->all();

        $this->ensureCoursesBelongToMajor($major, $courseIds);

        $plan = new PackagePlan();
        $plan->major = $major;
        $plan->name = $data['name'];
        $plan->description = $data['description'] ?? '';
        $plan->price = (float) $data['price'];
        $plan->active = array_key_exists('active', $data) ? (bool) $data['active'] : true;
        $plan->sort_order = array_key_exists('sort_order', $data) ? (int) $data['sort_order'] : 0;
        $plan->save();

        $this->syncPlanCourses($plan->id, $courseIds);

        return redirect()->back(303);
    }

    public function update(Request $request, PackagePlan $packagePlan)
    {
        $data = $request->validate([
            'major' => ['sometimes', 'required', 'string', 'max:50'],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
            'course_ids' => ['nullable', 'array'],
            'course_ids.*' => ['integer', 'min:1'],
        ]);

        $nextMajor = array_key_exists('major', $data)
            ? strtolower(trim((string) $data['major']))
            : strtolower(trim((string) ($packagePlan->major ?? '')));

        $courseIds = collect($data['course_ids'] ?? [])
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => $v > 0)
            ->unique()
            ->values()
            ->all();

        $this->ensureCoursesBelongToMajor($nextMajor, $courseIds);

        $payload = [];
        foreach (['name', 'description', 'price', 'active', 'sort_order'] as $field) {
            if ($request->has($field)) {
                $payload[$field] = $data[$field];
            }
        }
        if ($request->has('major')) {
            $payload['major'] = $nextMajor;
        }

        if ($payload !== []) {
            $packagePlan->update($payload);
        }

        if ($request->has('course_ids')) {
            $this->syncPlanCourses($packagePlan->id, $courseIds);
        }

        return redirect()->back(303);
    }

    public function destroy(PackagePlan $packagePlan)
    {
        $planId = (int) $packagePlan->id;
        DB::transaction(function () use ($packagePlan, $planId) {
            $this->deletePlanCourses($planId);
            $packagePlan->delete();
        });

        return redirect()->back(303);
    }

    private function ensureCoursesBelongToMajor(string $major, array $courseIds): void
    {
        if ($major === '' || $courseIds === []) {
            return;
        }

        if (!Schema::hasTable('courses')) {
            throw ValidationException::withMessages([
                'course_ids' => 'Courses table not found.',
            ]);
        }

        $validCourseIds = DB::table('courses')
            ->whereIn('course_id', $courseIds)
            ->whereRaw("LOWER(TRIM(COALESCE(major, ''))) = ?", [$major])
            ->pluck('course_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        $sortedSelected = $courseIds;
        sort($validCourseIds);
        sort($sortedSelected);

        if ($validCourseIds !== $sortedSelected) {
            throw ValidationException::withMessages([
                'course_ids' => 'Selected courses do not belong to the selected language.',
            ]);
        }
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

    private function deletePlanCourses(int $planId): void
    {
        $pivotColumns = $this->getPivotColumns();
        if ($pivotColumns) {
            [$planColumn] = $pivotColumns;
            DB::table('package_plan_courses')->where($planColumn, $planId)->delete();
            return;
        }

        if (Schema::hasTable('package_plans') && Schema::hasColumn('package_plans', 'courses')) {
            DB::table('package_plans')->where('id', $planId)->update(['courses' => null]);
        }
    }

    private function syncPlanCourses(int $planId, array $courseIds): void
    {
        $pivotColumns = $this->getPivotColumns();
        if ($pivotColumns) {
            [$planColumn, $courseColumn] = $pivotColumns;
            DB::transaction(function () use ($planId, $courseIds, $planColumn, $courseColumn) {
                DB::table('package_plan_courses')->where($planColumn, $planId)->delete();
                if ($courseIds === []) {
                    return;
                }
                $batch = [];
                foreach ($courseIds as $courseId) {
                    $batch[] = [
                        $planColumn => $planId,
                        $courseColumn => $courseId,
                    ];
                }
                DB::table('package_plan_courses')->insert($batch);
            });
            return;
        }

        if (Schema::hasTable('package_plans') && Schema::hasColumn('package_plans', 'courses')) {
            DB::table('package_plans')->where('id', $planId)->update(['courses' => $courseIds]);
        }
    }
}
