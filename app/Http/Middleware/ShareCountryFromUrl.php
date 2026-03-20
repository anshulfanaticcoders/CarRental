<?php

namespace App\Http\Middleware;

use Closure;

class ShareCountryFromUrl
{
    public function handle($request, Closure $next)
    {
        // Priority 1: country from URL parameter
        $country = $request->route('country');

        if ($country) {
            $country = strtolower($country);
            session(['country' => $country]);
        } else {
            // Priority 2: session country (set by SetCurrency middleware)
            $country = session('country', 'us');
        }

        view()->share('country', $country);
        inertia()->share('country', $country);

        return $next($request);
    }
}
