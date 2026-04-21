<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Payment;
use App\Traits\ApiResponse;
use App\Services\NotificationDispatchService;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AdminPaymentController extends Controller
{
    use ApiResponse;

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

    public function unactivated(Request $request)
    {
        $admin = $request->user();
        if (! $admin instanceof Admin) {
            return $this->errorResponse('Forbidden', 403);
        }

        if (! $admin->hasPermission('user')) {
            return $this->errorResponse('Forbidden', 403);
        }

        if (! Schema::hasTable('payments')) {
            return $this->successResponse([], 200, $this->paginate(0, 1, 25));
        }

        $page = max(1, (int) $request->query('page', 1));
        $limit = (int) $request->query('limit', 25);
        $limit = min(100, max(10, $limit));

        $payments = Payment::query()
            ->where('activated', 0)
            ->orderByDesc('id')
            ->paginate($limit, ['id', 'user_id', 'major', 'amount', 'meta', 'screenshot', 'activated'], 'page', $page);

        $paymentRows = collect($payments->items());
        $userIds = $paymentRows
            ->pluck('user_id')
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '' && $v !== '0' && ctype_digit($v))
            ->unique()
            ->values()
            ->all();

        $learnersByUserId = [];
        if (!empty($userIds) && Schema::hasTable('learners') && Schema::hasColumn('learners', 'user_id')) {
            $nameColumn = Schema::hasColumn('learners', 'learner_name')
                ? 'learner_name'
                : (Schema::hasColumn('learners', 'name') ? 'name' : null);
            $emailColumn = Schema::hasColumn('learners', 'learner_email')
                ? 'learner_email'
                : (Schema::hasColumn('learners', 'email') ? 'email' : null);
            $phoneColumn = Schema::hasColumn('learners', 'learner_phone')
                ? 'learner_phone'
                : (Schema::hasColumn('learners', 'phone') ? 'phone' : null);

            $select = ['user_id'];
            if ($nameColumn) $select[] = $nameColumn;
            if ($emailColumn) $select[] = $emailColumn;
            if ($phoneColumn) $select[] = $phoneColumn;

            $learnersByUserId = DB::table('learners')
                ->whereIn('user_id', $userIds)
                ->get($select)
                ->keyBy('user_id')
                ->all();
        }

        $data = $paymentRows->map(function (Payment $payment) use ($learnersByUserId) {
            $userId = trim((string) ($payment->user_id ?? ''));
            $learner = ($userId !== '' && $userId !== '0') ? ($learnersByUserId[$userId] ?? null) : null;

            $username = '';
            $email = '';
            $phone = '';
            if ($learner) {
                $username = (string) ($learner->learner_name ?? $learner->name ?? '');
                $email = (string) ($learner->learner_email ?? $learner->email ?? '');
                $phone = (string) ($learner->learner_phone ?? $learner->phone ?? '');
            }

            $meta = is_array($payment->meta) ? $payment->meta : [];
            $packagePlan = $meta['packagePlan'] ?? null;
            $packagePlan = is_string($packagePlan) ? $packagePlan : null;

            return [
                'userId' => $userId,
                'username' => trim($username),
                'email' => trim($email),
                'phone' => trim($phone),
                'paymentId' => (int) ($payment->id ?? 0),
                'major' => (string) ($payment->major ?? ''),
                'paymentAmount' => $payment->amount,
                'packagePlan' => $packagePlan,
                'screenshotUrl' => $this->normalizeUrl((string) ($payment->screenshot ?? '')),
            ];
        })->values()->all();

        return $this->successResponse($data, 200, $this->paginate(
            (int) $payments->total(),
            (int) $payments->currentPage(),
            (int) $payments->perPage()
        ));
    }

    public function pendingApproval(Request $request)
    {
        $admin = $request->user();
        if (! $admin instanceof Admin) {
            return $this->errorResponse('Forbidden', 403);
        }

        if (! $admin->hasPermission('user')) {
            return $this->errorResponse('Forbidden', 403);
        }

        $approveColumn = $this->resolvePaymentApproveColumn();
        if ($approveColumn === null) {
            return $this->successResponse([], 200, $this->paginate(0, 1, 25));
        }

        $page = max(1, (int) $request->query('page', 1));
        $limit = (int) $request->query('limit', 25);
        $limit = min(100, max(10, $limit));

        $payments = Payment::query()
            ->where($approveColumn, 0)
            ->orderByDesc('id')
            ->paginate($limit, ['id', 'user_id', 'major', 'amount', 'meta', 'screenshot'], 'page', $page);

        $paymentRows = collect($payments->items());
        $userIds = $paymentRows
            ->pluck('user_id')
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '' && $v !== '0' && ctype_digit($v))
            ->unique()
            ->values()
            ->all();

        $learnersByUserId = [];
        if (!empty($userIds) && Schema::hasTable('learners') && Schema::hasColumn('learners', 'user_id')) {
            $nameColumn = Schema::hasColumn('learners', 'learner_name')
                ? 'learner_name'
                : (Schema::hasColumn('learners', 'name') ? 'name' : null);
            $emailColumn = Schema::hasColumn('learners', 'learner_email')
                ? 'learner_email'
                : (Schema::hasColumn('learners', 'email') ? 'email' : null);
            $phoneColumn = Schema::hasColumn('learners', 'learner_phone')
                ? 'learner_phone'
                : (Schema::hasColumn('learners', 'phone') ? 'phone' : null);

            $select = ['user_id'];
            if ($nameColumn) $select[] = $nameColumn;
            if ($emailColumn) $select[] = $emailColumn;
            if ($phoneColumn) $select[] = $phoneColumn;

            $learnersByUserId = DB::table('learners')
                ->whereIn('user_id', $userIds)
                ->get($select)
                ->keyBy('user_id')
                ->all();
        }

        $data = $paymentRows->map(function (Payment $payment) use ($learnersByUserId) {
            $userId = trim((string) ($payment->user_id ?? ''));
            $learner = ($userId !== '' && $userId !== '0') ? ($learnersByUserId[$userId] ?? null) : null;

            $username = '';
            $email = '';
            $phone = '';
            if ($learner) {
                $username = (string) ($learner->learner_name ?? $learner->name ?? '');
                $email = (string) ($learner->learner_email ?? $learner->email ?? '');
                $phone = (string) ($learner->learner_phone ?? $learner->phone ?? '');
            }

            $meta = is_array($payment->meta) ? $payment->meta : [];
            $packagePlan = $meta['packagePlan'] ?? null;
            $packagePlan = is_string($packagePlan) ? $packagePlan : null;

            return [
                'paymentId' => (int) ($payment->id ?? 0),
                'userId' => $userId,
                'username' => trim($username),
                'email' => trim($email),
                'phone' => trim($phone),
                'major' => (string) ($payment->major ?? ''),
                'paymentAmount' => $payment->amount,
                'packagePlan' => $packagePlan,
                'screenshotUrl' => $this->normalizeUrl((string) ($payment->screenshot ?? '')),
            ];
        })->values()->all();

        return $this->successResponse($data, 200, $this->paginate(
            (int) $payments->total(),
            (int) $payments->currentPage(),
            (int) $payments->perPage()
        ));
    }

    public function unactivatedDetail(Request $request, int $paymentId)
    {
        $admin = $request->user();
        if (! $admin instanceof Admin) {
            return $this->errorResponse('Forbidden', 403);
        }

        if (! $admin->hasPermission('user')) {
            return $this->errorResponse('Forbidden', 403);
        }

        if (! Schema::hasTable('payments')) {
            return $this->errorResponse('Payments table not found.', 422);
        }

        $payment = Payment::query()->find($paymentId);
        if (! $payment) {
            return $this->errorResponse('Payment not found.', 404);
        }

        if ((int) ($payment->activated ?? 0) !== 0) {
            return $this->errorResponse('Payment is already activated.', 422);
        }

        $userId = trim((string) ($payment->user_id ?? ''));

        $learner = null;
        if ($userId !== '' && $userId !== '0' && Schema::hasTable('learners') && Schema::hasColumn('learners', 'user_id')) {
            $nameColumn = Schema::hasColumn('learners', 'learner_name')
                ? 'learner_name'
                : (Schema::hasColumn('learners', 'name') ? 'name' : null);
            $emailColumn = Schema::hasColumn('learners', 'learner_email')
                ? 'learner_email'
                : (Schema::hasColumn('learners', 'email') ? 'email' : null);
            $phoneColumn = Schema::hasColumn('learners', 'learner_phone')
                ? 'learner_phone'
                : (Schema::hasColumn('learners', 'phone') ? 'phone' : null);
            $imageColumn = Schema::hasColumn('learners', 'learner_image')
                ? 'learner_image'
                : (Schema::hasColumn('learners', 'image') ? 'image' : null);

            $select = ['user_id'];
            if ($nameColumn) $select[] = $nameColumn;
            if ($emailColumn) $select[] = $emailColumn;
            if ($phoneColumn) $select[] = $phoneColumn;
            if ($imageColumn) $select[] = $imageColumn;

            $learner = DB::table('learners')->where('user_id', $userId)->first($select);
        }

        $username = '';
        $email = '';
        $phone = '';
        $userImage = '';
        if ($learner) {
            $username = (string) ($learner->learner_name ?? $learner->name ?? '');
            $email = (string) ($learner->learner_email ?? $learner->email ?? '');
            $phone = (string) ($learner->learner_phone ?? $learner->phone ?? '');
            $userImage = (string) ($learner->learner_image ?? $learner->image ?? '');
        }

        $courseIds = collect($payment->courses ?? [])
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value > 0)
            ->unique()
            ->values()
            ->all();

        $courses = [];
        if (!empty($courseIds) && Schema::hasTable('courses') && Schema::hasColumn('courses', 'course_id')) {
            $titleColumn = Schema::hasColumn('courses', 'title')
                ? 'title'
                : (Schema::hasColumn('courses', 'name') ? 'name' : null);
            $majorColumn = Schema::hasColumn('courses', 'major') ? 'major' : null;
            $vipColumn = Schema::hasColumn('courses', 'is_vip') ? 'is_vip' : null;
            $feeColumn = Schema::hasColumn('courses', 'fee') ? 'fee' : null;
            $activeColumn = Schema::hasColumn('courses', 'active') ? 'active' : null;
            $coverColumn = Schema::hasColumn('courses', 'cover_url') ? 'cover_url' : null;

            $select = ['course_id'];
            if ($titleColumn) $select[] = $titleColumn;
            if ($majorColumn) $select[] = $majorColumn;
            if ($vipColumn) $select[] = $vipColumn;
            if ($feeColumn) $select[] = $feeColumn;
            if ($activeColumn) $select[] = $activeColumn;
            if ($coverColumn) $select[] = $coverColumn;

            $rows = Course::query()
                ->whereIn('course_id', $courseIds)
                ->get($select)
                ->keyBy('course_id');

            $courses = collect($courseIds)->map(function (int $courseId) use ($rows) {
                $row = $rows->get($courseId);
                if (!$row) {
                    return [
                        'courseId' => $courseId,
                        'title' => 'Course #' . $courseId,
                    ];
                }

                return [
                    'courseId' => (int) $row->course_id,
                    'title' => (string) ($row->title ?? $row->name ?? ''),
                    'major' => (string) ($row->major ?? ''),
                    'isVip' => isset($row->is_vip) ? (int) $row->is_vip : null,
                    'fee' => $row->fee ?? null,
                    'active' => isset($row->active) ? (int) $row->active : null,
                    'coverUrl' => isset($row->cover_url) ? (string) ($row->cover_url ?? '') : null,
                ];
            })->values()->all();
        }

        $meta = is_array($payment->meta) ? $payment->meta : [];
        $packagePlan = $meta['packagePlan'] ?? null;
        $packagePlan = is_string($packagePlan) ? $packagePlan : null;

        $payload = [
            'paymentInfo' => [
                'paymentId' => (int) ($payment->id ?? 0),
                'userId' => $userId,
                'major' => (string) ($payment->major ?? ''),
                'paymentAmount' => $payment->amount,
                'packagePlan' => $packagePlan,
                'screenshotUrl' => $this->normalizeUrl((string) ($payment->screenshot ?? '')),
            ],
            'userInfo' => [
                'userId' => $userId,
                'username' => trim($username),
                'email' => trim($email),
                'phone' => trim($phone),
                'image' => trim($userImage),
            ],
            'coursesInfo' => $courses,
        ];

        return $this->successResponse($payload);
    }

    public function activate(Request $request, int $paymentId, NotificationDispatchService $dispatch)
    {
        $admin = $request->user();
        if (! $admin instanceof Admin) {
            return $this->errorResponse('Forbidden', 403);
        }

        if (! $admin->hasPermission('user')) {
            return $this->errorResponse('Forbidden', 403);
        }

        if (! Schema::hasTable('payments')) {
            return $this->errorResponse('Payments table not found.', 422);
        }

        if (! Schema::hasTable('vipusers')) {
            return $this->errorResponse('Vipusers table not found.', 422);
        }

        $paymentRow = Payment::query()->find($paymentId);
        if (! $paymentRow) {
            return $this->errorResponse('Payment not found.', 404);
        }

        if ((int) ($paymentRow->activated ?? 0) === 1) {
            return $this->successResponse([
                'paymentId' => (int) $paymentRow->id,
                'activated' => true,
            ]);
        }

        $userId = trim((string) ($paymentRow->user_id ?? ''));
        if ($userId === '' || $userId === '0' || !ctype_digit($userId)) {
            return $this->errorResponse('Payment user_id is missing.', 422);
        }

        $courseIds = collect($paymentRow->courses ?? [])
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value > 0)
            ->unique()
            ->values();

        if ($courseIds->isEmpty()) {
            return $this->errorResponse('Payment courses are empty.', 422);
        }

        $hasVipUserId = Schema::hasColumn('vipusers', 'user_id');
        $hasVipPhone = Schema::hasColumn('vipusers', 'phone');
        $vipUserColumn = $hasVipUserId ? 'user_id' : ($hasVipPhone ? 'phone' : null);
        if ($vipUserColumn === null) {
            return $this->errorResponse('Vipusers user column not found.', 422);
        }

        $hasVipCourseId = Schema::hasColumn('vipusers', 'course_id');
        $hasVipCourse = Schema::hasColumn('vipusers', 'course');
        $vipCourseColumn = $hasVipCourseId ? 'course_id' : ($hasVipCourse ? 'course' : null);
        if ($vipCourseColumn === null) {
            return $this->errorResponse('Vipusers course column not found.', 422);
        }

        $hasVipMajor = Schema::hasColumn('vipusers', 'major');
        $hasVipDate = Schema::hasColumn('vipusers', 'date');
        $major = strtolower(trim((string) ($paymentRow->major ?? '')));
        $now = now();
        $paymentMeta = is_array($paymentRow->meta) ? $paymentRow->meta : [];
        $packagePlan = strtolower(trim((string) ($paymentMeta['packagePlan'] ?? '')));

        try {
            DB::transaction(function () use (
                $paymentRow,
                $userId,
                $courseIds,
                $vipUserColumn,
                $vipCourseColumn,
                $hasVipUserId,
                $hasVipPhone,
                $hasVipMajor,
                $hasVipDate,
                $major,
                $now,
                $packagePlan
            ) {
                $existingCourseIds = DB::table('vipusers')
                    ->where($vipUserColumn, $userId)
                    ->whereIn($vipCourseColumn, $courseIds->all())
                    ->pluck($vipCourseColumn)
                    ->map(fn ($value) => (int) $value)
                    ->unique()
                    ->values();

                $missingCourseIds = $courseIds->diff($existingCourseIds)->values();

                if ($missingCourseIds->isNotEmpty()) {
                    $batch = [];
                    foreach ($missingCourseIds as $courseId) {
                        $row = [
                            $vipUserColumn => $userId,
                            $vipCourseColumn => (int) $courseId,
                        ];

                        if ($hasVipUserId && $vipUserColumn !== 'user_id') {
                            $row['user_id'] = $userId;
                        }
                        if ($hasVipPhone && $vipUserColumn !== 'phone') {
                            $row['phone'] = (string) $userId;
                        }
                        if ($hasVipMajor && $major !== '') {
                            $row['major'] = $major;
                        }
                        if ($hasVipDate) {
                            $row['date'] = $now;
                        }

                        $batch[] = $row;
                    }

                    DB::table('vipusers')->insert($batch);
                }

                if (Schema::hasTable('user_data')) {
                    $userDataUpdate = [];
                    if (Schema::hasColumn('user_data', 'is_vip')) {
                        $userDataUpdate['is_vip'] = 1;
                    }
                    if ($packagePlan === 'diamond' && Schema::hasColumn('user_data', 'diamond_plan')) {
                        $userDataUpdate['diamond_plan'] = 1;
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

                $paymentRow->activated = 1;
                $paymentRow->save();
            });
        } catch (ValidationException $e) {
            return $this->errorResponse('Activation failed.', 422);
        } catch (\Throwable $e) {
            return $this->errorResponse('Activation failed.', 500);
        }

        $dispatch->notifyUserDatabase($userId, [
            'type' => 'payment.activated',
            'actor' => [
                'userId' => 10000,
                'name' => 'Admin',
            ],
            'target' => [
                'paymentId' => (int) ($paymentRow->id ?? 0),
                'major' => $major,
            ],
            'navigation' => [
                'routeName' => 'PurchasedCourses',
                'params' => [
                    'major' => (string) $major,
                ],
            ],
            'metadata' => [
                'courses' => array_values($courseIds->all()),
            ],
        ], 'App\\Notifications\\PaymentActivated');

        $dispatch->pushToUserTokens(
            $userId,
            'Subscription Activated',
            'Your course subscription has been activated.',
            [
                'type' => 'payment.activated',
                'major' => $major,
                'courses' => json_encode(array_values($courseIds->all())),
                'navigation' => [
                    'routeName' => 'PurchasedCourses',
                    'params' => [
                        'major' => (string) $major,
                    ],
                ],
            ]
        );

        return $this->successResponse([
            'paymentId' => (int) ($paymentRow->id ?? 0),
            'userId' => $userId,
            'major' => $major,
            'courses' => array_values($courseIds->all()),
            'activated' => true,
        ]);
    }
}

