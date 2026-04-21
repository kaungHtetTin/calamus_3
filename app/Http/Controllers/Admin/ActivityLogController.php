<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'admin_id' => trim((string) $request->query('admin_id', '')),
            'action' => trim((string) $request->query('action', '')),
            'method' => strtoupper(trim((string) $request->query('method', ''))),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
        ];

        $query = DB::table('activity_logs as al')
            ->leftJoin('admins as a', 'a.id', '=', 'al.admin_id')
            ->select([
                'al.id',
                'al.admin_id',
                'al.method',
                'al.action',
                'al.path',
                'al.route_name',
                'al.ip_address',
                'al.user_agent',
                'al.request_payload',
                'al.status_code',
                'al.created_at',
                'a.name as admin_name',
                'a.email as admin_email',
            ]);

        if ($filters['q'] !== '') {
            $needle = strtolower($filters['q']);
            $query->where(function ($sub) use ($needle) {
                $sub->whereRaw('LOWER(COALESCE(a.name, "")) like ?', ["%{$needle}%"])
                    ->orWhereRaw('LOWER(COALESCE(a.email, "")) like ?', ["%{$needle}%"])
                    ->orWhereRaw('LOWER(COALESCE(al.path, "")) like ?', ["%{$needle}%"])
                    ->orWhereRaw('LOWER(COALESCE(al.route_name, "")) like ?', ["%{$needle}%"]);
            });
        }

        if ($filters['admin_id'] !== '' && ctype_digit($filters['admin_id'])) {
            $query->where('al.admin_id', (int) $filters['admin_id']);
        }

        if (in_array($filters['action'], ['create', 'update', 'delete'], true)) {
            $query->where('al.action', $filters['action']);
        }

        if (in_array($filters['method'], ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $query->where('al.method', $filters['method']);
        }

        if ($filters['date_from'] !== '') {
            $query->whereDate('al.created_at', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate('al.created_at', '<=', $filters['date_to']);
        }

        $logs = $query
            ->orderByDesc('al.id')
            ->paginate(30)
            ->withQueryString();

        $logs->setCollection(
            $logs->getCollection()->map(function ($row) {
                $payload = $row->request_payload;
                if (is_string($payload) && $payload !== '') {
                    $decoded = json_decode($payload, true);
                    $payload = is_array($decoded) ? $decoded : [];
                } elseif (!is_array($payload)) {
                    $payload = [];
                }

                return [
                    'id' => (int) $row->id,
                    'admin_id' => $row->admin_id !== null ? (int) $row->admin_id : null,
                    'admin_name' => (string) ($row->admin_name ?? ''),
                    'admin_email' => (string) ($row->admin_email ?? ''),
                    'method' => (string) ($row->method ?? ''),
                    'action' => (string) ($row->action ?? ''),
                    'path' => (string) ($row->path ?? ''),
                    'route_name' => (string) ($row->route_name ?? ''),
                    'ip_address' => (string) ($row->ip_address ?? ''),
                    'user_agent' => (string) ($row->user_agent ?? ''),
                    'request_payload' => $payload,
                    'status_code' => $row->status_code !== null ? (int) $row->status_code : null,
                    'created_at' => $row->created_at,
                ];
            })
        );

        $adminOptions = DB::table('admins')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'name' => (string) ($row->name ?? ''),
                    'email' => (string) ($row->email ?? ''),
                ];
            })
            ->values();

        return Inertia::render('Admin/ActivityLogs', [
            'logs' => $logs,
            'filters' => $filters,
            'adminOptions' => $adminOptions,
        ]);
    }

    public function clear(Request $request)
    {
        DB::table('activity_logs')->delete();

        return redirect()
            ->route('admin.activity-logs.index', [], 303)
            ->with('success', 'Activity logs cleared.');
    }
}
