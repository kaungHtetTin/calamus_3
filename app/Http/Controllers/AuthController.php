<?php

namespace App\Http\Controllers;

use App\Services\AuthEmailService;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    use ApiResponse;

    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(Request $request)
    {
        // Allow phone OR email
        $validator = Validator::make($request->all(), [
            'phone' => 'required_without:email|string|max:32',
            'email' => 'required_without:phone|email|max:100',
            'password' => 'required|string',
            'fcmToken' => 'nullable|string|max:500',
            'platform' => 'nullable|string|in:ios,android,andorid',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Phone number/Email and password are required', 400);
        }

        $identifier = $request->input('email') ?: $request->input('phone');
        $password = $request->input('password');
        $major = $request->input('major');
        $deviceType = $request->input('deviceType', 'mobile');
        $platform = $request->input('platform');
        $fcmToken = $request->input('fcmToken');

        try {
            $data = $this->authService->login($identifier, $password, $major, $deviceType, $fcmToken, $platform);

            return $this->successResponse($data);
        } catch (\Exception $e) {
            $code = $e->getCode();
            if ($code < 400 || $code > 500) {
                $code = 400;
            }

            return $this->errorResponse($e->getMessage(), $code);
        }
    }

    public function register(Request $request)
    {
        $input = $request->all();
        $name = trim($input['name'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $fcmToken = trim((string) ($input['fcmToken'] ?? ''));
        $platform = strtolower(trim((string) ($input['platform'] ?? '')));

        $errors = [];
        if ($name === '') {
            $errors[] = 'Name is required';
        } elseif (is_numeric($name)) {
            $errors[] = 'Name cannot be a number';
        }
        if (strlen($name) > 100) {
            $errors[] = 'Name is too long';
        }

        if ($phone === '' && $email === '') {
            $errors[] = 'Phone number or email is required';
        }
        if ($phone !== '' && ! preg_match('/^[0-9+\s\-]{6,32}$/', $phone)) {
            $errors[] = 'Please enter a valid phone number';
        }

        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address';
        }

        if ($password === '') {
            $errors[] = 'Password is required';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        } elseif (strlen($password) > 256) {
            $errors[] = 'Password is too long';
        }
        if ($fcmToken !== '' && strlen($fcmToken) > 500) {
            $errors[] = 'FCM token is too long';
        }
        if ($platform !== '' && ! in_array($platform, ['ios', 'android', 'andorid'], true)) {
            $errors[] = 'Invalid platform';
        }

        $major = strtolower(trim((string) ($input['major'] ?? '')));
        if ($major !== '' && strlen($major) > 20) {
            $errors[] = 'Invalid major';
        }

        if (! empty($errors)) {
            return $this->errorResponse(implode('. ', $errors), 400);
        }

        try {
            $data = $this->authService->register($input);

            return $this->successResponse($data);
        } catch (\Exception $e) {
            $code = $e->getCode();
            if ($code < 400 || $code > 500) {
                $code = 500;
            }

            return $this->errorResponse($e->getMessage(), $code);
        }
    }

    public function checkAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'nullable|string|max:32|required_without:email',
            'email' => 'nullable|email|max:100|required_without:phone',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Phone number or valid email is required', 400);
        }

        $email = $request->input('email');
        $phone = $request->input('phone');

        $data = $this->authService->checkAccountAvailability($email, $phone);

        return $this->successResponse($data);
    }

    public function socialRedirect(Request $request, string $provider)
    {
        try {
            $normalizedProvider = $this->normalizeSocialProvider($provider);
        } catch (\Throwable $e) {
            return $this->errorResponse('Unsupported provider.', 400);
        }

        try {
            return $this->socialiteDriver($normalizedProvider)->stateless()->redirect();
        } catch (\Throwable $e) {
            return $this->errorResponse('Social login is not configured for this provider.', 400);
        }
    }

    public function socialCallback(Request $request, string $provider)
    {
        try {
            $normalizedProvider = $this->normalizeSocialProvider($provider);
        } catch (\Throwable $e) {
            return $this->errorResponse('Unsupported provider.', 400);
        }

        try {
            $socialUser = $this->socialiteDriver($normalizedProvider)->stateless()->user();
        } catch (\Throwable $e) {
            return $this->errorResponse('Unable to authenticate with provider.', 400);
        }

        $major = $request->query('major');
        $deviceType = $request->query('deviceType', 'mobile');
        $platform = $request->query('platform');
        $fcmToken = $request->query('fcmToken');

        try {
            $data = $this->authService->socialLogin(
                provider: $normalizedProvider,
                providerUserId: (string) ($socialUser->getId() ?? ''),
                email: $socialUser->getEmail(),
                name: $socialUser->getName() ?: $socialUser->getNickname(),
                avatarUrl: $socialUser->getAvatar(),
                accessToken: property_exists($socialUser, 'token') ? (string) ($socialUser->token ?? '') : null,
                refreshToken: property_exists($socialUser, 'refreshToken') ? (string) ($socialUser->refreshToken ?? '') : null,
                expiresIn: property_exists($socialUser, 'expiresIn') ? (int) ($socialUser->expiresIn ?? 0) : null,
                raw: (array) ($socialUser->user ?? []),
                major: is_string($major) ? $major : null,
                deviceType: is_string($deviceType) ? $deviceType : 'mobile',
                fcmToken: is_string($fcmToken) ? $fcmToken : null,
                platform: is_string($platform) ? $platform : null
            );

            return $this->successResponse($data);
        } catch (\Exception $e) {
            $code = (int) $e->getCode();
            if ($code < 400 || $code > 500) {
                $code = 400;
            }

            return $this->errorResponse($e->getMessage(), $code);
        }
    }

    public function socialToken(Request $request, string $provider)
    {
        try {
            $normalizedProvider = $this->normalizeSocialProvider($provider);
        } catch (\Throwable $e) {
            return $this->errorResponse('Unsupported provider.', 400);
        }

        $validator = Validator::make($request->all(), [
            'accessToken' => 'required|string',
            'major' => 'nullable|string|max:20',
            'deviceType' => 'nullable|string|max:20',
            'platform' => 'nullable|string|in:ios,android,andorid',
            'fcmToken' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 400);
        }

        try {
            $socialUser = $this->socialiteDriver($normalizedProvider)->stateless()->userFromToken((string) $request->input('accessToken'));
        } catch (\Throwable $e) {
            return $this->errorResponse('Unable to authenticate with provider token.', 400);
        }

        try {
            $data = $this->authService->socialLogin(
                provider: $normalizedProvider,
                providerUserId: (string) ($socialUser->getId() ?? ''),
                email: $socialUser->getEmail(),
                name: $socialUser->getName() ?: $socialUser->getNickname(),
                avatarUrl: $socialUser->getAvatar(),
                accessToken: (string) $request->input('accessToken'),
                refreshToken: property_exists($socialUser, 'refreshToken') ? (string) ($socialUser->refreshToken ?? '') : null,
                expiresIn: property_exists($socialUser, 'expiresIn') ? (int) ($socialUser->expiresIn ?? 0) : null,
                raw: (array) ($socialUser->user ?? []),
                major: $request->input('major'),
                deviceType: (string) $request->input('deviceType', 'mobile'),
                fcmToken: $request->input('fcmToken'),
                platform: $request->input('platform')
            );

            return $this->successResponse($data);
        } catch (\Exception $e) {
            $code = (int) $e->getCode();
            if ($code < 400 || $code > 500) {
                $code = 400;
            }

            return $this->errorResponse($e->getMessage(), $code);
        }
    }

    public function logout(Request $request)
    {
        $this->authService->logout($request->user());

        return $this->successResponse([], 200, ['status' => 'success']);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return $this->errorResponse('Invalid or expired token', 401);
        }

        $major = $request->input('major');
        $fcmToken = $request->input('fcmToken');
        $platform = $request->input('platform');
        $deviceType = $request->input('deviceType', 'mobile');

        $platformNormalized = strtolower(trim((string) ($platform ?? '')));
        if ($platformNormalized !== '' && ! in_array($platformNormalized, ['ios', 'android', 'andorid'], true)) {
            return $this->errorResponse('Invalid platform', 400);
        }

        if (is_string($fcmToken) && trim($fcmToken) !== '' && strlen((string) $fcmToken) > 500) {
            return $this->errorResponse('FCM token is too long', 400);
        }
        
        if ($major) {
            $this->authService->ensureUserDataRows((string) $user->user_id, $major);
        }

        if (is_string($fcmToken) && trim($fcmToken) !== '' && is_string($major) && trim($major) !== '') {
            $this->authService->syncFcmTokenForUser(
                userId: (string) $user->user_id,
                major: (string) $major,
                fcmToken: (string) $fcmToken,
                platform: $platformNormalized !== '' ? $platformNormalized : null,
                deviceType: (string) $deviceType
            );
        }
        
        $blueMarkAccess = $this->authService->resolveBlueMarkAccess((string) $user->user_id, $major);
        $diamondPlan = $this->authService->resolveDiamondPlanAccess((string) $user->user_id, $major);

        return $this->successResponse([
            'user' => $this->authService->formatUser($user, $blueMarkAccess, $diamondPlan),
        ]);
    }

    /**
     * Request a password reset OTP (email must match a registered account; response is generic).
     */
    public function forgotPassword(Request $request, AuthEmailService $authEmailService)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:100',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 400);
        }

        try {
            $authEmailService->sendForgotPasswordEmail((string) $request->input('email'));
        } catch (\Throwable $e) {
            return $this->mailSendFailureResponse($e, 'Forgot password email failed');
        }

        return $this->successResponse([
            'message' => 'If an account exists for that email, we sent a password reset code.',
        ]);
    }

    /**
     * Change password while logged in: current password + new password (no OTP).
     */
    public function resetPassword(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return $this->errorResponse('Not authenticated', 401);
        }

        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:6|max:256',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 400);
        }

        $old = $request->input('oldPassword', $request->input('currentPassword'));
        if ($old === null || $old === '') {
            return $this->errorResponse('Old password is required (use oldPassword or currentPassword).', 400);
        }

        $confirm = $request->input('passwordConfirmation', $request->input('password_confirmation'));
        if ((string) $confirm !== (string) $request->input('password')) {
            return $this->errorResponse('Password confirmation does not match.', 400);
        }
        if (! Hash::check((string) $old, $user->password)) {
            return $this->errorResponse('Incorrect current password.', 400);
        }

        $user->password = Hash::make((string) $request->input('password'));
        $user->save();

        return $this->successResponse([
            'message' => 'Your password has been updated.',
        ]);
    }

    /**
     * After forgot-password, set a new password using email + 6-digit OTP (not logged in).
     */
    public function confirmForgotPassword(Request $request, AuthEmailService $authEmailService)
    {
        $raw = $request->input('code', $request->input('otp'));
        $request->merge(['code' => preg_replace('/\D/', '', (string) $raw)]);

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:100',
            'code' => 'required|string|size:6|regex:/^\d{6}$/',
            'password' => 'required|string|min:6|max:256',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 400);
        }

        $confirm = $request->input('passwordConfirmation', $request->input('password_confirmation'));
        if ((string) $confirm !== (string) $request->input('password')) {
            return $this->errorResponse('Password confirmation does not match.', 400);
        }

        try {
            $authEmailService->completeForgotPassword(
                (string) $request->input('email'),
                (string) $request->input('code'),
                (string) $request->input('password')
            );

            return $this->successResponse([
                'message' => 'Your password has been reset. You can sign in with your new password.',
            ]);
        } catch (\Exception $e) {
            $code = (int) $e->getCode();
            if ($code < 400 || $code > 499) {
                $code = 400;
            }

            return $this->errorResponse($e->getMessage(), $code);
        }
    }

    /**
     * Verify email using the 6-digit OTP sent to the user's inbox.
     */
    public function verifyEmail(Request $request, AuthEmailService $authEmailService)
    {
        $raw = $request->input('code', $request->input('otp'));
        $request->merge(['code' => preg_replace('/\D/', '', (string) $raw)]);

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:100',
            'code' => 'required|string|size:6|regex:/^\d{6}$/',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 400);
        }

        try {
            $authEmailService->verifyEmail(
                (string) $request->input('email'),
                (string) $request->input('code')
            );

            return $this->successResponse([
                'message' => 'Your email has been verified.',
            ]);
        } catch (\Exception $e) {
            $code = (int) $e->getCode();
            if ($code < 400 || $code > 499) {
                $code = 400;
            }

            return $this->errorResponse($e->getMessage(), $code);
        }
    }

    /**
     * Resend the verification email (authenticated; account must have an email).
     */
    public function resendVerificationEmail(Request $request, AuthEmailService $authEmailService)
    {
        $user = $request->user();
        if (! $user) {
            return $this->errorResponse('Not authenticated', 401);
        }

        try {
            $authEmailService->sendEmailVerification($user);

            return $this->successResponse([
                'message' => 'We sent a new verification code to your email address.',
            ]);
        } catch (\Throwable $e) {
            $code = (int) $e->getCode();
            if ($e instanceof \Exception && $code >= 400 && $code < 500) {
                return $this->errorResponse($e->getMessage(), $code);
            }

            return $this->mailSendFailureResponse($e, 'Resend verification email failed');
        }
    }

    /**
     * Log mail errors; return real message when APP_DEBUG is true (helps fix SMTP / Mailpit locally).
     */
    protected function mailSendFailureResponse(\Throwable $e, string $logContext)
    {
        Log::warning($logContext.': '.$e->getMessage(), ['exception' => $e]);

        if (config('app.debug')) {
            return $this->errorResponse($e->getMessage(), 500);
        }

        return $this->errorResponse('Unable to send email. Check mail settings or try again later.', 500);
    }

    private function normalizeSocialProvider(string $provider): string
    {
        $p = strtolower(trim($provider));
        if (! in_array($p, ['google', 'facebook'], true)) {
            throw new \InvalidArgumentException('Unsupported provider.');
        }

        return $p;
    }

    private function socialiteDriver(string $provider)
    {
        if ($provider === 'google') {
            return Socialite::driver('google')->scopes(['openid', 'profile', 'email']);
        }

        return Socialite::driver('facebook')->scopes(['email'])->fields(['id', 'name', 'email']);
    }
}
