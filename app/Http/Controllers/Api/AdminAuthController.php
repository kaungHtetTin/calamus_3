<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminAuthController extends Controller
{
    use ApiResponse;

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:100',
            'password' => 'required|string',
            'deviceName' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Email and password are required', 400);
        }

        $email = (string) $request->input('email', '');
        $password = (string) $request->input('password', '');
        $deviceName = trim((string) $request->input('deviceName', 'admin-mobile'));

        $admin = Admin::query()->where('email', $email)->first();
        if (! $admin || ! Hash::check($password, (string) $admin->password)) {
            return $this->errorResponse('Invalid credentials', 401);
        }

        $token = $admin->createToken($deviceName)->plainTextToken;

        return $this->successResponse([
            'token' => $token,
            'admin' => $admin,
        ]);
    }

    public function me(Request $request)
    {
        $admin = $request->user();
        if (! $admin instanceof Admin) {
            return $this->errorResponse('Invalid or expired token', 401);
        }

        return $this->successResponse([
            'admin' => $admin,
        ]);
    }

    public function logout(Request $request)
    {
        $actor = $request->user();
        if ($actor instanceof Admin && $request->user()?->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        return $this->successResponse([], 200, ['status' => 'success']);
    }
}
