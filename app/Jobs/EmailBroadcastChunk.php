<?php

namespace App\Jobs;

use App\Models\Learner;
use App\Services\PhpMailerMailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EmailBroadcastChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 3;

    public string $broadcastId;

    public string $subject;

    public string $body;

    public array $userIds;

    public function __construct(string $broadcastId, string $subject, string $body, array $userIds)
    {
        $this->onQueue('email-broadcast');
        $this->broadcastId = $broadcastId;
        $this->subject = $subject;
        $this->body = $body;
        $this->userIds = array_values($userIds);
    }

    public function handle(PhpMailerMailService $mail): void
    {
        $broadcastId = $this->broadcastId;
        $subject = $this->subject;
        $body = $this->body;
        $prefix = "email_broadcast:{$broadcastId}:";
        $expiresAt = now()->addDays(3);

        Cache::put($prefix.'status', 'running', $expiresAt);
        Cache::put($prefix.'last_user_id', null, $expiresAt);
        $this->logEmailBroadcast('info', 'job_start', ['id' => $broadcastId, 'users' => count($this->userIds)]);

        $learners = Learner::query()
            ->whereIn('user_id', $this->userIds)
            ->whereNotNull('email_verified_at')
            ->whereNotNull('learner_email')
            ->where('learner_email', '!=', '')
            ->orderBy('user_id')
            ->select(['user_id', 'learner_email', 'learner_name'])
            ->cursor();

        $sentDelta = 0;
        $failedDelta = 0;
        $processedDelta = 0;
        $lastUserId = null;
        $failureSamples = 0;
        $delayMs = (int) config('phpmailer.broadcast_delay_ms', 0);

        foreach ($learners as $learner) {
            $lastUserId = (int) ($learner->user_id ?? 0);
            if ($processedDelta === 0 && $lastUserId !== 0) {
                Cache::put($prefix.'last_user_id', $lastUserId, $expiresAt);
            }
            $toEmail = trim((string) ($learner->learner_email ?? ''));
            if ($toEmail === '') {
                $failedDelta++;
                $processedDelta++;

                continue;
            }
            $toName = (string) ($learner->learner_name ?? '');
            try {
                $html = view('emails.admin.user_message', [
                    'subject' => $subject,
                    'body' => $body,
                    'recipientName' => $toName,
                    'appName' => config('app.name'),
                ])->render();
                $mail->sendHtml($toEmail, $toName, $subject, $html);
                $sentDelta++;
            } catch (\Throwable $e) {
                $failedDelta++;
                if ($failureSamples < 3) {
                    $failureSamples++;
                    $this->logEmailBroadcast('warning', 'send_failed', [
                        'id' => $broadcastId,
                        'user_id' => $lastUserId,
                        'email' => $this->maskEmail($toEmail),
                        'error_type' => get_class($e),
                        'error' => Str::limit((string) $e->getMessage(), 240, '…'),
                    ]);
                }
            }

            if ($delayMs > 0) {
                usleep(min(5000, $delayMs) * 1000);
            }

            $processedDelta++;
            if (($processedDelta % 10) === 0) {
                if ($sentDelta > 0) {
                    Cache::increment($prefix.'sent', $sentDelta);
                }
                if ($failedDelta > 0) {
                    Cache::increment($prefix.'failed', $failedDelta);
                }
                Cache::increment($prefix.'processed', $sentDelta + $failedDelta);
                if ($lastUserId !== null) {
                    Cache::put($prefix.'last_user_id', $lastUserId, $expiresAt);
                }
                Cache::put($prefix.'status', 'running', $expiresAt);
                $sentDelta = 0;
                $failedDelta = 0;

                $overallProcessed = (int) Cache::get($prefix.'processed', 0);
                if ($overallProcessed > 0 && ($overallProcessed % 50) === 0) {
                    $total = (int) Cache::get($prefix.'total', 0);
                    $sent = (int) Cache::get($prefix.'sent', 0);
                    $failed = (int) Cache::get($prefix.'failed', 0);
                    $this->logEmailBroadcast('info', 'progress', [
                        'id' => $broadcastId,
                        'processed' => $overallProcessed,
                        'total' => $total,
                        'sent' => $sent,
                        'failed' => $failed,
                    ]);
                }
            }
        }

        if ($sentDelta > 0) {
            Cache::increment($prefix.'sent', $sentDelta);
        }
        if ($failedDelta > 0) {
            Cache::increment($prefix.'failed', $failedDelta);
        }
        Cache::increment($prefix.'processed', $sentDelta + $failedDelta);
        if ($lastUserId !== null) {
            Cache::put($prefix.'last_user_id', $lastUserId, $expiresAt);
        }
        Cache::put($prefix.'status', 'running', $expiresAt);

        $done = (int) Cache::increment($prefix.'jobs_done', 1);
        $totalJobs = (int) Cache::get($prefix.'jobs_total', 0);
        $dispatchDone = (int) Cache::get($prefix.'dispatch_done', 0);
        if ($dispatchDone === 1 && $totalJobs > 0 && $done >= $totalJobs) {
            Cache::put($prefix.'status', 'completed', $expiresAt);
            Cache::put($prefix.'finished_at', now()->toDateTimeString(), $expiresAt);
        }

        $total = (int) Cache::get($prefix.'total', 0);
        $sent = (int) Cache::get($prefix.'sent', 0);
        $failed = (int) Cache::get($prefix.'failed', 0);
        $processed = (int) Cache::get($prefix.'processed', 0);
        $this->logEmailBroadcast('info', 'job_done', [
            'id' => $broadcastId,
            'users' => count($this->userIds),
            'processed' => $processed,
            'total' => $total,
            'sent' => $sent,
            'failed' => $failed,
            'jobs_done' => $done,
            'jobs_total' => $totalJobs,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $broadcastId = $this->broadcastId;
        $prefix = "email_broadcast:{$broadcastId}:";
        $expiresAt = now()->addDays(3);

        Cache::put($prefix.'status', 'failed', $expiresAt);
        Cache::put($prefix.'finished_at', now()->toDateTimeString(), $expiresAt);
        $this->logEmailBroadcast('error', 'job_failed', [
            'id' => $broadcastId,
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);
    }

    private function maskEmail(string $email): string
    {
        $email = trim($email);
        if ($email === '') {
            return '';
        }

        $parts = explode('@', $email, 2);
        if (count($parts) !== 2) {
            return Str::limit($email, 3, '***');
        }

        [$local, $domain] = $parts;
        $local = trim($local);
        $domain = trim($domain);

        if ($local === '' || $domain === '') {
            return Str::limit($email, 3, '***');
        }

        $keep = mb_substr($local, 0, 2);

        return $keep.'***@'.$domain;
    }

    private function logEmailBroadcast(string $level, string $event, array $context = []): void
    {
        $payload = array_merge([
            'event' => $event,
        ], $context);

        try {
            Log::channel('email_broadcast')->log($level, $event, $payload);
        } catch (\Throwable $e) {
            try {
                Log::log('error', 'email_broadcast_log_channel_error', [
                    'event' => $event,
                    'level' => $level,
                    'channel_error' => $e->getMessage(),
                    'context' => $payload,
                ]);
            } catch (\Throwable $ignored) {
                return;
            }
        }
    }
}
