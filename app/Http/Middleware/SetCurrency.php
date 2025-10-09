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

        try {
            $location = Location::get();

            $currency = 'USD'; // Default currency

            // Log for debugging
            if ($location) {
                \Log::info('Location detected:', [
                    'country_code' => $location->countryCode,
                    'country_name' => $location->countryName ?? 'Unknown',
                    'city' => $location->cityName ?? 'Unknown',
                    'ip' => $location->ip ?? 'Unknown'
                ]);

                if ($location->countryCode) {
                    $currency = match ($location->countryCode) {
                        // North America
                        'US' => 'USD',
                        'CA' => 'CAD',
                        'MX' => 'MXN',
                        'NI' => 'NIO',

                        // Europe - Eurozone
                        'FR', 'DE', 'IT', 'ES', 'NL', 'BE', 'AT', 'PT', 'FI', 'IE', 'GR', 'CY', 'MT', 'SK', 'SI', 'EE', 'LV', 'LT' => 'EUR',

                        // Europe - Non-Eurozone
                        'GB' => 'GBP',
                        'CH' => 'CHF',
                        'SE' => 'SEK',
                        'NO' => 'NOK',
                        'DK' => 'DKK',
                        'IS' => 'ISK',

                        // Asia Pacific
                        'JP' => 'JPY',
                        'AU' => 'AUD',
                        'NZ' => 'NZD',
                        'CN' => 'CNH',
                        'HK' => 'HKD',
                        'SG' => 'SGD',
                        'KR' => 'KRW',
                        'IN' => 'INR',
                        'MY' => 'MYR',
                        'TH' => 'THB',
                        'ID' => 'IDR',
                        'PH' => 'PHP',
                        'VN' => 'VND',

                        // Middle East & Africa
                        'AE' => 'AED',
                        'SA' => 'SAR',
                        'IL' => 'ILS',
                        'ZA' => 'ZAR',
                        'MA' => 'MAD',
                        'EG' => 'EGP',
                        'NG' => 'NGN',
                        'KE' => 'KES',
                        'UG' => 'UGX',

                        // South America
                        'BR' => 'BRL',
                        'AR' => 'ARS',
                        'CL' => 'CLP',
                        'CO' => 'COP',
                        'PE' => 'PEN',
                        'VE' => 'VES',

                        // Others
                        'RU' => 'RUB',
                        'TR' => 'TRY',
                        'JO' => 'JOD',
                        'AZ' => 'AZN',
                        'OM' => 'OMR',
                        'BH' => 'BHD',
                        'QA' => 'QAR',
                        'KW' => 'KWD',

                        default => 'USD',
                    };
                }
            } else {
                \Log::warning('Location detection failed - location is null');
            }

            // Store currency in session
            session(['currency' => $currency]);

            \Log::info('Currency set based on location:', [
                'currency' => $currency,
                'detected_from' => $location ? 'IP detection' : 'default'
            ]);

        } catch (\Exception $e) {
            \Log::error('Location detection error: ' . $e->getMessage());

            // Fallback to USD if location detection fails
            session(['currency' => 'USD']);
        }

        return $next($request);
    }
}
