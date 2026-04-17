<?php

namespace App\Http\Middleware;

use App\Services\CurrencyDetectionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrency
{
    private CurrencyDetectionService $currencyDetectionService;

    public function __construct(CurrencyDetectionService $currencyDetectionService)
    {
        $this->currencyDetectionService = $currencyDetectionService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $currentCurrency = session('currency');
            $lastManualChangeTime = session('currency_manual_change_time', 0);
            $currentTime = time();
            $currentIP = $this->resolveRealIp($request);
            $lastDetectedIP = session('last_detected_ip', '');

            $detection = $this->currencyDetectionService->detectCurrencyByIp($currentIP);
            $detectedCurrency = $detection['currency'] ?? ($currentCurrency ?: 'USD');
            $detectedCountry = $detection['success'] ? strtoupper((string) ($detection['country_code'] ?? '')) : '';

            $shouldUpdateCurrency = false;

            if (!$currentCurrency) {
                $shouldUpdateCurrency = true;
            } elseif ($currentIP !== $lastDetectedIP) {
                $shouldUpdateCurrency = true;
            } elseif (session('last_detected_country', '') !== $detectedCountry) {
                $shouldUpdateCurrency = true;
            } elseif (($currentTime - $lastManualChangeTime) > 1800) {
                $shouldUpdateCurrency = true;
            }

            if ($detectedCountry !== '') {
                session(['country' => strtolower($detectedCountry)]);
            }

            if ($shouldUpdateCurrency && $currentCurrency !== $detectedCurrency) {
                session(['currency' => $detectedCurrency]);
                session(['currency_detection_time' => $currentTime]);
            }

            session(['last_detected_ip' => $currentIP]);
            session(['last_detected_country' => $detectedCountry]);

        } catch (\Exception $e) {
            if (!session('currency')) {
                session(['currency' => 'USD']);
                session(['currency_detection_time' => time()]);
            }
            if (!session('country')) {
                session(['country' => 'us']);
            }
        }

        return $next($request);
    }

    private function resolveRealIp(Request $request): string
    {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        ];

        foreach ($ipKeys as $key) {
            $rawIp = $request->server($key);

            if (!$rawIp) {
                continue;
            }

            foreach (explode(',', $rawIp) as $candidateIp) {
                $candidateIp = trim($candidateIp);

                if (filter_var($candidateIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $candidateIp;
                }
            }
        }

        return (string) $request->ip();
    }

    private function currencyForCountry(string $countryCode): string
    {
        return match ($countryCode) {
            // North America
            'US' => 'USD', 'CA' => 'CAD', 'MX' => 'MXN',
            'NI' => 'NIO', 'CR' => 'CRC', 'PA' => 'PAB',
            'GT' => 'GTQ', 'HN' => 'HNL', 'SV' => 'SVC', 'BZ' => 'BZD',
            'JM' => 'JMD', 'BB' => 'BBD', 'TT' => 'TTD', 'DO' => 'DOP',
            'HT' => 'HTG',
            'AG', 'DM', 'GD', 'KN', 'LC', 'VC', 'AI', 'MS' => 'XCD',

            // Europe - Eurozone
            'DE', 'FR', 'IT', 'ES', 'NL', 'BE', 'AT', 'PT', 'FI', 'IE',
            'GR', 'CY', 'MT', 'SK', 'SI', 'EE', 'LV', 'LT', 'LU', 'HR',
            'RE', 'YT', 'GP', 'MQ', 'GF' => 'EUR',

            // Europe - Non-Eurozone
            'GB' => 'GBP', 'CH' => 'CHF', 'SE' => 'SEK', 'NO' => 'NOK',
            'DK', 'FO', 'GL' => 'DKK', 'IS' => 'ISK',
            'PL' => 'PLN', 'CZ' => 'CZK', 'HU' => 'HUF',
            'RO' => 'RON', 'BG' => 'BGN',

            // Asia Pacific
            'CN' => 'CNY', 'JP' => 'JPY', 'IN' => 'INR', 'KR' => 'KRW',
            'ID' => 'IDR', 'TH' => 'THB', 'MY' => 'MYR', 'PH' => 'PHP',
            'VN' => 'VND', 'SG' => 'SGD', 'HK' => 'HKD', 'TW' => 'TWD',
            'BD' => 'BDT', 'PK' => 'PKR', 'LK' => 'LKR', 'MM' => 'MMK',
            'KH' => 'KHR', 'LA' => 'LAK', 'NP' => 'NPR', 'BT' => 'BTN',
            'BN' => 'BND', 'MV' => 'MVR',

            // Oceania
            'AU', 'TV', 'NR' => 'AUD', 'NZ' => 'NZD', 'FJ' => 'FJD',
            'PG' => 'PGK', 'SB' => 'SBD', 'VU' => 'VUV', 'WS' => 'WST',
            'TO' => 'TOP',
            'PW', 'FM', 'MH' => 'USD',

            // Middle East & North Africa
            'SA' => 'SAR', 'AE' => 'AED', 'IL' => 'ILS', 'QA' => 'QAR',
            'KW' => 'KWD', 'BH' => 'BHD', 'OM' => 'OMR', 'JO' => 'JOD',
            'LB' => 'LBP', 'SY' => 'SYP', 'IQ' => 'IQD', 'IR' => 'IRR',
            'AF' => 'AFN', 'EG' => 'EGP', 'LY' => 'LYD', 'TN' => 'TND',
            'DZ' => 'DZD', 'MA' => 'MAD', 'SD' => 'SDG', 'YE' => 'YER',

            // Sub-Saharan Africa
            'ZA' => 'ZAR', 'NG' => 'NGN', 'KE' => 'KES', 'GH' => 'GHS',
            'UG' => 'UGX', 'TZ' => 'TZS', 'ZM' => 'ZMW', 'ZW' => 'ZWL',
            'MW' => 'MWK', 'MZ' => 'MZN', 'AO' => 'AOA', 'BW' => 'BWP',
            'NA' => 'NAD', 'SZ' => 'SZL', 'LS' => 'LSL', 'LR' => 'LRD',
            'SL' => 'SLL', 'GN' => 'GNF',
            'CI', 'BF', 'ML', 'NE', 'SN', 'TG', 'BJ' => 'XOF',
            'CM', 'CF', 'TD', 'CG', 'GA', 'GQ' => 'XAF',
            'CD' => 'CDF', 'RW' => 'RWF', 'BI' => 'BIF', 'DJ' => 'DJF',
            'ER' => 'ERN', 'ET' => 'ETB', 'SO' => 'SOS', 'GM' => 'GMD',
            'CV' => 'CVE', 'ST' => 'STN', 'SH' => 'SHP', 'SC' => 'SCR',
            'MU' => 'MUR', 'MG' => 'MGA', 'KM' => 'KMF',

            // South America
            'BR' => 'BRL', 'AR' => 'ARS', 'CL' => 'CLP', 'CO' => 'COP',
            'PE' => 'PEN', 'VE' => 'VES', 'UY' => 'UYU', 'PY' => 'PYG',
            'BO' => 'BOB', 'GY' => 'GYD', 'SR' => 'SRD',
            'EC', 'PR', 'VI', 'VG', 'TC' => 'USD',

            // Caribbean
            'CU' => 'CUP', 'BS' => 'BSD', 'KY' => 'KYD', 'BM' => 'BMD',
            'AN', 'SX' => 'ANG', 'AW' => 'AWG', 'CW' => 'CWG',

            // Former Soviet Union
            'RU' => 'RUB', 'UA' => 'UAH', 'BY' => 'BYN', 'MD' => 'MDL',
            'GE' => 'GEL', 'AM' => 'AMD', 'AZ' => 'AZN', 'KZ' => 'KZT',
            'KG' => 'KGS', 'UZ' => 'UZS', 'TJ' => 'TJS', 'TM' => 'TMT',

            // Others
            'TR' => 'TRY',

            default => 'USD',
        };
    }
}
