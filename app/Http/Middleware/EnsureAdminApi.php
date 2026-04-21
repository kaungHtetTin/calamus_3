<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;

class EnsureAdminApi
{
    public function handle(Request $request, Closure $next)
    {
        $actor = $request->user();
        if (! $actor instanceof Admin) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
            ], 403);
        }

        return $next($request);
    }
}
