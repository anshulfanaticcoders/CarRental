<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureAwinClick
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->has('awc')) {
            $awcValue = $request->query('awc');
            if (is_string($awcValue) && preg_match('/^\d+_\d+_[a-f0-9]+$/', $awcValue)) {
                $response->cookie(
                    'awc',
                    $awcValue,
                    525600,
                    '/',
                    null,
                    true,
                    false,
                    false,
                    'None'
                );
            }
        }

        return $response;
    }
}
