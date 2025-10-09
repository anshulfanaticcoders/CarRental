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
            $lastManualChangeTime = session('currency_manual_change_time', 0);
            $currentTime = time();
            $currentIP = $request->ip();
            $lastDetectedIP = session('last_detected_ip', '');

            // Detect location on every request to check for changes
            $location = Location::get();
            if (!$location && app()->environment('local')) {
                $location = $this->getLocationFallback($request);
            }

            $detectedCurrency = 'USD'; // Default currency
            $detectedCountry = 'US'; // Default country

            if ($location && $location->countryCode) {
                $detectedCountry = $location->countryCode;
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

                \Log::info('Location detected:', [
                    'country_code' => $location->countryCode,
                    'country_name' => $location->countryName ?? 'Unknown',
                    'city' => $location->cityName ?? 'Unknown',
                    'ip' => $location->ip ?? $currentIP,
                    'detected_currency' => $detectedCurrency
                ]);
            } else {
                \Log::warning('Location detection failed - location is null');
            }

            // Check if we should update currency:
            $shouldUpdateCurrency = false;
            $updateReason = '';

            // 1. New session - no currency set yet
            if (!$currentCurrency) {
                $shouldUpdateCurrency = true;
                $updateReason = 'new_session';
            }
            // 2. IP address changed (user moved to different location)
            elseif ($currentIP !== $lastDetectedIP) {
                $shouldUpdateCurrency = true;
                $updateReason = 'ip_changed';
            }
            // 3. Country changed (user traveled to different country)
            elseif (session('last_detected_country', '') !== $detectedCountry) {
                $shouldUpdateCurrency = true;
                $updateReason = 'country_changed';
            }
            // 4. Manual change was more than 30 minutes ago (allow automatic updates)
            elseif (($currentTime - $lastManualChangeTime) > 1800) {
                $shouldUpdateCurrency = true;
                $updateReason = 'manual_change_expired';
            }

            // Update currency if conditions are met and detected currency is different
            if ($shouldUpdateCurrency && $currentCurrency !== $detectedCurrency) {
                session(['currency' => $detectedCurrency]);
                session(['currency_detection_time' => $currentTime]);
                session(['last_detected_ip' => $currentIP]);
                session(['last_detected_country' => $detectedCountry]);

                \Log::info('Currency updated based on location:', [
                    'reason' => $updateReason,
                    'new_currency' => $detectedCurrency,
                    'previous_currency' => $currentCurrency ?? 'none',
                    'country' => $detectedCountry,
                    'ip' => $currentIP,
                    'previous_ip' => $lastDetectedIP
                ]);
            } else {
                // Update tracking data even if currency didn't change
                session(['last_detected_ip' => $currentIP]);
                session(['last_detected_country' => $detectedCountry]);

                \Log::info('Using existing currency:', [
                    'currency' => $currentCurrency,
                    'detected_currency' => $detectedCurrency,
                    'country' => $detectedCountry,
                    'ip' => $currentIP,
                    'manual_change_minutes_ago' => ($currentTime - $lastManualChangeTime) / 60
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
