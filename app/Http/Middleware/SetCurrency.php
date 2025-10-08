<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Stevebauman\Location\Facades\Location;

class SetCurrency
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (session()->has('currency')) {
            return $next($request);
        }

        $location = Location::get();

        $currency = 'USD'; // Default currency
        if ($location && $location->countryCode) {
            $currency = match ($location->countryCode) {
                'US' => 'USD',
                'GB' => 'GBP',
                'FR', 'DE', 'IT', 'ES', 'NL' => 'EUR',
                'JP' => 'JPY',
                'CA' => 'CAD',
                'AU' => 'AUD',
                'IN' => 'INR',
                default => 'USD',
            };
        }

        session(['currency' => $currency]);

        return $next($request);
    }
}
