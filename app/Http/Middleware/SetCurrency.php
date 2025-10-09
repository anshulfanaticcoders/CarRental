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
        // Always detect location and update currency if it changes
        // This ensures currency updates when user travels to different countries

        try {
            $location = Location::get();

            // If location fails on localhost, use a fallback method
            if (!$location && app()->environment('local')) {
                $location = $this->getLocationFallback($request);
            }

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

            // Update currency only if it's different from current session currency
            $currentCurrency = session('currency');
            if ($currentCurrency !== $currency) {
                session(['currency' => $currency]);

                \Log::info('Currency updated:', [
                    'old_currency' => $currentCurrency,
                    'new_currency' => $currency,
                    'detected_country' => $location->countryCode ?? 'Unknown'
                ]);
            }

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

    /**
     * Fallback method for localhost development
     */
    private function getLocationFallback(Request $request)
    {
        // Known IP mappings for testing
        $knownIps = [
            '49.43.142.103' => ['countryCode' => 'IN', 'countryName' => 'India', 'cityName' => 'Shimla'],
            '8.8.8.8' => ['countryCode' => 'US', 'countryName' => 'United States', 'cityName' => 'Mountain View'],
            '8.8.4.4' => ['countryCode' => 'GB', 'countryName' => 'United Kingdom', 'cityName' => 'London'],
            '8.8.8.1' => ['countryCode' => 'JP', 'countryName' => 'Japan', 'cityName' => 'Tokyo'],
            '8.8.8.2' => ['countryCode' => 'DE', 'countryName' => 'Germany', 'cityName' => 'Frankfurt'],
            '8.8.8.3' => ['countryCode' => 'AU', 'countryName' => 'Australia', 'cityName' => 'Sydney'],
        ];

        // Try to get real external IP for localhost
        if ($request->ip() === '127.0.0.1' || $request->ip() === '::1') {
            try {
                $externalIp = file_get_contents('https://ipinfo.io/json');
                if ($externalIp) {
                    $ipData = json_decode($externalIp, true);
                    if ($ipData && isset($ipData['country'])) {
                        return (object) [
                            'ip' => $ipData['ip'] ?? '127.0.0.1',
                            'countryCode' => $ipData['country'] ?? 'US',
                            'countryName' => $ipData['country'] ?? 'Unknown',
                            'cityName' => $ipData['city'] ?? 'Unknown',
                        ];
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('External IP detection failed: ' . $e->getMessage());
            }

            // Fallback to known IP (your India IP for testing)
            \Log::info('Using fallback IP for localhost testing');
            return (object) $knownIps['49.43.142.103'];
        }

        return null;
    }
}
