<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FcmService
{
    private string $serviceAccountPath;
    private ?array $serviceAccount = null;

    public function __construct()
    {
        $this->serviceAccountPath = base_path('important-firebase-key.json');
    }

    /**
     * Send a push notification to multiple tokens or a single token.
     */
    public function sendPush(string $token, string $title, string $body, array $data = [], ?string $image = null): bool
    {
        try {
            return $this->sendMessage([
                'token' => $token,
            ], $title, $body, $data, $image);
        } catch (\Exception $e) {
            Log::error('FCM Send Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send a push notification to a topic.
     */
    public function sendTopic(string $topic, string $title, string $body, array $data = [], ?string $image = null): bool
    {
        try {
            $topic = trim($topic);
            if ($topic === '') {
                return false;
            }

            return $this->sendMessage([
                'topic' => $topic,
            ], $title, $body, $data, $image);
        } catch (\Exception $e) {
            Log::error('FCM Topic Send Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Format data values to strings (FCM requirement for 'data' field).
     */
    private function formatData(array $data): array
    {
        $formatted = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $formatted[$key] = json_encode($value);
            } else {
                $formatted[$key] = (string) $value;
            }
        }
        return $formatted;
    }

    private function sendMessage(array $target, string $title, string $body, array $data = [], ?string $image = null): bool
    {
        $startedAt = microtime(true);
        $targetType = array_key_exists('topic', $target) ? 'topic' : 'token';
        $topic = array_key_exists('topic', $target) ? trim((string) $target['topic']) : null;
        $tokenFingerprint = array_key_exists('token', $target) ? $this->tokenFingerprint((string) $target['token']) : null;

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            Log::warning('FCM access token missing', $this->buildLogContext([
                'targetType' => $targetType,
                'topic' => $topic,
                'tokenFingerprint' => $tokenFingerprint,
            ], $title, $body, $data, $image));
            return false;
        }

        $project_id = $this->getServiceAccount()['project_id'];
        $url = "https://fcm.googleapis.com/v1/projects/{$project_id}/messages:send";

        $message = [
            'message' => array_merge($target, [
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $this->formatData($data),
                'android' => [
                    'notification' => [
                        'sound' => 'default',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1,
                        ],
                    ],
                ],
            ]),
        ];

        if ($image) {
            $message['message']['notification']['image'] = $image;
        }

        $client = new Client();
        $response = $client->post($url, [
            'http_errors' => false,
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ],
            'json' => $message,
        ]);

        $status = (int) $response->getStatusCode();
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        $rawBody = (string) $response->getBody();
        $decoded = json_decode($rawBody, true);
        $name = is_array($decoded) ? ($decoded['name'] ?? null) : null;

        $logContext = $this->buildLogContext([
            'targetType' => $targetType,
            'topic' => $topic,
            'tokenFingerprint' => $tokenFingerprint,
            'status' => $status,
            'durationMs' => $durationMs,
            'fcmName' => $name,
            'responseBody' => $status === 200 ? null : Str::limit($rawBody, 1500),
        ], $title, $body, $data, $image);

        if ($status === 200) {
            Log::info('FCM push sent', $logContext);
            return true;
        }

        Log::warning('FCM push failed', $logContext);
        return false;
    }

    /**
     * Get OAuth2 Access Token for FCM.
     */
    private function getAccessToken(): ?string
    {
        return Cache::remember('fcm_access_token', 3500, function () {
            try {
                $account = $this->getServiceAccount();
                $now = time();
                $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
                $payload = json_encode([
                    'iss' => $account['client_email'],
                    'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                    'aud' => $account['token_uri'],
                    'exp' => $now + 3600,
                    'iat' => $now,
                ]);

                $base64UrlHeader = $this->base64UrlEncode($header);
                $base64UrlPayload = $this->base64UrlEncode($payload);

                $signature = '';
                openssl_sign(
                    $base64UrlHeader . "." . $base64UrlPayload,
                    $signature,
                    $account['private_key'],
                    'SHA256'
                );
                $base64UrlSignature = $this->base64UrlEncode($signature);

                $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

                $client = new Client();
                $response = $client->post($account['token_uri'], [
                    'form_params' => [
                        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                        'assertion' => $jwt,
                    ],
                ]);

                $data = json_decode($response->getBody()->getContents(), true);
                return $data['access_token'] ?? null;
            } catch (\Exception $e) {
                Log::error('FCM Token Error: ' . $e->getMessage());
                return null;
            }
        });
    }

    private function getServiceAccount(): array
    {
        if ($this->serviceAccount === null) {
            if (!file_exists($this->serviceAccountPath)) {
                throw new \Exception('Service account file not found: ' . $this->serviceAccountPath);
            }
            $this->serviceAccount = json_decode(file_get_contents($this->serviceAccountPath), true);
        }
        return $this->serviceAccount;
    }

    private function base64UrlEncode(string $text): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($text));
    }

    private function tokenFingerprint(string $token): string
    {
        $token = trim($token);
        if ($token === '') {
            return '';
        }
        return substr(hash('sha256', $token), 0, 12);
    }

    private function buildLogContext(array $base, string $title, string $body, array $data, ?string $image): array
    {
        $type = null;
        if (array_key_exists('type', $data) && is_scalar($data['type'])) {
            $type = (string) $data['type'];
        }

        return array_filter(array_merge([
            'title' => Str::limit($title, 120),
            'bodyPreview' => Str::limit($body, 120),
            'dataType' => $type,
            'dataKeys' => array_values(array_map('strval', array_keys($data))),
            'hasImage' => $image !== null && trim((string) $image) !== '',
        ], $base), static fn ($v) => $v !== null);
    }
}
