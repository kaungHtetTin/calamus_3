<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $sector
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string ...$sectors)
    {
        $admin = Auth::guard('admin')->user();

        $sectors = array_values(array_filter(array_map('trim', $sectors)));
        $hasAnySectorPermission = false;

        if ($admin) {
            foreach ($sectors as $s) {
                if ($admin->hasPermission($s)) {
                    $hasAnySectorPermission = true;
                    break;
                }
            }
        }

        if (!$admin || !$hasAnySectorPermission) {
            $label = count($sectors) > 1 ? implode(', ', $sectors) : ($sectors[0] ?? '');
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized access to ' . $label], 403);
            }
            return redirect()->route('admin.dashboard')->with('error', 'You do not have permission to access ' . $label);
        }

        return $next($request);
    }
}
