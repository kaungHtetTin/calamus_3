<?php

return [

    /*
    | Used only when MAIL_MAILER is not `log` or `array` (see PhpMailerMailService).
    | For local testing without SMTP, set MAIL_MAILER=log in .env.
    */
    'host' => env('MAIL_HOST', '127.0.0.1'),

    'port' => (int) env('MAIL_PORT', 587),

    'username' => env('MAIL_USERNAME'),

    'password' => env('MAIL_PASSWORD'),

    /*
    | tls, ssl, or empty string for none.
    */
    'encryption' => env('MAIL_ENCRYPTION', 'tls'),

    'timeout' => (int) env('MAIL_TIMEOUT', 15),

    'broadcast_delay_ms' => (int) env('MAIL_BROADCAST_DELAY_MS', 0),

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'help@calamuseducation.com'),
        'name' => env('MAIL_FROM_NAME', env('APP_NAME', 'Calamus')),
    ],

    /*
    | Forgot password uses a 6-digit OTP (no link). Minutes until the code expires.
    */
    'password_reset_otp_ttl_minutes' => (int) env('MAIL_PASSWORD_RESET_OTP_TTL', 15),

    /*
    | Email verification uses a 6-digit OTP (no link). Minutes until the code expires.
    */
    'email_verification_otp_ttl_minutes' => (int) env('MAIL_EMAIL_VERIFICATION_OTP_TTL', 15),

];
