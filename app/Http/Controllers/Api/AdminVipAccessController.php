<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Course;
use App\Models\PackagePlan;
use App\Models\Payment;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminVipAccessController extends Controller
{
    use ApiResponse;

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

    private function normalizeUrl(?string $value): string
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return '';
        }
        if (preg_match('/^https?:\\/\\//i', $raw)) {
            return $raw;
        }
        $baseUrl = (string) (config('app.url') ?: env('APP_URL') ?: '');
        $baseUrl = rtrim($baseUrl, '/');
        if ($baseUrl === '') {
            return $raw;
        }
        if (str_starts_with($raw, '/')) {
            return $baseUrl . $raw;
        }
        return $baseUrl . '/' . $raw;
    }

    private function storePaymentScreenshot(UploadedFile $file): array
    {
        $filename = Str::random(18) . '.' . $file->getClientOriginalExtension();
        $path = 'payments/screenshots';
        $storedPath = Storage::disk('uploads')->putFileAs($path, $file, $filename);
        $urlPath = Storage::disk('uploads')->url($storedPath);
        $fileUrl = $this->normalizeUrl($urlPath);
        $fileSize = (int) ($file->getSize() ?? 0);

        return [$fileUrl, $fileSize];
    }

    private function resolvePaymentApproveColumn(): ?string
    {
        if (!Schema::hasTable('payments')) {
            return null;
        }

        if (Schema::hasColumn('payments', 'approved')) {
            return 'approved';
        }

        if (Schema::hasColumn('payments', 'approve')) {
            return 'approve';
        }

        return null;
    }

    public function status(Request $request, int $userId)
    {
        $admin = $request->user();
        if (! $admin instanceof Admin) {
            return $this->errorResponse('Forbidden', 403);
        }

        if (! $admin->hasPermission('user')) {
            return $this->errorResponse('Forbidden', 403);
        }

        $major = strtolower(trim((string) $request->query('major', '')));
        if ($major === '') {
            return $this->errorResponse('major is required.', 422);
        }

        if (!Schema::hasTable('courses') || !Schema::hasColumn('courses', 'course_id')) {
            return $this->errorResponse('Courses table not found.', 422);
        }

        if (!Schema::hasTable('vipusers') || !Schema::hasColumn('vipusers', 'user_id') || !Schema::hasColumn('vipusers', 'course_id')) {
            return $this->errorResponse('Vipusers table not found.', 422);
        }

        $courses = Course::query()
            ->whereRaw("LOWER(TRIM(COALESCE(major, ''))) = ?", [$major])
            ->orderBy('sorting')
            ->orderBy('course_id')
            ->get(['course_id', 'title', 'major', 'is_vip', 'active', 'fee']);

        $courseIds = $courses->pluck('course_id')->map(fn ($v) => (int) $v)->all();

        $plans = [];
        $courseIdsByPlanId = [];
        if (Schema::hasTable('package_plans')) {
            $plans = PackagePlan::query()
                ->whereRaw("LOWER(TRIM(COALESCE(major, ''))) = ?", [$major])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'major', 'name', 'description', 'price', 'active', 'sort_order']);

            $planIds = $plans->pluck('id')->map(fn ($v) => (int) $v)->all();
            $courseIdsByPlanId = $this->getCourseIdsByPlanId($planIds);
        }

        $vipQuery = DB::table('vipusers')
            ->where('user_id', $userId)
            ->whereIn('course_id', $courseIds);

        if (Schema::hasColumn('vipusers', 'major')) {
            $vipQuery->whereRaw('LOWER(TRIM(COALESCE(major, \'\'))) = ?', [$major]);
        }

        $vipCourseIds = $vipQuery
            ->pluck('course_id')
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        $vipSet = array_flip($vipCourseIds);
        $userData = null;
        if (Schema::hasTable('user_data') && Schema::hasColumn('user_data', 'user_id') && Schema::hasColumn('user_data', 'major')) {
            $userData = DB::table('user_data')
                ->where('user_id', $userId)
                ->whereRaw('LOWER(TRIM(major)) = ?', [$major])
                ->first();
        }
        $isVip = $userData && isset($userData->is_vip) ? (int) $userData->is_vip : 0;
        $isDiamond = $userData && isset($userData->diamond_plan) ? (int) $userData->diamond_plan : 0;
        $packagePlan = $isDiamond === 1 ? 'diamond' : ($isVip === 1 ? 'vip' : '');

        $data = [
            'userId' => (int) $userId,
            'major' => $major,
            'isVip' => $isVip,
            'isDiamond' => $isDiamond,
            'selectedCourseIds' => $vipCourseIds,
            'packagePlans' => collect($plans)->map(function (PackagePlan $plan) use ($courseIdsByPlanId) {
                $planId = (int) $plan->id;
                return [
                    'id' => $planId,
                    'major' => (string) ($plan->major ?? ''),
                    'name' => (string) ($plan->name ?? ''),
                    'description' => (string) ($plan->description ?? ''),
                    'price' => (float) ($plan->price ?? 0),
                    'active' => (bool) ($plan->active ?? false),
                    'sortOrder' => (int) ($plan->sort_order ?? 0),
                    'courseIds' => $courseIdsByPlanId[$planId] ?? [],
                ];
            })->values()->all(),
            'courses' => $courses->map(function (Course $c) use ($vipSet, $packagePlan) {
                $courseId = (int) $c->course_id;
                $isGrantedVip = array_key_exists($courseId, $vipSet);
                return [
                    'courseId' => $courseId,
                    'title' => (string) ($c->title ?? ''),
                    'major' => (string) ($c->major ?? ''),
                    'isVipCourse' => (int) ($c->is_vip ?? 0),
                    'active' => (int) ($c->active ?? 0),
                    'fee' => $c->fee ?? null,
                    'isGrantedVip' => $isGrantedVip,
                    'packagePlan' => $isGrantedVip ? $packagePlan : null,
                ];
            })->values()->all(),
        ];

        return $this->successResponse($data);
    }

    public function update(Request $request, int $userId)
    {
        $admin = $request->user();
        if (! $admin instanceof Admin) {
            return $this->errorResponse('Forbidden', 403);
        }

        if (! $admin->hasPermission('user')) {
            return $this->errorResponse('Forbidden', 403);
        }

        $major = strtolower(trim((string) $request->input('major', '')));
        if ($major === '') {
            return $this->errorResponse('major is required.', 422);
        }

        $isVipRaw = $request->has('isVip') ? $request->input('isVip') : $request->input('is_vip', null);
        $isDiamondRaw = $request->has('isDiamond') ? $request->input('isDiamond') : $request->input('is_diamond', null);

        if ($isVipRaw === null) {
            return $this->errorResponse('isVip is required.', 422);
        }
        if ($isDiamondRaw === null) {
            return $this->errorResponse('isDiamond is required.', 422);
        }

        $isVip = filter_var($isVipRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $isDiamond = filter_var($isDiamondRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($isVip === null) {
            return $this->errorResponse('isVip must be boolean.', 422);
        }
        if ($isDiamond === null) {
            return $this->errorResponse('isDiamond must be boolean.', 422);
        }

        $isVipInt = $isVip ? 1 : 0;
        $isDiamondInt = ($isVipInt === 1 && $isDiamond) ? 1 : 0;

        $selectedCourseIds = collect($request->input('selectedCourseIds', []))
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => $v > 0)
            ->unique()
            ->values();

        if ($isVipInt === 1 && $selectedCourseIds->isEmpty()) {
            return $this->errorResponse('selectedCourseIds is required when isVip is true.', 422);
        }

        if ($isVipInt === 0) {
            $selectedCourseIds = collect();
        }

        $amount = $request->input('amount');
        if ($amount === null || !is_numeric($amount)) {
            return $this->errorResponse('amount is required.', 422);
        }
        $amountValue = (float) $amount;
        if ($amountValue < 0) {
            return $this->errorResponse('amount must be >= 0.', 422);
        }

        $transactionId = trim((string) $request->input('transactionId', ''));
        if ($transactionId === '') {
            return $this->errorResponse('transactionId is required.', 422);
        }

        $file = $request->file('screenshot');
        if (!$file instanceof UploadedFile) {
            return $this->errorResponse('screenshot is required.', 422);
        }

        if (!Schema::hasTable('courses') || !Schema::hasColumn('courses', 'course_id')) {
            return $this->errorResponse('Courses table not found.', 422);
        }

        if (!Schema::hasTable('vipusers') || !Schema::hasColumn('vipusers', 'user_id') || !Schema::hasColumn('vipusers', 'course_id')) {
            return $this->errorResponse('Vipusers table not found.', 422);
        }

        if (!Schema::hasTable('payments')) {
            return $this->errorResponse('Payments table not found.', 422);
        }

        $approveColumn = $this->resolvePaymentApproveColumn();
        if ($approveColumn === null) {
            return $this->errorResponse('Payments approve column not found.', 422);
        }

        if ($selectedCourseIds->isNotEmpty()) {
            $validCourseIds = DB::table('courses')
                ->whereIn('course_id', $selectedCourseIds->all())
                ->whereRaw("LOWER(TRIM(COALESCE(major, ''))) = ?", [$major])
                ->pluck('course_id')
                ->map(fn ($v) => (int) $v)
                ->all();

            $sortedSelected = $selectedCourseIds->all();
            sort($validCourseIds);
            sort($sortedSelected);
            if ($validCourseIds !== $sortedSelected) {
                return $this->errorResponse('Selected courses do not belong to the given language.', 422);
            }
        }

        [$screenshotUrl] = $this->storePaymentScreenshot($file);

        $partnerCode = trim((string) $request->input('partnerCode', ''));
        $now = now();

        $changes = [
            'insertedCourseIds' => [],
            'removedCourseIds' => [],
        ];

        try {
            DB::transaction(function () use (
                $userId,
                $major,
                $selectedCourseIds,
                $isVipInt,
                $isDiamondInt,
                $amountValue,
                $transactionId,
                $screenshotUrl,
                $partnerCode,
                $approveColumn,
                $now,
                &$changes
            ) {
                $courseIdsForMajor = DB::table('courses')
                    ->whereRaw("LOWER(TRIM(COALESCE(major, ''))) = ?", [$major])
                    ->pluck('course_id')
                    ->map(fn ($v) => (int) $v)
                    ->all();

                $existingVipCourseIds = DB::table('vipusers')
                    ->where('user_id', $userId)
                    ->whereIn('course_id', $courseIdsForMajor)
                    ->when(Schema::hasColumn('vipusers', 'major'), function ($q) use ($major) {
                        $q->whereRaw("LOWER(TRIM(COALESCE(major, ''))) = ?", [$major]);
                    })
                    ->pluck('course_id')
                    ->map(fn ($v) => (int) $v)
                    ->unique()
                    ->values()
                    ->all();

                $existingSet = collect($existingVipCourseIds);
                $toDelete = $existingSet->diff($selectedCourseIds)->values();
                $toInsert = $selectedCourseIds->diff($existingSet)->values();

                if ($toDelete->isNotEmpty()) {
                    DB::table('vipusers')
                        ->where('user_id', $userId)
                        ->whereIn('course_id', $toDelete->all())
                        ->when(Schema::hasColumn('vipusers', 'major'), function ($q) use ($major) {
                            $q->whereRaw("LOWER(TRIM(COALESCE(major, ''))) = ?", [$major]);
                        })
                        ->delete();
                }

                if ($toInsert->isNotEmpty()) {
                    $insertRows = $toInsert->map(function ($courseId) use ($userId, $major, $now) {
                        $row = [
                            'user_id' => (int) $userId,
                            'course_id' => (int) $courseId,
                        ];

                        if (Schema::hasColumn('vipusers', 'major')) {
                            $row['major'] = $major;
                        }
                        if (Schema::hasColumn('vipusers', 'date')) {
                            $row['date'] = $now;
                        }

                        return $row;
                    })->values()->all();

                    DB::table('vipusers')->insert($insertRows);
                }

                $changes['insertedCourseIds'] = $toInsert->values()->all();
                $changes['removedCourseIds'] = $toDelete->values()->all();

                if (Schema::hasTable('user_data')) {
                    $userDataUpdate = [];
                    if (Schema::hasColumn('user_data', 'is_vip')) {
                        $userDataUpdate['is_vip'] = $isVipInt;
                    }
                    if (Schema::hasColumn('user_data', 'diamond_plan')) {
                        $userDataUpdate['diamond_plan'] = $isDiamondInt;
                    }
                    if (Schema::hasColumn('user_data', 'updated_at')) {
                        $userDataUpdate['updated_at'] = $now;
                    }

                    if (!empty($userDataUpdate)) {
                        $existingUserData = DB::table('user_data')
                            ->where('user_id', $userId)
                            ->whereRaw('LOWER(TRIM(major)) = ?', [$major])
                            ->first();

                        if ($existingUserData) {
                            DB::table('user_data')
                                ->where('id', $existingUserData->id)
                                ->update($userDataUpdate);
                        } else {
                            $insertData = array_merge($userDataUpdate, [
                                'user_id' => $userId,
                                'major' => $major,
                            ]);
                            if (Schema::hasColumn('user_data', 'created_at')) {
                                $insertData['created_at'] = $now;
                            }
                            DB::table('user_data')->insert($insertData);
                        }
                    }
                }

                $payment = new Payment();
                $payment->user_id = $userId;
                $payment->major = $major;
                $payment->amount = $amountValue;
                $payment->courses = $selectedCourseIds->values()->all();
                $payment->screenshot = $screenshotUrl;
                $payment->activated = 1;
                $payment->transaction_id = $transactionId;
                if (Schema::hasColumn('payments', 'date')) {
                    $payment->date = $now;
                }

                if (Schema::hasColumn('payments', $approveColumn)) {
                    $payment->{$approveColumn} = 0;
                }

                if (Schema::hasColumn('payments', 'meta')) {
                    $meta = is_array($payment->meta) ? $payment->meta : [];
                    if ($partnerCode !== '') {
                        $meta['partnerCode'] = $partnerCode;
                    }
                    $payment->meta = $meta;
                }

                $payment->save();

                if ($partnerCode !== '') {
                    if (!Schema::hasTable('partners')) {
                        throw new \RuntimeException('Partner system not configured.');
                    }

                    $partner = DB::table('partners')->where('private_code', $partnerCode)->first();
                    if (!$partner) {
                        throw new \RuntimeException('Wrong promotion Code! Activation fail');
                    }

                    if (Schema::hasTable('partner_earnings')) {
                        $commissionRate = (float) ($partner->commission_rate ?? 0);
                        $originalPrice = $amountValue / 0.9;
                        $amountReceived = ($originalPrice * $commissionRate) / 100;

                        $insert = [];
                        if (Schema::hasColumn('partner_earnings', 'partner_id')) $insert['partner_id'] = $partner->id ?? null;
                        if (Schema::hasColumn('partner_earnings', 'target_course_id')) $insert['target_course_id'] = null;
                        if (Schema::hasColumn('partner_earnings', 'target_package_id')) $insert['target_package_id'] = null;
                        if (Schema::hasColumn('partner_earnings', 'user_id')) $insert['user_id'] = $userId;
                        if (Schema::hasColumn('partner_earnings', 'price')) $insert['price'] = $amountValue;
                        if (Schema::hasColumn('partner_earnings', 'commission_rate')) $insert['commission_rate'] = $commissionRate;
                        if (Schema::hasColumn('partner_earnings', 'amount_received')) $insert['amount_received'] = $amountReceived;
                        if (Schema::hasColumn('partner_earnings', 'status')) $insert['status'] = 'pending';
                        if (Schema::hasColumn('partner_earnings', 'created_at')) $insert['created_at'] = $now;
                        if (Schema::hasColumn('partner_earnings', 'updated_at')) $insert['updated_at'] = $now;

                        DB::table('partner_earnings')->insert($insert);
                    }
                }
            });
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->errorResponse('VIP update failed.', 500);
        }

        return $this->successResponse([
            'userId' => (int) $userId,
            'major' => $major,
            'isVip' => $isVipInt,
            'isDiamond' => $isDiamondInt,
            'selectedCourseIds' => $selectedCourseIds->values()->all(),
            'insertedCourseIds' => array_values($changes['insertedCourseIds']),
            'removedCourseIds' => array_values($changes['removedCourseIds']),
            'payment' => [
                'activated' => 1,
                'approved' => 0,
                'amount' => $amountValue,
                'transactionId' => $transactionId,
                'screenshotUrl' => $screenshotUrl,
                'partnerCode' => $partnerCode !== '' ? $partnerCode : null,
            ],
        ]);
    }
}
