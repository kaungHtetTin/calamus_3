<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceJsonResponse
{
    public function handle(Request $request, Closure $next)
    {
        $accept = $request->headers->get('Accept');
        if (!$accept || strpos($accept, 'application/json') === false) {
            $request->headers->set('Accept', 'application/json');
        }
        return $next($request);
    }
}
