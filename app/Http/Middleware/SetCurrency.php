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
            $realIP = $this->getRealIP($request);
            $location = Location::get($realIP);
            if (!$location) {
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
                    'CR' => 'CRC',
                    'PA' => 'PAB',
                    'GT' => 'GTQ',
                    'HN' => 'HNL',
                    'SV' => 'SVC',
                    'BZ' => 'BZD',
                    'JM' => 'JMD',
                    'BB' => 'BBD',
                    'TT' => 'TTD',
                    'DO' => 'DOP',
                    'HT' => 'HTG',
                    'AG' => 'XCD',
                    'DM' => 'XCD',
                    'GD' => 'XCD',
                    'KN' => 'XCD',
                    'LC' => 'XCD',
                    'VC' => 'XCD',

                    // Europe - Eurozone (All 20 countries)
                    'DE', 'FR', 'IT', 'ES', 'NL', 'BE', 'AT', 'PT', 'FI', 'IE',
                    'GR', 'CY', 'MT', 'SK', 'SI', 'EE', 'LV', 'LT', 'LU', 'HR' => 'EUR',

                    // Europe - Non-Eurozone
                    'GB' => 'GBP',
                    'CH' => 'CHF',
                    'SE' => 'SEK',
                    'NO' => 'NOK',
                    'DK' => 'DKK',
                    'IS' => 'ISK',
                    'PL' => 'PLN',
                    'CZ' => 'CZK',
                    'HU' => 'HUF',
                    'RO' => 'RON',
                    'BG' => 'BGN',

                    // Asia Pacific - Major Economies
                    'CN' => 'CNY',
                    'JP' => 'JPY',
                    'IN' => 'INR',
                    'KR' => 'KRW',
                    'ID' => 'IDR',
                    'TH' => 'THB',
                    'MY' => 'MYR',
                    'PH' => 'PHP',
                    'VN' => 'VND',
                    'SG' => 'SGD',
                    'HK' => 'HKD',
                    'TW' => 'TWD',
                    'BD' => 'BDT',
                    'PK' => 'PKR',
                    'LK' => 'LKR',
                    'MM' => 'MMK',
                    'KH' => 'KHR',
                    'LA' => 'LAK',
                    'NP' => 'NPR',
                    'BT' => 'BTN',

                    // Oceania
                    'AU' => 'AUD',
                    'NZ' => 'NZD',
                    'FJ' => 'FJD',
                    'PG' => 'PGK',
                    'SB' => 'SBD',
                    'VU' => 'VUV',
                    'WS' => 'WST',
                    'TO' => 'TOP',
                    'KI' => 'KID',
                    'TV' => 'AUD',
                    'NR' => 'AUD',
                    'PW' => 'USD',
                    'FM' => 'USD',
                    'MH' => 'USD',

                    // Middle East & North Africa
                    'SA' => 'SAR',
                    'AE' => 'AED',
                    'IL' => 'ILS',
                    'QA' => 'QAR',
                    'KW' => 'KWD',
                    'BH' => 'BHD',
                    'OM' => 'OMR',
                    'JO' => 'JOD',
                    'LB' => 'LBP',
                    'SY' => 'SYP',
                    'IQ' => 'IQD',
                    'IR' => 'IRR',
                    'AF' => 'AFN',
                    'EG' => 'EGP',
                    'LY' => 'LYD',
                    'TN' => 'TND',
                    'DZ' => 'DZD',
                    'MA' => 'MAD',
                    'SD' => 'SDG',
                    'YE' => 'YER',

                    // Sub-Saharan Africa
                    'ZA' => 'ZAR',
                    'NG' => 'NGN',
                    'KE' => 'KES',
                    'GH' => 'GHS',
                    'UG' => 'UGX',
                    'TZ' => 'TZS',
                    'ZM' => 'ZMW',
                    'ZW' => 'ZWL',
                    'MW' => 'MWK',
                    'MZ' => 'MZN',
                    'AO' => 'AOA',
                    'BW' => 'BWP',
                    'NA' => 'NAD',
                    'SZ' => 'SZL',
                    'LS' => 'LSL',
                    'LR' => 'LRD',
                    'SL' => 'SLL',
                    'GN' => 'GNF',
                    'CI' => 'XOF',
                    'BF' => 'XOF',
                    'ML' => 'XOF',
                    'NE' => 'XOF',
                    'SN' => 'XOF',
                    'TG' => 'XOF',
                    'BJ' => 'XOF',
                    'CM' => 'XAF',
                    'CF' => 'XAF',
                    'TD' => 'XAF',
                    'CG' => 'XAF',
                    'GA' => 'XAF',
                    'GQ' => 'XAF',
                    'CD' => 'CDF',
                    'RW' => 'RWF',
                    'BI' => 'BIF',
                    'DJ' => 'DJF',
                    'ER' => 'ERN',
                    'ET' => 'ETB',
                    'SO' => 'SOS',
                    'GM' => 'GMD',
                    'CV' => 'CVE',
                    'ST' => 'STN',
                    'SH' => 'SHP',
                    'SC' => 'SCR',
                    'MU' => 'MUR',
                    'MG' => 'MGA',
                    'KM' => 'KMF',
                    'RE' => 'EUR',
                    'YT' => 'EUR',

                    // South America
                    'BR' => 'BRL',
                    'AR' => 'ARS',
                    'CL' => 'CLP',
                    'CO' => 'COP',
                    'PE' => 'PEN',
                    'VE' => 'VES',
                    'UY' => 'UYU',
                    'PY' => 'PYG',
                    'BO' => 'BOB',
                    'EC' => 'USD',
                    'GY' => 'GYD',
                    'SR' => 'SRD',
                    'GF' => 'EUR',

                    // Central America & Caribbean
                    'CU' => 'CUP',
                    'PR' => 'USD',
                    'VI' => 'USD',
                    'GP' => 'EUR',
                    'MQ' => 'EUR',
                    'BB' => 'BBD',
                    'BS' => 'BSD',
                    'KY' => 'KYD',
                    'BM' => 'BMD',
                    'AI' => 'XCD',
                    'VG' => 'USD',
                    'MS' => 'XCD',
                    'TC' => 'USD',
                    'DM' => 'XCD',
                    'GD' => 'XCD',
                    'KN' => 'XCD',
                    'LC' => 'XCD',
                    'VC' => 'XCD',
                    'AN' => 'ANG',
                    'AW' => 'AWG',
                    'CW' => 'CWG',
                    'SX' => 'ANG',

                    // Former Soviet Union
                    'RU' => 'RUB',
                    'UA' => 'UAH',
                    'BY' => 'BYN',
                    'MD' => 'MDL',
                    'GE' => 'GEL',
                    'AM' => 'AMD',
                    'AZ' => 'AZN',
                    'KZ' => 'KZT',
                    'KG' => 'KGS',
                    'UZ' => 'UZS',
                    'TJ' => 'TJS',
                    'TM' => 'TMT',

                    // Southeast Asia continued
                    'BN' => 'BND',
                    'KH' => 'KHR',
                    'LA' => 'LAK',
                    'MM' => 'MMK',
                    'NP' => 'NPR',
                    'BT' => 'BTN',
                    'MV' => 'MVR',

                    // Others
                    'TR' => 'TRY',
                    'CY' => 'EUR',
                    'MT' => 'EUR',
                    'IS' => 'ISK',
                    'FO' => 'DKK',
                    'GL' => 'DKK',

                    // Default to USD for any unspecified countries
                    default => 'USD',
                };

                // Location detected silently
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

            // IMPORTANT: Always set country in session for blog filtering
            session(['country' => strtolower($detectedCountry)]);

            // Update currency if conditions are met and detected currency is different
            if ($shouldUpdateCurrency && $currentCurrency !== $detectedCurrency) {
                session(['currency' => $detectedCurrency]);
                session(['currency_detection_time' => $currentTime]);
                session(['last_detected_ip' => $currentIP]);
                session(['last_detected_country' => $detectedCountry]);

                // Currency and country updated based on location
            } else {
                // Update tracking data even if currency didn't change
                session(['last_detected_ip' => $currentIP]);
                session(['last_detected_country' => $detectedCountry]);

              // Using existing data
            }

        } catch (\Exception $e) {

            // Fallback to USD and US if no currency/country is set
            if (!session('currency')) {
                session(['currency' => 'USD']);
                session(['currency_detection_time' => time()]);
            }
            if (!session('country')) {
                session(['country' => 'us']);
                // Set fallback country to US
            }
        }

        return $next($request);
    }

    /**
     * Get the real visitor IP address from proxy headers
     * This is essential for Coolify deployments behind reverse proxies
     */
    private function getRealIP(Request $request): string
    {
        // Check various proxy headers for the real IP
        $ipHeaders = [
            'CF-Connecting-IP',        // Cloudflare
            'X-Forwarded-For',         // Standard proxy header
            'X-Real-IP',               // nginx
            'HTTP_X_FORWARDED_FOR',    // Alternative header format
            'HTTP_CLIENT_IP',          // Client IP header
            'HTTP_X_REAL_IP',          // Alternative real IP header
        ];

        foreach ($ipHeaders as $header) {
            $ip = $request->header($header);
            if ($ip) {
                // X-Forwarded-For can contain multiple IPs, get the first one
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }

                // Validate that it's a public IP address
                if ($this->isValidPublicIP($ip)) {
                  // Real IP detected from {$header}: {$ip}
                    return $ip;
                }
            }
        }

        // Fallback to default IP detection
        $defaultIP = $request->ip();
        // Using default IP (no proxy headers found): {$defaultIP}
        return $defaultIP;
    }

    /**
     * Check if the IP address is a valid public IP
     */
    private function isValidPublicIP(string $ip): bool
    {
        // Basic IP validation
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
            return false;
        }

        // Exclude private IP ranges
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return true;
        }

        return false;
    }

    /**
     * Fallback method for location detection when primary service fails
     */
    private function getLocationFallback(Request $request)
    {
        // Get the real IP address first
        $realIP = $this->getRealIP($request);

        // Known IP mappings for testing (works on both local and live)
        $knownIps = [
            '49.43.142.103' => ['countryCode' => 'IN', 'countryName' => 'India', 'cityName' => 'Shimla'],
            '8.8.8.8' => ['countryCode' => 'US', 'countryName' => 'United States', 'cityName' => 'Mountain View'],
            '8.8.4.4' => ['countryCode' => 'GB', 'countryName' => 'United Kingdom', 'cityName' => 'London'],
            '8.8.8.1' => ['countryCode' => 'JP', 'countryName' => 'Japan', 'cityName' => 'Tokyo'],
            '8.8.8.2' => ['countryCode' => 'DE', 'countryName' => 'Germany', 'cityName' => 'Frankfurt'],
            '8.8.8.3' => ['countryCode' => 'AU', 'countryName' => 'Australia', 'cityName' => 'Sydney'],
        ];

        // Check if current IP matches any known test IPs
        if (isset($knownIps[$realIP])) {
            // Using known test IP mapping
            return (object) $knownIps[$realIP];
        }

        // For localhost, try to get real external IP using IPInfo API
        if ($realIP === '127.0.0.1' || $realIP === '::1') {
            try {
                // Use your IPInfo API key to get real external IP
                $apiKey = env('IPINFO_TOKEN');
                if ($apiKey) {
                    $externalIp = file_get_contents("https://ipinfo.io/json?token={$apiKey}");
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
                }
            } catch (\Exception $e) {
            }

            // Using default fallback for localhost
            return (object) $knownIps['49.43.142.103']; // India for testing
        }

        // For live server: try alternative IP detection methods
        try {
            // Method 1: Try IPInfo without token (limited but works)
            $ipData = @file_get_contents("https://ipinfo.io/{$realIP}/json");
            if ($ipData) {
                $data = json_decode($ipData, true);
                if ($data && isset($data['country'])) {
                    // Location detected via IPInfo fallback
                    return (object) [
                        'ip' => $realIP,
                        'countryCode' => $data['country'],
                        'countryName' => $data['country'] ?? 'Unknown',
                        'cityName' => $data['city'] ?? 'Unknown',
                    ];
                }
            }

            // Method 2: Try ip-api.com (free, no API key required)
            $ipData = @file_get_contents("http://ip-api.com/json/{$realIP}");
            if ($ipData) {
                $data = json_decode($ipData, true);
                if ($data && $data['status'] === 'success' && isset($data['countryCode'])) {
                    // Location detected via ip-api.com fallback
                    return (object) [
                        'ip' => $realIP,
                        'countryCode' => $data['countryCode'],
                        'countryName' => $data['country'] ?? 'Unknown',
                        'cityName' => $data['city'] ?? 'Unknown',
                    ];
                }
            }
        } catch (\Exception $e) {
        }

        // All location detection methods failed, using default US
        return null;
    }
}
