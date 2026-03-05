<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAffiliateRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->role !== 'affiliate') {
            abort(403, 'Access denied. Affiliate role required.');
        }

        if (!$user->affiliateBusiness) {
            abort(403, 'No affiliate business found for this account.');
        }

        return $next($request);
    }
}
