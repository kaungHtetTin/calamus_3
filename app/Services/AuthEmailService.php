<?php

namespace App\Services;

use App\Models\Learner;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;

class AuthEmailService
{
    public function __construct(
        private PhpMailerMailService $mailer
    ) {
    }

    /**
     * Send a 6-digit OTP if a learner exists with this email (silent if not).
     */
    public function sendForgotPasswordEmail(string $email): void
    {
        $email = trim($email);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $learner = Learner::where('learner_email', $email)->first();
        if (!$learner) {
            return;
        }

        $otp = $this->generateSixDigitOtp();
        $ttlMinutes = max(1, (int) config('phpmailer.password_reset_otp_ttl_minutes', 15));

        DB::table('password_resets')->updateOrInsert(
            ['email' => $email],
            [
                'email' => $email,
                'token' => Hash::make($otp),
                'created_at' => now(),
            ]
        );

        $html = View::make('emails.auth.forgot-password', [
            'appName' => config('app.name'),
            'recipientName' => $learner->learner_name,
            'otpCode' => $otp,
            'expiresMinutes' => $ttlMinutes,
        ])->render();

        $this->mailer->sendHtml(
            $email,
            (string) $learner->learner_name,
            'Your password reset code for '.config('app.name'),
            $html
        );
    }

    /**
     * Set a new password after forgot-password, using email + 6-digit OTP (no login required).
     *
     * @throws \Exception
     */
    public function completeForgotPassword(string $email, string $code, string $newPassword): Learner
    {
        $email = trim($email);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('A valid email is required.', 400);
        }

        $code = preg_replace('/\D/', '', $code);
        if (strlen($code) !== 6) {
            throw new \Exception('Enter the 6-digit reset code.', 400);
        }

        if (strlen($newPassword) < 6 || strlen($newPassword) > 256) {
            throw new \Exception('Password must be between 6 and 256 characters.', 400);
        }

        $row = DB::table('password_resets')->where('email', $email)->first();
        if (!$row || !Hash::check($code, $row->token)) {
            throw new \Exception('Invalid or incorrect reset code.', 400);
        }

        $ttlMinutes = max(1, (int) config('phpmailer.password_reset_otp_ttl_minutes', 15));
        $created = Carbon::parse($row->created_at);
        if ($created->copy()->addMinutes($ttlMinutes)->isPast()) {
            DB::table('password_resets')->where('email', $email)->delete();
            throw new \Exception('This reset code has expired. Please request a new one.', 400);
        }

        $learner = Learner::where('learner_email', $email)->first();
        if (!$learner) {
            DB::table('password_resets')->where('email', $email)->delete();
            throw new \Exception('Account not found.', 404);
        }

        $learner->password = Hash::make($newPassword);
        $learner->save();

        DB::table('password_resets')->where('email', $email)->delete();

        $learner->tokens()->delete();
        $learner->auth_token = '';
        $learner->save();

        $this->sendPasswordChangedEmail($learner);

        return $learner->fresh();
    }

    /**
     * Confirm email using the 6-digit OTP from the verification email.
     *
     * @throws \Exception
     */
    public function verifyEmail(string $email, string $code): Learner
    {
        $email = trim($email);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('A valid email is required.', 400);
        }

        $code = preg_replace('/\D/', '', $code);
        if (strlen($code) !== 6) {
            throw new \Exception('Enter the 6-digit verification code.', 400);
        }

        $row = DB::table('learner_email_verifications')
            ->where('email', $email)
            ->orderByDesc('id')
            ->first();

        if (!$row || !Hash::check($code, $row->token)) {
            throw new \Exception('Invalid or incorrect verification code.', 400);
        }

        if (Carbon::parse($row->expires_at)->isPast()) {
            DB::table('learner_email_verifications')->where('id', $row->id)->delete();
            throw new \Exception('This code has expired. Request a new verification code.', 400);
        }

        $learner = Learner::where('user_id', $row->user_id)->where('learner_email', $email)->first();
        if (!$learner) {
            DB::table('learner_email_verifications')->where('id', $row->id)->delete();
            throw new \Exception('Account not found.', 404);
        }

        $learner->email_verified_at = now();
        $learner->save();

        DB::table('learner_email_verifications')->where('email', $email)->delete();

        return $learner->fresh();
    }

    /**
     * Send (or re-send) verification email for a learner who has not verified yet.
     *
     * @throws \Exception
     */
    public function sendEmailVerification(Learner $learner): void
    {
        $email = trim((string) $learner->learner_email);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Add a valid email address to your account before verifying.', 400);
        }

        if ($learner->email_verified_at !== null) {
            throw new \Exception('This email is already verified.', 400);
        }

        $otp = $this->generateSixDigitOtp();
        $ttlMinutes = max(1, (int) config('phpmailer.email_verification_otp_ttl_minutes', 15));

        DB::table('learner_email_verifications')->where('email', $email)->delete();

        DB::table('learner_email_verifications')->insert([
            'email' => $email,
            'user_id' => $learner->user_id,
            'token' => Hash::make($otp),
            'expires_at' => now()->addMinutes($ttlMinutes),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $html = View::make('emails.auth.verify-email', [
            'appName' => config('app.name'),
            'recipientName' => $learner->learner_name,
            'email' => $email,
            'otpCode' => $otp,
            'expiresMinutes' => $ttlMinutes,
        ])->render();

        $this->mailer->sendHtml(
            $email,
            (string) $learner->learner_name,
            'Your verification code for '.config('app.name'),
            $html
        );
    }

    /**
     * Cryptographically random 6-digit numeric code (e.g. 000000–999999).
     */
    private function generateSixDigitOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function sendPasswordChangedEmail(Learner $learner): void
    {
        $email = trim((string) $learner->learner_email);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $html = View::make('emails.auth.password-changed', [
            'appName' => config('app.name'),
            'recipientName' => $learner->learner_name,
        ])->render();

        $this->mailer->sendHtml(
            $email,
            (string) $learner->learner_name,
            'Your '.config('app.name').' password was changed',
            $html
        );
    }
}
