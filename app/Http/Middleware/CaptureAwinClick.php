<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CaptureAwinClick
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('awc')) {
            $awcValue = $request->query('awc');

            // Uppercase hex is valid too — rejecting it silently cost attribution.
            if (is_string($awcValue) && preg_match('/^\d+_\d+_[A-Fa-f0-9]+$/', $awcValue)) {
                // Cookie::queue, not $response->cookie(): file/binary responses
                // (sitemap.xml, feeds) have no cookie() method — a decorated
                // ?awc= link onto one of those used to 500.
                Cookie::queue(Cookie::make('awc', $awcValue, 525600, '/', null, true, false, false, 'None'));
                // Click time — last-click attribution needs it to arbitrate
                // against Trabber / Skyscanner / QR-affiliate claims.
                Cookie::queue(Cookie::make('awc_at', now()->toIso8601String(), 525600, '/', null, true, false, false, 'None'));
            } elseif (is_string($awcValue) && $awcValue !== '') {
                Log::info('CaptureAwinClick: rejected awc format', ['awc' => substr($awcValue, 0, 64)]);
            }
        }

        return $next($request);
    }
}
