<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Services\NotificationDispatchService;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Course;

class PaymentController extends Controller
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

    /**
     * Submit a payment record
     */
    public function paid(Request $request, NotificationDispatchService $dispatch)
    {
        $user = $request->user();
        if (!$user) {
            return $this->errorResponse('Not authenticated', 401);
        }

        $request->validate([
            'amount' => 'required|numeric',
            'major' => 'required|string',
            'courses' => 'required|array',
            'packagePlan' => 'required',
            'screenshot' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'transactionId' => 'required|string|unique:payments,transaction_id'
        ], [
            'transactionId.unique' => 'This transaction ID has already been submitted.'
        ]);

        try {
            $packagePlan = $request->input('packagePlan');

            $meta = [
                'packagePlan' => $packagePlan,
            ];

            $screenshotPath = '';
            if ($request->hasFile('screenshot')) {
                $file = $request->file('screenshot');
                $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
                $path = 'payments/screenshots';
                
                // Store in uploads disk
                $storedPath = Storage::disk('uploads')->putFileAs($path, $file, $filename);
                $screenshotPath = Storage::disk('uploads')->url($storedPath);
            }

            $payment = Payment::create([
                'user_id' => $user->user_id,
                'amount' => $request->amount,
                'major' => $request->major,
                'courses' => $request->courses,
                'meta' => $meta,
                'screenshot' => env('APP_URL') . $screenshotPath,
                'approve' => false,
                'activated' => false,
                'transaction_id' => $request->transactionId,
                'date' => now()
            ]);

            $major = strtolower(trim((string) ($payment->major ?? '')));
            $dispatch->notifyAdminDatabase([
                'type' => 'payment.created',
                'actor' => [
                    'userId' => (string) ($user->user_id ?? ''),
                    'name' => (string) ($user->learner_name ?? ''),
                    'image' => (string) ($user->learner_image ?? ''),
                ],
                'target' => [
                    'paymentId' => (int) ($payment->id ?? 0),
                    'major' => $major,
                ],
                'metadata' => [
                    'amount' => (string) ($payment->amount ?? ''),
                    'transactionId' => (string) ($payment->transaction_id ?? ''),
                ],
            ], 'App\\Notifications\\PaymentCreated');

            $dispatch->pushToAdminTopicByMajor(
                $major,
                'New Payment Submitted',
                'A user submitted a payment request.',
                [
                    'type' => 'payment.created',
                    'paymentId' => (string) ($payment->id ?? ''),
                    'major' => $major,
                ]
            );

            $purchasedCoursesByMajor = $this->getPurchasedCourseIdsByMajor((string) ($user->user_id ?? ''));

            return $this->successResponse($payment, 201, [
                'message' => 'Payment submitted successfully. Please wait for approval.',
                'purchased_courses_by_major' => $purchasedCoursesByMajor,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to submit payment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get payment history for authenticated user
     */
    public function history(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->errorResponse('Not authenticated', 401);
        }

        $page = (int) ($request->query('page', 1));
        if ($page < 1) $page = 1;

        $limit = (int) ($request->query('limit', 20));
        if ($limit < 1) $limit = 20;
        if ($limit > 100) $limit = 100;

        $major = strtolower(trim((string) $request->query('major', '')));

        $approveColumn = $this->resolvePaymentApproveColumn();

        $query = Payment::query()->where('user_id', $user->user_id);
        if ($major !== '' && Schema::hasColumn('payments', 'major')) {
            $query->whereRaw('LOWER(TRIM(major)) = ?', [$major]);
        }

        $total = (int) (clone $query)->count();
        $payments = $query
            ->orderByDesc('id')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        $courseIds = $payments
            ->flatMap(fn ($p) => collect($p->courses ?? []))
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => $v > 0)
            ->unique()
            ->values()
            ->all();

        $coursesById = collect();
        if (!empty($courseIds) && Schema::hasTable('courses') && Schema::hasColumn('courses', 'course_id')) {
            $titleColumn = Schema::hasColumn('courses', 'title') ? 'title' : (Schema::hasColumn('courses', 'name') ? 'name' : null);
            $coverColumn = Schema::hasColumn('courses', 'cover_url') ? 'cover_url' : null;
            $majorColumn = Schema::hasColumn('courses', 'major') ? 'major' : null;
            $vipColumn = Schema::hasColumn('courses', 'is_vip') ? 'is_vip' : null;

            $select = ['course_id'];
            if ($titleColumn) $select[] = $titleColumn;
            if ($coverColumn) $select[] = $coverColumn;
            if ($majorColumn) $select[] = $majorColumn;
            if ($vipColumn) $select[] = $vipColumn;

            $coursesById = Course::query()->whereIn('course_id', $courseIds)->get($select)->keyBy('course_id');
        }

        $data = $payments->map(function (Payment $payment) use ($coursesById, $approveColumn) {
            $meta = is_array($payment->meta) ? $payment->meta : [];
            $packagePlan = $meta['packagePlan'] ?? null;
            $packagePlan = is_string($packagePlan) ? $packagePlan : null;

            $courseIds = collect($payment->courses ?? [])
                ->map(fn ($v) => (int) $v)
                ->filter(fn ($v) => $v > 0)
                ->unique()
                ->values()
                ->all();

            $courses = collect($courseIds)->map(function (int $courseId) use ($coursesById) {
                $row = $coursesById->get($courseId);
                if (!$row) {
                    return [
                        'courseId' => $courseId,
                        'title' => 'Course #' . $courseId,
                    ];
                }

                return [
                    'courseId' => (int) $row->course_id,
                    'title' => (string) ($row->title ?? $row->name ?? ''),
                    'coverUrl' => isset($row->cover_url) ? (string) ($row->cover_url ?? '') : null,
                    'major' => isset($row->major) ? (string) ($row->major ?? '') : null,
                    'isVip' => isset($row->is_vip) ? (int) $row->is_vip : null,
                ];
            })->values()->all();

            $approved = null;
            if ($approveColumn) {
                $approved = (bool) ($payment->{$approveColumn} ?? false);
            }

            return [
                'paymentId' => (int) ($payment->id ?? 0),
                'amount' => $payment->amount ?? null,
                'major' => (string) ($payment->major ?? ''),
                'transactionId' => (string) ($payment->transaction_id ?? ''),
                'screenshot' => (string) ($payment->screenshot ?? ''),
                'packagePlan' => $packagePlan,
                'approved' => $approved,
                'activated' => (bool) ($payment->activated ?? false),
                'date' => $payment->date ? $payment->date->toDateTimeString() : null,
                'courses' => $courses,
            ];
        })->values();

        return $this->successResponse($data, 200, $this->paginate($total, $page, $limit));
    }

    private function getPurchasedCourseIdsByMajor(string $userId): array
    {
        $userId = trim($userId);
        if ($userId === '' || $userId === '0' || !ctype_digit($userId)) {
            return [];
        }

        $result = [];

        if (Schema::hasTable('payments')) {
            $payments = Payment::query()
                ->where('user_id', $userId)
                ->get(['major', 'courses', 'activated']);

            foreach ($payments as $payment) {
                $major = strtolower(trim((string) ($payment->major ?? '')));
                if ($major === '') {
                    continue;
                }

                $courseIds = collect($payment->courses ?? [])
                    ->map(fn ($value) => (int) $value)
                    ->filter(fn ($value) => $value > 0)
                    ->unique()
                    ->values()
                    ->all();

                if ($courseIds === []) {
                    continue;
                }

                if (!array_key_exists($major, $result)) {
                    $result[$major] = [];
                }

                $result[$major] = array_values(array_unique(array_merge($result[$major], $courseIds)));
            }
        }

        if (Schema::hasTable('vipusers')) {
            $hasVipUserId = Schema::hasColumn('vipusers', 'user_id');
            $hasVipPhone = Schema::hasColumn('vipusers', 'phone');
            $vipUserColumn = $hasVipUserId ? 'user_id' : ($hasVipPhone ? 'phone' : null);
            if ($vipUserColumn) {
                $hasVipCourseId = Schema::hasColumn('vipusers', 'course_id');
                $hasVipCourse = Schema::hasColumn('vipusers', 'course');
                $vipCourseColumn = $hasVipCourseId ? 'course_id' : ($hasVipCourse ? 'course' : null);

                if ($vipCourseColumn) {
                    $hasVipMajor = Schema::hasColumn('vipusers', 'major');

                    if ($hasVipMajor) {
                        $rows = DB::table('vipusers')
                            ->where($vipUserColumn, $userId)
                            ->get([$vipCourseColumn . ' as course_id', 'major']);

                        foreach ($rows as $row) {
                            $major = strtolower(trim((string) ($row->major ?? '')));
                            $courseId = (int) ($row->course_id ?? 0);
                            if ($major === '' || $courseId <= 0) {
                                continue;
                            }
                            if (!array_key_exists($major, $result)) {
                                $result[$major] = [];
                            }
                            $result[$major][] = $courseId;
                        }
                    } elseif (Schema::hasTable('courses') && Schema::hasColumn('courses', 'course_id') && Schema::hasColumn('courses', 'major')) {
                        $rows = DB::table('vipusers')
                            ->leftJoin('courses', 'vipusers.' . $vipCourseColumn, '=', 'courses.course_id')
                            ->where('vipusers.' . $vipUserColumn, $userId)
                            ->get(['vipusers.' . $vipCourseColumn . ' as course_id', 'courses.major as major']);

                        foreach ($rows as $row) {
                            $major = strtolower(trim((string) ($row->major ?? '')));
                            $courseId = (int) ($row->course_id ?? 0);
                            if ($major === '' || $courseId <= 0) {
                                continue;
                            }
                            if (!array_key_exists($major, $result)) {
                                $result[$major] = [];
                            }
                            $result[$major][] = $courseId;
                        }
                    }

                    foreach ($result as $major => $courseIds) {
                        $result[$major] = array_values(array_unique(array_map('intval', $courseIds)));
                    }
                }
            }
        }

        ksort($result);
        foreach ($result as $major => $courseIds) {
            sort($courseIds);
            $result[$major] = $courseIds;
        }

        return $result;
    }
}
