<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Learner;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AdminVipAccessTransferController extends Controller
{
    use ApiResponse;

    public function preview(Request $request)
    {
        $admin = $request->user();
        if (!$admin instanceof Admin || !$admin->hasPermission('user')) {
            return $this->errorResponse('Forbidden', 403);
        }

        $data = $request->validate([
            'source' => ['nullable', 'string', 'max:200'],
            'target' => ['nullable', 'string', 'max:200'],
        ]);

        $sourceQuery = trim((string) ($data['source'] ?? ''));
        $targetQuery = trim((string) ($data['target'] ?? ''));

        $source = $sourceQuery !== '' ? $this->lookupLearnerByEmailOrPhoneOrId($sourceQuery) : null;
        $target = $targetQuery !== '' ? $this->lookupLearnerByEmailOrPhoneOrId($targetQuery) : null;

        $preview = null;
        if ($source && $target) {
            $preview = $this->buildPreview((string) $source['user_id'], (string) $target['user_id']);
        }

        return $this->successResponse([
            'sourceQuery' => $sourceQuery,
            'targetQuery' => $targetQuery,
            'sourceUser' => $source,
            'targetUser' => $target,
            'preview' => $preview,
        ]);
    }

    public function execute(Request $request)
    {
        $admin = $request->user();
        if (!$admin instanceof Admin || !$admin->hasPermission('user')) {
            return $this->errorResponse('Forbidden', 403);
        }

        $data = $request->validate([
            'sourceUserId' => ['required', 'string', 'regex:/^\d{1,20}$/', 'not_in:0'],
            'targetUserId' => ['required', 'string', 'regex:/^\d{1,20}$/', 'not_in:0'],
            'mode' => ['required', 'string', 'in:move,copy'],
        ]);

        $sourceUserId = trim((string) $data['sourceUserId']);
        $targetUserId = trim((string) $data['targetUserId']);
        $mode = (string) $data['mode'];

        if ($sourceUserId === '0' || $targetUserId === '0') {
            throw ValidationException::withMessages([
                'sourceUserId' => 'Source/Target user not found.',
            ]);
        }

        if ($sourceUserId === $targetUserId) {
            throw ValidationException::withMessages([
                'targetUserId' => 'Target must be different from source.',
            ]);
        }

        $sourceExists = Learner::query()->where('user_id', $sourceUserId)->exists();
        $targetExists = Learner::query()->where('user_id', $targetUserId)->exists();
        if (!$sourceExists || !$targetExists) {
            throw ValidationException::withMessages([
                'sourceUserId' => 'Source/Target user not found.',
            ]);
        }

        $previewBefore = $this->buildPreview($sourceUserId, $targetUserId);
        if (!$previewBefore['source_has_vip']) {
            throw ValidationException::withMessages([
                'sourceUserId' => 'Source user has no VIP access to transfer.',
            ]);
        }

        DB::transaction(function () use ($sourceUserId, $targetUserId, $mode) {
            $this->transferUserDataVip($sourceUserId, $targetUserId, $mode);
            $this->transferVipUsersCourses($sourceUserId, $targetUserId, $mode);
        });

        $previewAfter = $this->buildPreview($sourceUserId, $targetUserId);

        return $this->successResponse([
            'mode' => $mode,
            'message' => $mode === 'move' ? 'VIP access transferred.' : 'VIP access copied.',
            'previewBefore' => $previewBefore,
            'previewAfter' => $previewAfter,
        ]);
    }

    private function lookupLearnerByEmailOrPhoneOrId(string $query): ?array
    {
        if (!Schema::hasTable('learners') || !Schema::hasColumn('learners', 'user_id')) {
            return null;
        }

        $q = trim($query);
        if ($q === '') {
            return null;
        }

        $nameColumn = Schema::hasColumn('learners', 'learner_name')
            ? 'learner_name'
            : (Schema::hasColumn('learners', 'name') ? 'name' : null);
        $emailColumn = Schema::hasColumn('learners', 'learner_email')
            ? 'learner_email'
            : (Schema::hasColumn('learners', 'email') ? 'email' : null);
        $phoneColumn = Schema::hasColumn('learners', 'learner_phone')
            ? 'learner_phone'
            : (Schema::hasColumn('learners', 'phone') ? 'phone' : null);

        if (ctype_digit($q)) {
            $row = DB::table('learners')
                ->where('user_id', $q)
                ->orderByDesc('user_id')
                ->first([
                    'user_id',
                    $nameColumn ? $nameColumn . ' as name' : DB::raw("'' as name"),
                    $emailColumn ? $emailColumn . ' as email' : DB::raw("'' as email"),
                    $phoneColumn ? $phoneColumn . ' as phone' : DB::raw("'' as phone"),
                ]);

            if ($row) {
                return [
                    'user_id' => (string) ($row->user_id ?? ''),
                    'name' => (string) ($row->name ?? ''),
                    'email' => (string) ($row->email ?? ''),
                    'phone' => (string) ($row->phone ?? ''),
                ];
            }
        }

        $isEmail = str_contains($q, '@');
        if ($isEmail && $emailColumn === null) {
            return null;
        }
        if (!$isEmail && $phoneColumn === null) {
            return null;
        }

        $row = DB::table('learners')
            ->when($isEmail, function ($builder) use ($emailColumn, $q) {
                $builder->whereRaw('LOWER(TRIM(' . $emailColumn . ')) = ?', [mb_strtolower($q)]);
            })
            ->when(!$isEmail, function ($builder) use ($phoneColumn, $q) {
                $phone = preg_replace('/\D+/', '', $q);
                $builder->whereRaw('CAST(' . $phoneColumn . ' AS UNSIGNED) = ?', [(int) $phone]);
            })
            ->orderByDesc('user_id')
            ->first([
                'user_id',
                $nameColumn ? $nameColumn . ' as name' : DB::raw("'' as name"),
                $emailColumn ? $emailColumn . ' as email' : DB::raw("'' as email"),
                $phoneColumn ? $phoneColumn . ' as phone' : DB::raw("'' as phone"),
            ]);

        if (!$row) {
            return null;
        }

        return [
            'user_id' => (string) ($row->user_id ?? ''),
            'name' => (string) ($row->name ?? ''),
            'email' => (string) ($row->email ?? ''),
            'phone' => (string) ($row->phone ?? ''),
        ];
    }

    private function buildPreview(string $sourceUserId, string $targetUserId): array
    {
        $sourceVipMajors = [];
        $sourceDiamondMajors = [];
        $sourceVipCoursesCount = 0;

        if (Schema::hasTable('user_data')) {
            $rows = DB::table('user_data')
                ->where('user_id', $sourceUserId)
                ->get(['major', 'is_vip', 'diamond_plan']);

            foreach ($rows as $r) {
                $major = strtolower(trim((string) ($r->major ?? '')));
                if ($major === '') {
                    continue;
                }
                if ((int) ($r->is_vip ?? 0) === 1) {
                    $sourceVipMajors[] = $major;
                }
                if ((int) ($r->diamond_plan ?? 0) === 1) {
                    $sourceDiamondMajors[] = $major;
                }
            }
        }

        if (Schema::hasTable('vipusers')) {
            $sourceVipCoursesCount = (int) DB::table('vipusers')
                ->where('user_id', $sourceUserId)
                ->count();
        }

        $sourceVipMajors = array_values(array_unique($sourceVipMajors));
        $sourceDiamondMajors = array_values(array_unique($sourceDiamondMajors));

        return [
            'source_has_vip' => !empty($sourceVipMajors) || !empty($sourceDiamondMajors) || $sourceVipCoursesCount > 0,
            'source' => [
                'user_id' => $sourceUserId,
                'vip_majors' => $sourceVipMajors,
                'diamond_majors' => $sourceDiamondMajors,
                'vip_courses_count' => $sourceVipCoursesCount,
            ],
            'target' => [
                'user_id' => $targetUserId,
            ],
        ];
    }

    private function transferUserDataVip(string $sourceUserId, string $targetUserId, string $mode): void
    {
        if (!Schema::hasTable('user_data')) {
            return;
        }

        $rows = DB::table('user_data')
            ->where('user_id', $sourceUserId)
            ->where(function ($q) {
                $q->where('is_vip', 1)->orWhere('diamond_plan', 1);
            })
            ->get(['major', 'is_vip', 'diamond_plan']);

        $now = now();

        foreach ($rows as $r) {
            $major = strtolower(trim((string) ($r->major ?? '')));
            if ($major === '') {
                continue;
            }

            $vip = (int) ($r->is_vip ?? 0) === 1 ? 1 : 0;
            $diamond = (int) ($r->diamond_plan ?? 0) === 1 ? 1 : 0;

            DB::table('user_data')->updateOrInsert(
                ['user_id' => $targetUserId, 'major' => $major],
                [
                    'is_vip' => $vip,
                    'diamond_plan' => $diamond,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            if ($mode === 'move') {
                DB::table('user_data')
                    ->where('user_id', $sourceUserId)
                    ->whereRaw('LOWER(TRIM(major)) = ?', [$major])
                    ->update([
                        'is_vip' => 0,
                        'diamond_plan' => 0,
                        'updated_at' => $now,
                    ]);
            }
        }
    }

    private function transferVipUsersCourses(string $sourceUserId, string $targetUserId, string $mode): void
    {
        if (!Schema::hasTable('vipusers')) {
            return;
        }
        if (!Schema::hasColumn('vipusers', 'user_id') || !Schema::hasColumn('vipusers', 'course_id')) {
            return;
        }

        $hasMajor = Schema::hasColumn('vipusers', 'major');
        $courses = DB::table('vipusers')
            ->where('user_id', $sourceUserId)
            ->get($hasMajor ? ['course_id', 'major'] : ['course_id']);

        foreach ($courses as $row) {
            $courseId = (int) ($row->course_id ?? 0);
            if ($courseId <= 0) {
                continue;
            }
            $major = $hasMajor ? strtolower(trim((string) ($row->major ?? ''))) : null;

            $where = ['user_id' => $targetUserId, 'course_id' => $courseId];
            if ($hasMajor && $major !== null && $major !== '') {
                $where['major'] = $major;
            }

            DB::table('vipusers')->updateOrInsert($where, []);
        }

        if ($mode === 'move') {
            DB::table('vipusers')->where('user_id', $sourceUserId)->delete();
        }
    }
}

