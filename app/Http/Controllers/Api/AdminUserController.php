<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;

class AdminUserController extends Controller
{
    use ApiResponse;

    public function lookup(Request $request)
    {
        $admin = $request->user();
        if (! $admin instanceof Admin) {
            return $this->errorResponse('Forbidden', 403);
        }

        if (! $admin->hasPermission('user')) {
            return $this->errorResponse('Forbidden', 403);
        }

        if (! Schema::hasTable('learners') || ! Schema::hasColumn('learners', 'user_id')) {
            return $this->errorResponse('Learners table not found.', 422);
        }

        $email = trim((string) $request->query('email', ''));
        $phone = trim((string) $request->query('phone', ''));
        $q = trim((string) $request->query('q', ''));

        if ($email === '' && $phone === '' && $q !== '') {
            if (str_contains($q, '@')) {
                $email = $q;
            } else {
                $phone = $q;
            }
        }

        if ($email === '' && $phone === '') {
            return $this->errorResponse('email or phone is required.', 422);
        }

        $normalizedPhoneInt = null;
        if ($phone !== '') {
            if (!preg_match('/^\d+$/', $phone)) {
                return $this->errorResponse('phone must be an integer value.', 422);
            }
            $normalizedPhoneInt = (int) $phone;
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
        $imageColumn = Schema::hasColumn('learners', 'learner_image')
            ? 'learner_image'
            : (Schema::hasColumn('learners', 'image') ? 'image' : null);

        if ($email !== '' && $emailColumn === null) {
            return $this->errorResponse('Email search is not supported.', 422);
        }
        if ($normalizedPhoneInt !== null && $phoneColumn === null) {
            return $this->errorResponse('Phone search is not supported.', 422);
        }

        $select = ['user_id'];
        if ($nameColumn) $select[] = $nameColumn;
        if ($emailColumn) $select[] = $emailColumn;
        if ($phoneColumn) $select[] = $phoneColumn;
        if ($imageColumn) $select[] = $imageColumn;
        if (Schema::hasColumn('learners', 'email_verified_at')) $select[] = 'email_verified_at';

        $query = DB::table('learners');
        if ($email !== '') {
            $normalizedEmail = mb_strtolower(trim($email));
            $query->whereRaw('LOWER(TRIM(' . $emailColumn . ')) = ?', [$normalizedEmail]);
        } else {
            $query->whereRaw('CAST(' . $phoneColumn . ' AS UNSIGNED) = ?', [$normalizedPhoneInt]);
        }

        $rows = $query->limit(2)->get($select);
        if ($rows->count() === 0) {
            return $this->errorResponse('User not found.', 404);
        }
        if ($rows->count() > 1) {
            return $this->errorResponse('Multiple users matched. Please refine search.', 422);
        }

        $row = $rows->first();
        $data = [
            'userId' => (int) ($row->user_id ?? 0),
            'username' => trim((string) ($row->{$nameColumn} ?? '')),
            'email' => $emailColumn ? trim((string) ($row->{$emailColumn} ?? '')) : '',
            'phone' => $phoneColumn ? (int) ($row->{$phoneColumn} ?? 0) : 0,
            'image' => $imageColumn ? trim((string) ($row->{$imageColumn} ?? '')) : '',
            'emailVerifiedAt' => isset($row->email_verified_at) && $row->email_verified_at ? (string) $row->email_verified_at : null,
        ];

        return $this->successResponse($data);
    }

    public function show(Request $request, int $userId)
    {
        $admin = $request->user();
        if (! $admin instanceof Admin) {
            return $this->errorResponse('Forbidden', 403);
        }

        if (! $admin->hasPermission('user')) {
            return $this->errorResponse('Forbidden', 403);
        }

        if (! Schema::hasTable('learners') || ! Schema::hasColumn('learners', 'user_id')) {
            return $this->errorResponse('Learners table not found.', 422);
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
        $imageColumn = Schema::hasColumn('learners', 'learner_image')
            ? 'learner_image'
            : (Schema::hasColumn('learners', 'image') ? 'image' : null);

        $select = ['user_id'];
        if ($nameColumn) $select[] = $nameColumn;
        if ($emailColumn) $select[] = $emailColumn;
        if ($phoneColumn) $select[] = $phoneColumn;
        if ($imageColumn) $select[] = $imageColumn;
        if (Schema::hasColumn('learners', 'email_verified_at')) $select[] = 'email_verified_at';

        $row = DB::table('learners')
            ->where('user_id', (int) $userId)
            ->first($select);

        if (! $row) {
            return $this->errorResponse('User not found.', 404);
        }

        $data = [
            'userId' => (int) ($row->user_id ?? 0),
            'username' => trim((string) ($row->{$nameColumn} ?? '')),
            'email' => $emailColumn ? trim((string) ($row->{$emailColumn} ?? '')) : '',
            'phone' => $phoneColumn ? (int) ($row->{$phoneColumn} ?? 0) : 0,
            'image' => $imageColumn ? trim((string) ($row->{$imageColumn} ?? '')) : '',
            'emailVerifiedAt' => isset($row->email_verified_at) && $row->email_verified_at ? (string) $row->email_verified_at : null,
        ];

        return $this->successResponse($data);
    }

    public function resetPassword(Request $request)
    {
        $admin = $request->user();
        if (! $admin instanceof Admin) {
            return $this->errorResponse('Forbidden', 403);
        }

        if (! $admin->hasPermission('user')) {
            return $this->errorResponse('Forbidden', 403);
        }

        $validator = Validator::make($request->all(), [
            'userId' => ['required'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        if (! Schema::hasTable('learners') || ! Schema::hasColumn('learners', 'user_id') || ! Schema::hasColumn('learners', 'password')) {
            return $this->errorResponse('Learners table not found.', 422);
        }

        $userId = trim((string) $request->input('userId'));
        if ($userId === '' || !ctype_digit($userId)) {
            return $this->errorResponse('Invalid userId.', 422);
        }

        $password = (string) $request->input('password');

        $exists = DB::table('learners')->where('user_id', $userId)->exists();
        if (! $exists) {
            return $this->errorResponse('User not found.', 404);
        }

        DB::table('learners')->where('user_id', $userId)->update([
            'password' => Hash::make($password),
        ]);

        $revoked = PersonalAccessToken::query()
            ->where('tokenable_type', 'App\\Models\\Learner')
            ->where('tokenable_id', (int) $userId)
            ->delete();

        return $this->successResponse([
            'userId' => (int) $userId,
            'passwordReset' => true,
            'tokensRevoked' => (int) $revoked,
        ]);
    }
}

