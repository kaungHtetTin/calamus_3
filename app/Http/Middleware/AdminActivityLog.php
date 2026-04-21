<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Closure;

class AdminActivityLog
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $method = strtoupper((string) $request->method());
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }

        if (!Schema::hasTable('activity_logs')) {
            return $response;
        }

        $admin = $request->user('admin');
        if (!$admin instanceof Admin) {
            $actor = $request->user();
            if ($actor instanceof Admin) {
                $admin = $actor;
            }
        }

        if (!$admin instanceof Admin) {
            return $response;
        }

        $action = match ($method) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'unknown',
        };

        $payload = $this->sanitizePayload($request->all());

        DB::table('activity_logs')->insert([
            'admin_id' => (int) $admin->id,
            'method' => $method,
            'action' => $action,
            'path' => substr((string) $request->path(), 0, 500),
            'route_name' => $request->route() ? (string) ($request->route()->getName() ?? '') : null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status_code' => (int) $response->getStatusCode(),
            'created_at' => now(),
        ]);

        return $response;
    }

    private function sanitizePayload(array $payload): array
    {
        $sensitive = ['password', 'password_confirmation', 'token', 'authorization', 'secret'];
        $sanitized = [];

        foreach ($payload as $key => $value) {
            $normalizedKey = strtolower((string) $key);
            $isSensitive = false;
            foreach ($sensitive as $word) {
                if (str_contains($normalizedKey, $word)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $sanitized[$key] = '***';
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizePayload($value);
                continue;
            }

            if (is_object($value)) {
                $sanitized[$key] = '[object]';
                continue;
            }

            if (is_string($value) && mb_strlen($value) > 2000) {
                $sanitized[$key] = mb_substr($value, 0, 2000) . '...';
                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }
}

