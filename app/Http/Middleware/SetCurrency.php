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
        try {
            $currentCurrency = session('currency');
            $lastDetectionTime = session('currency_detection_time', 0);
            $currentTime = time();

            // Check if we should run automatic detection:
            // 1. No currency in session (brand new session), OR
            // 2. It's been more than 1 hour since last detection (new browser session)
            // 3. User hasn't manually changed currency in the last hour
            $shouldRunDetection = !$currentCurrency || ($currentTime - $lastDetectionTime > 3600);

            if ($shouldRunDetection) {
                \Log::info('Running automatic currency detection:', [
                    'reason' => !$currentCurrency ? 'no_currency_in_session' : 'hour_since_last_detection',
                    'current_currency' => $currentCurrency ?? 'none',
                    'time_since_detection' => $currentTime - $lastDetectionTime
                ]);

                // Automatic detection based on location
                $location = Location::get();

                // If location fails on localhost, use a fallback method
                if (!$location && app()->environment('local')) {
                    $location = $this->getLocationFallback($request);
                }

                $detectedCurrency = 'USD'; // Default currency

                // Log for debugging
                if ($location) {
                    \Log::info('Location detected:', [
                        'country_code' => $location->countryCode,
                        'country_name' => $location->countryName ?? 'Unknown',
                        'city' => $location->cityName ?? 'Unknown',
                        'ip' => $location->ip ?? 'Unknown'
                    ]);

                    if ($location->countryCode) {
                        $detectedCurrency = match ($location->countryCode) {
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

                // Only update currency if it's different from current
                // This respects manual user selection
                if ($currentCurrency !== $detectedCurrency) {
                    session(['currency' => $detectedCurrency]);
                    session(['currency_detection_time' => $currentTime]);

                    \Log::info('Currency automatically set based on location:', [
                        'currency' => $detectedCurrency,
                        'detected_from' => $location ? 'IP detection' : 'default',
                        'country' => $location->countryCode ?? 'Unknown',
                        'previous_currency' => $currentCurrency ?? 'none'
                    ]);
                } else {
                    // Update detection time even if currency didn't change
                    session(['currency_detection_time' => $currentTime]);
                    \Log::info('Location detected but currency unchanged:', [
                        'currency' => $currentCurrency,
                        'country' => $location->countryCode ?? 'Unknown'
                    ]);
                }
            } else {
                \Log::info('Using existing currency from session:', [
                    'currency' => $currentCurrency,
                    'source' => 'user_selection_or_recent_detection',
                    'time_since_detection' => $currentTime - $lastDetectionTime
                ]);
            }

        } catch (\Exception $e) {
            \Log::error('Location detection error: ' . $e->getMessage());

            // Only fallback to USD if no currency is set
            if (!session('currency')) {
                session(['currency' => 'USD']);
                session(['currency_detection_time' => time()]);
            }
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
