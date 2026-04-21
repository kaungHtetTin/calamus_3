<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\Exception as PhpMailerException;
use PHPMailer\PHPMailer\PHPMailer;

class PhpMailerMailService
{
    /**
     * Send HTML email via SMTP using PHPMailer.
     *
     * When {@see config('mail.default')} is `log` or `array`, no SMTP connection is made; the message is
     * written to the log (for local dev without Mailpit/SMTP). Matches Laravel's MAIL_MAILER convention.
     *
     * @throws \RuntimeException
     */
    public function sendHtml(string $toEmail, string $toName, string $subject, string $htmlBody, ?string $altBody = null): void
    {
        $driver = (string) config('mail.default', env('MAIL_MAILER', 'smtp'));

        if (in_array($driver, ['log', 'array'], true)) {
            Log::info('[Mail] '.$subject, [
                'to' => $toEmail,
                'to_name' => $toName,
                'driver' => $driver,
            ]);
            if (config('app.debug')) {
                Log::debug('[Mail HTML]', ['body' => $htmlBody]);
            }

            return;
        }

        $host = config('phpmailer.host');
        if ($host === null || $host === '') {
            throw new \RuntimeException('Mail is not configured (set MAIL_HOST in .env, or use MAIL_MAILER=log for local testing).');
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->Port = config('phpmailer.port', 587);
            $user = (string) config('phpmailer.username', '');
            $pass = (string) config('phpmailer.password', '');
            if ($user !== '' || $pass !== '') {
                $mail->SMTPAuth = true;
                $mail->Username = $user;
                $mail->Password = $pass;
            } else {
                $mail->SMTPAuth = false;
            }

            $enc = strtolower((string) config('phpmailer.encryption', 'tls'));
            if ($enc === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($enc === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPAutoTLS = false;
                $mail->SMTPSecure = '';
            }

            $from = config('phpmailer.from.address', 'hello@example.com');
            $fromName = config('phpmailer.from.name', config('app.name'));
            $mail->setFrom($from, $fromName);
            $mail->addAddress($toEmail, $toName !== '' ? $toName : $toEmail);

            $mail->isHTML(true);
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $altBody ?? strip_tags($htmlBody);

            $mail->send();
        } catch (PhpMailerException $e) {
            throw new \RuntimeException('Mail could not be sent: '.$e->getMessage(), 0, $e);
        }
    }
}
