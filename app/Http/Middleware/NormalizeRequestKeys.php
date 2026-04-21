<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NormalizeRequestKeys
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Recursively convert all snake_case keys in query and input to camelCase
        $request->replace($this->camelizeKeys($request->all()));
        $request->query->replace($this->camelizeKeys($request->query->all()));

        return $next($request);
    }

    /**
     * Recursively convert array keys to camelCase.
     */
    protected function camelizeKeys(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $camelKey = Str::camel($key);

            if (is_array($value)) {
                $result[$camelKey] = $this->camelizeKeys($value);
            } else {
                $result[$camelKey] = $value;
            }
        }

        return $result;
    }
}
