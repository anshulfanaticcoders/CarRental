<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Verifies the bearer token sent by the Vrooem Gateway for internal API calls.
 * Token is configured via GATEWAY_INTERNAL_TOKEN in .env.
 */
class VerifyGatewayToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        $expected = config('vrooem.internal_api_token');

        if (!$token || !$expected || $token !== $expected) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
