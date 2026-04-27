<?php

namespace App\Services;

use App\Models\Language;
use App\Models\Learner;
use App\Models\UserData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AuthService
{
    public function __construct(
        private AuthEmailService $authEmailService
    ) {
    }

    public function checkAccountAvailability(?string $email, ?string $phone): array
    {
        $normalizedEmail = trim((string) ($email ?? ''));
        $normalizedPhone = trim((string) ($phone ?? ''));

        $emailExists = false;
        $phoneExists = false;

        if ($normalizedEmail !== '') {
            $emailExists = Learner::where('learner_email', $normalizedEmail)->exists();
        }

        if ($normalizedPhone !== '') {
            $phoneExists = Learner::where('learner_phone', $normalizedPhone)->exists();
        }

        return [
            'email' => [
                'value' => $normalizedEmail !== '' ? $normalizedEmail : null,
                'isRegistered' => $emailExists,
                'isAvailable' => ! $emailExists,
            ],
            'phone' => [
                'value' => $normalizedPhone !== '' ? $normalizedPhone : null,
                'isRegistered' => $phoneExists,
                'isAvailable' => ! $phoneExists,
            ],
        ];
    }

    public function login($identifier, $password, $major = null, $deviceType = 'mobile', $fcmToken = null, $platform = null)
    {
        $user = null;

        // Check if identifier is email
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $user = Learner::where('learner_email', $identifier)->first();
        } else {
            // Assume phone
            $user = Learner::where('learner_phone', $identifier)->first();
        }

        if (! $user) {
            throw new \Exception('Account not found. Please check your login details.', 404);
        }

        if (! Hash::check($password, $user->password)) {
            throw new \Exception('Incorrect password. Please try again.', 401);
        }

        // Sanctum Token Logic
        $tokenName = 'web';
        if ($major) {
            if (in_array(strtolower($deviceType), ['tablet', 'ipad'])) {
                $tokenName = $major.'|tablet';
            } else {
                $tokenName = $major.'|mobile';
            }
        }

        // Limit 1 per type
        $user->tokens()->where('name', $tokenName)->delete();

        $tokenResult = $user->createToken($tokenName);
        $plainTextToken = $tokenResult->plainTextToken;

        // Legacy token support
        $user->auth_token = $plainTextToken;
        $user->save();
        $this->ensureUserDataRows((string) $user->user_id, $major);
        $fcmDevice = trim((string) ($platform ?? '')) !== '' ? (string) $platform : (string) $deviceType;
        $this->syncFcmToken((string) $user->user_id, $major, $fcmToken, $fcmDevice);

        return [
            'token' => $plainTextToken,
            'user' => $this->formatUser($user),
        ];
    }

    public function socialLogin(
        string $provider,
        string $providerUserId,
        ?string $email,
        ?string $name,
        ?string $avatarUrl,
        ?string $accessToken,
        ?string $refreshToken,
        ?int $expiresIn,
        array $raw,
        ?string $major = null,
        string $deviceType = 'mobile',
        ?string $fcmToken = null,
        ?string $platform = null
    ): array {
        $normalizedProvider = strtolower(trim($provider));
        if (! in_array($normalizedProvider, ['google', 'facebook'], true)) {
            throw new \Exception('Unsupported provider.', 400);
        }

        $normalizedProviderUserId = trim($providerUserId);
        if ($normalizedProviderUserId === '') {
            throw new \Exception('Invalid provider user id.', 400);
        }

        if (! Schema::hasTable('social_accounts')) {
            throw new \Exception('Social login is not available. Please run migrations.', 500);
        }

        $normalizedEmail = trim((string) ($email ?? ''));
        $normalizedName = trim((string) ($name ?? ''));
        $normalizedAvatar = trim((string) ($avatarUrl ?? ''));

        $accessToken = $accessToken !== null && trim($accessToken) !== '' ? trim($accessToken) : null;
        $refreshToken = $refreshToken !== null && trim($refreshToken) !== '' ? trim($refreshToken) : null;
        $tokenExpiresAt = null;
        if ($expiresIn !== null && $expiresIn > 0) {
            $tokenExpiresAt = now()->addSeconds($expiresIn);
        }

        try {
            DB::beginTransaction();

            $social = DB::table('social_accounts')
                ->where('provider', $normalizedProvider)
                ->where('provider_user_id', $normalizedProviderUserId)
                ->lockForUpdate()
                ->first();

            $learner = null;

            if ($social) {
                $learner = Learner::where('user_id', (string) $social->user_id)->lockForUpdate()->first();
            }

            if (! $learner && $normalizedEmail !== '') {
                $emailKey = mb_strtolower($normalizedEmail);
                $learner = Learner::whereRaw('LOWER(TRIM(learner_email)) = ?', [$emailKey])->lockForUpdate()->first();
            }

            if (! $learner) {
                $displayName = $normalizedName;
                if ($displayName === '' && $normalizedEmail !== '') {
                    $displayName = explode('@', $normalizedEmail)[0] ?? 'User';
                }
                if ($displayName === '') {
                    $displayName = 'User';
                }

                $placeholder = \Illuminate\Support\Facades\Storage::disk('uploads')->url('placeholder.png');
                $learner = new Learner();
                $learner->learner_phone = 0;
                $learner->learner_email = $normalizedEmail !== '' ? $normalizedEmail : '';
                $learner->learner_name = $displayName;
                $learner->password = Hash::make(bin2hex(random_bytes(32)));
                $learner->learner_image = $normalizedAvatar !== '' ? $normalizedAvatar : env('APP_URL').$placeholder;
                $learner->cover_image = '';
                $learner->save();
            }

            $payload = [
                'user_id' => (string) $learner->user_id,
                'provider' => $normalizedProvider,
                'provider_user_id' => $normalizedProviderUserId,
                'provider_email' => $normalizedEmail !== '' ? $normalizedEmail : null,
                'avatar_url' => $normalizedAvatar !== '' ? $normalizedAvatar : null,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_expires_at' => $tokenExpiresAt,
                'raw' => empty($raw) ? null : json_encode($raw),
                'updated_at' => now(),
            ];

            if (! $social) {
                $payload['created_at'] = now();
                DB::table('social_accounts')->insert($payload);
            } else {
                DB::table('social_accounts')->where('id', (int) $social->id)->update($payload);
            }

            $tokenName = $this->resolveTokenName($major, $deviceType);
            $learner->tokens()->where('name', $tokenName)->delete();

            $tokenResult = $learner->createToken($tokenName);
            $plainTextToken = $tokenResult->plainTextToken;

            $learner->auth_token = $plainTextToken;
            $learner->save();

            $this->ensureUserDataRows((string) $learner->user_id, $major);
            $fcmDevice = trim((string) ($platform ?? '')) !== '' ? (string) $platform : (string) $deviceType;
            $this->syncFcmToken((string) $learner->user_id, $major, $fcmToken, $fcmDevice);

            DB::commit();

            return [
                'token' => $plainTextToken,
                'user' => $this->formatUser($learner),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            $code = is_numeric($e->getCode()) ? (int) $e->getCode() : 500;
            if ($code >= 400 && $code < 600) {
                throw new \Exception($e->getMessage(), $code);
            }
            throw new \Exception('Social login failed: '.$e->getMessage(), 500);
        }
    }

    public function register($data)
    {
        $name = trim($data['name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $fcmToken = trim((string) ($data['fcmToken'] ?? ''));
        $deviceType = trim((string) ($data['deviceType'] ?? 'mobile'));
        $platform = trim((string) ($data['platform'] ?? ''));
        $major = strtolower(trim((string) ($data['major'] ?? '')));

        // Validations (Service level validations could be more robust, but kept simple here)
        if ($name === '') {
            throw new \Exception('Name is required', 400);
        }
        if ($phone === '' && $email === '') {
            throw new \Exception('Phone number or email is required', 400);
        }
        if ($password === '') {
            throw new \Exception('Password is required', 400);
        }

        // Check existing phone (if provided)
        if ($phone !== '') {
            $existingPhone = Learner::where('learner_phone', $phone)->exists();
            if ($existingPhone) {
                throw new \Exception('This phone number is already registered. Please try logging in or use a different number.', 400);
            }
        }

        // Check existing email (if provided)
        if ($email !== '') {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Invalid email address', 400);
            }
            $existingEmail = Learner::where('learner_email', $email)->exists();
            if ($existingEmail) {
                throw new \Exception('This email address is already registered.', 400);
            }
        }

        try {
            DB::beginTransaction();

            $placeholder = \Illuminate\Support\Facades\Storage::disk('uploads')->url('placeholder.png');
            $hashedPassword = Hash::make($password);

            $learner = new Learner();
            $learner->learner_phone = $phone !== '' ? $phone : 0;
            $learner->learner_email = $email !== '' ? $email : '';
            $learner->learner_name = $name;
            $learner->password = $hashedPassword;
            $learner->learner_image = env('APP_URL').$placeholder;
            $learner->cover_image = '';

            $learner->save();

            $userId = (string) $learner->user_id;

            // Create Token
            $tokenResult = $learner->createToken('web');
            $plainTextToken = $tokenResult->plainTextToken;

            $learner->auth_token = $plainTextToken;
            $learner->save();

            $this->ensureUserDataRows($userId, $major);

            $fcmDevice = $platform !== '' ? $platform : $deviceType;
            $this->syncFcmToken($userId, $major !== '' ? $major : null, $fcmToken, $fcmDevice);

            DB::commit();

            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                try {
                    $this->authEmailService->sendEmailVerification($learner);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Verification email could not be sent: '.$e->getMessage());
                }
            }

            return [
                'token' => $plainTextToken,
                'user' => $this->formatUser($learner),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            $code = $e->getCode();
            // Ensure $code is an integer for the Exception constructor
            $intCode = is_numeric($code) ? (int) $code : 500;
            
            if ($intCode >= 400 && $intCode < 600) {
                throw new \Exception($e->getMessage(), $intCode);
            }
            throw new \Exception('Failed to create account: '.$e->getMessage(), 500);
        }
    }

    public function logout($user)
    {
        if ($user) {
            if ($user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }
            $user->auth_token = '';
            $user->save();
        }
    }

    public function syncFcmTokenForUser(string $userId, ?string $major, ?string $fcmToken, ?string $platform = null, ?string $deviceType = null): void
    {
        $fcmDevice = trim((string) ($platform ?? '')) !== '' ? (string) $platform : (string) ($deviceType ?? 'mobile');
        $this->syncFcmToken($userId, $major, $fcmToken, $fcmDevice);
    }

    public function formatUser($user, ?bool $blueMarkAccess = null, ?bool $diamondPlan = null)
    {
        $data = [
            'id' => $user->user_id,
            'name' => $user->learner_name,
            'email' => $user->learner_email,
            'phone' => $user->learner_phone,
            'image' => $user->learner_image,
            'emailVerified' => $user->email_verified_at !== null,
        ];

        if ($blueMarkAccess !== null) {
            $data['blueMarkAccess'] = $blueMarkAccess;
        }

        if ($diamondPlan !== null) {
            $data['diamondPlan'] = $diamondPlan;
        }

        return $data;
    }

    public function resolveBlueMarkAccess(string $userId, ?string $major): ?bool
    {
        $normalizedMajor = strtolower(trim((string) ($major ?? '')));
        if ($normalizedMajor === '') {
            return null;
        }

        $row = UserData::query()
            ->where('user_id', $userId)
            ->where('major', $normalizedMajor)
            ->first();

        if (! $row) {
            return false;
        }

        return (int) $row->is_vip === 1;
    }

    public function resolveDiamondPlanAccess(string $userId, ?string $major): ?bool
    {
        $normalizedMajor = strtolower(trim((string) ($major ?? '')));
        if ($normalizedMajor === '') {
            return null;
        }

        $row = UserData::query()
            ->where('user_id', $userId)
            ->where('major', $normalizedMajor)
            ->first();

        if (! $row) {
            return false;
        }

        return (int) $row->diamond_plan === 1;
    }

    private function syncFcmToken(string $userId, ?string $major, ?string $fcmToken, string $deviceType = 'mobile'): void
    {
        $token = $this->normalizeFcmToken($fcmToken);
        if ($token === null) {
            return;
        }

        $normalizedMajor = strtolower(trim((string) ($major ?? '')));
        $now = now();

        $platform = 'android';
        $normalizedDevice = strtolower(trim($deviceType));
        if ($normalizedDevice === 'ios' || $normalizedDevice === 'iphone' || $normalizedDevice === 'ipad') {
            $platform = 'ios';
        }

        if ($normalizedMajor !== '') {
            $row = UserData::where('user_id', $userId)->where('major', $normalizedMajor)->first();

            if ($row) {
                if ($row->first_join === null) {
                    $row->first_join = $now;
                }
                
                $tokens = is_array($row->token) ? $row->token : [];
                $tokens[$platform] = $token;
                $row->token = $tokens;
                
                $row->last_active = $now;
                
                $row->save();
            } else {
                UserData::create([
                    'user_id' => $userId,
                    'major' => $normalizedMajor,
                    'is_vip' => 0,
                    'diamond_plan' => 0,
                    'game_score' => 0,
                    'login_time' => 0,
                    'first_join' => $now,
                    'last_active' => $now,
                    'token' => [$platform => $token],
                ]);
            }

            return;
        }

        // We do not global update for all majors anymore because of lazy loading
        // We just do nothing if no major is provided, since FCM token requires a major context or it won't be saved
    }

    private function resolveTokenName(?string $major, string $deviceType): string
    {
        $tokenName = 'web';

        $normalizedMajor = trim((string) ($major ?? ''));
        if ($normalizedMajor === '') {
            return $tokenName;
        }

        if (in_array(strtolower($deviceType), ['tablet', 'ipad'], true)) {
            return $normalizedMajor.'|tablet';
        }

        return $normalizedMajor.'|mobile';
    }

    private function normalizeFcmToken(?string $fcmToken): ?string
    {
        $token = trim((string) ($fcmToken ?? ''));
        if ($token === '') {
            return null;
        }

        return mb_substr($token, 0, 500);
    }

    public function ensureUserDataRows(string $userId, ?string $currentMajor = null): void
    {
        $normalizedCurrentMajor = $currentMajor ? strtolower(trim($currentMajor)) : null;
        if (!$normalizedCurrentMajor) {
            return;
        }

        $now = now();

        try {
            $row = UserData::where('user_id', $userId)->where('major', $normalizedCurrentMajor)->first();

            if (! $row) {
                $data = [
                    'user_id' => $userId,
                    'major' => $normalizedCurrentMajor,
                    'is_vip' => 0,
                    'diamond_plan' => 0,
                    'game_score' => 0,
                    'login_time' => 0,
                    'first_join' => $now,
                    'last_active' => $now,
                ];
                UserData::create($data);
            } else {
                // Update activity for current major
                if ($row->first_join === null) {
                    $row->first_join = $now;
                }
                $row->last_active = $now;

                $row->save();
            }
        } catch (\Exception $e) {
            // Keep resilient if language row fails.
        }
    }
}
