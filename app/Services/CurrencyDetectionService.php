<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Drivers\IpInfo;
use Stevebauman\Location\LocationRequest;
use Stevebauman\Location\Position;
use Exception;

class CurrencyDetectionService
{
    private array $countryCurrencyMap;

    public function __construct()
    {
        $this->countryCurrencyMap = [
            'US' => 'USD', 'CA' => 'CAD', 'GB' => 'GBP', 'DE' => 'EUR', 'FR' => 'EUR',
            'IT' => 'EUR', 'ES' => 'EUR', 'NL' => 'EUR', 'BE' => 'EUR', 'AT' => 'EUR',
            'JP' => 'JPY', 'AU' => 'AUD', 'NZ' => 'NZD', 'CH' => 'CHF', 'SE' => 'SEK',
            'NO' => 'NOK', 'DK' => 'DKK', 'SG' => 'SGD', 'HK' => 'HKD', 'IN' => 'INR',
            'CN' => 'CNY', 'KR' => 'KRW', 'MX' => 'MXN', 'BR' => 'BRL', 'RU' => 'RUB',
            'ZA' => 'ZAR', 'AE' => 'AED', 'SA' => 'SAR', 'IL' => 'ILS', 'TH' => 'THB',
            'MY' => 'MYR', 'PH' => 'PHP', 'ID' => 'IDR', 'VN' => 'VND', 'EG' => 'EGP',
            'QA' => 'QAR', 'KW' => 'KWD', 'BH' => 'BHD', 'OM' => 'OMR', 'JO' => 'JOD',
            'PL' => 'PLN', 'CZ' => 'CZK', 'HU' => 'HUF', 'RO' => 'RON', 'BG' => 'BGN',
            'TR' => 'TRY', 'UA' => 'UAH',
        ];
    }

    public function detectCurrencyByIp(?string $ipAddress = null): array
    {
        try {
            $ip = $ipAddress ?? request()->ip();

            $cacheKey = "currency_detection_{$ip}";
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            $resolvedLocation = $this->resolveLocation($ip);
            $location = $resolvedLocation['location'];

            if ($location && $location->countryCode) {
                $countryCode = strtoupper($location->countryCode);
                $currency = $this->countryCurrencyMap[$countryCode] ?? 'USD';

                $result = [
                    'success' => true,
                    'currency' => $currency,
                    'country_code' => $countryCode,
                    'country' => $location->countryName ?? null,
                    'city' => $location->cityName ?? null,
                    'detection_method' => $resolvedLocation['method'],
                    'confidence' => 'high',
                ];

                Cache::put($cacheKey, $result, 3600);
                return $result;
            }

            return $this->buildFallbackResponse('Location detection returned no data');

        } catch (Exception $e) {
            Log::warning('Currency detection failed', [
                'error' => $e->getMessage(),
                'ip' => $ipAddress ?? 'unknown'
            ]);
            return $this->buildFallbackResponse('Detection failed: ' . $e->getMessage());
        }
    }

    public function detectCurrencyFromLocale(string $locale): array
    {
        try {
            $localeParts = explode('-', $locale);
            $countryCode = strtoupper(end($localeParts));
            $currency = $this->countryCurrencyMap[$countryCode] ?? null;

            if ($currency) {
                return [
                    'success' => true,
                    'currency' => $currency,
                    'country_code' => $countryCode,
                    'detection_method' => 'locale',
                    'confidence' => 'medium'
                ];
            }

            return $this->buildFallbackResponse('Unknown locale country');
        } catch (Exception $e) {
            return $this->buildFallbackResponse('Locale detection failed');
        }
    }

    public function getSupportedCurrencies(): array
    {
        return Cache::remember('supported_currencies', 86400, function () {
            return [
                'USD' => ['symbol' => '$', 'name' => 'US Dollar', 'code' => 'USD'],
                'EUR' => ['symbol' => '€', 'name' => 'Euro', 'code' => 'EUR'],
                'GBP' => ['symbol' => '£', 'name' => 'British Pound', 'code' => 'GBP'],
                'JPY' => ['symbol' => '¥', 'name' => 'Japanese Yen', 'code' => 'JPY'],
                'AUD' => ['symbol' => 'A$', 'name' => 'Australian Dollar', 'code' => 'AUD'],
                'CAD' => ['symbol' => 'C$', 'name' => 'Canadian Dollar', 'code' => 'CAD'],
                'CHF' => ['symbol' => 'Fr', 'name' => 'Swiss Franc', 'code' => 'CHF'],
                'HKD' => ['symbol' => 'HK$', 'name' => 'Hong Kong Dollar', 'code' => 'HKD'],
                'SGD' => ['symbol' => 'S$', 'name' => 'Singapore Dollar', 'code' => 'SGD'],
                'SEK' => ['symbol' => 'kr', 'name' => 'Swedish Krona', 'code' => 'SEK'],
                'KRW' => ['symbol' => '₩', 'name' => 'South Korean Won', 'code' => 'KRW'],
                'NOK' => ['symbol' => 'kr', 'name' => 'Norwegian Krone', 'code' => 'NOK'],
                'NZD' => ['symbol' => 'NZ$', 'name' => 'New Zealand Dollar', 'code' => 'NZD'],
                'INR' => ['symbol' => '₹', 'name' => 'Indian Rupee', 'code' => 'INR'],
                'MXN' => ['symbol' => 'Mex$', 'name' => 'Mexican Peso', 'code' => 'MXN'],
                'BRL' => ['symbol' => 'R$', 'name' => 'Brazilian Real', 'code' => 'BRL'],
                'RUB' => ['symbol' => '₽', 'name' => 'Russian Ruble', 'code' => 'RUB'],
                'ZAR' => ['symbol' => 'R', 'name' => 'South African Rand', 'code' => 'ZAR'],
                'AED' => ['symbol' => 'AED', 'name' => 'UAE Dirham', 'code' => 'AED'],
            ];
        });
    }

    public function clearDetectionCache(?string $ipAddress = null): void
    {
        $ip = $ipAddress ?? request()->ip();
        if ($ip) {
            Cache::forget("currency_detection_{$ip}");
        }
    }

    private function resolveLocation(string $ip): array
    {
        $location = Location::get($ip);

        if ($location && $location->countryCode) {
            return [
                'location' => $location,
                'method' => 'maxmind_or_configured_fallback',
            ];
        }

        $location = $this->resolveLocationWithIpInfo($ip);

        if ($location && $location->countryCode) {
            return [
                'location' => $location,
                'method' => 'ipinfo',
            ];
        }

        return [
            'location' => false,
            'method' => 'fallback',
        ];
    }

    /**
     * @return Position|bool
     */
    private function resolveLocationWithIpInfo(string $ip)
    {
        if (!config('location.ipinfo.token')) {
            return false;
        }

        try {
            $request = LocationRequest::createFrom(request())->setIp($ip);

            return app(IpInfo::class)->get($request);
        } catch (Exception $e) {
            Log::warning('IpInfo detection failed', [
                'error' => $e->getMessage(),
                'ip' => $ip,
            ]);

            return false;
        }
    }

    private function buildFallbackResponse(string $reason): array
    {
        return [
            'success' => false,
            'currency' => config('currency.default', 'USD'),
            'country_code' => null,
            'detection_method' => 'fallback',
            'confidence' => 'low',
            'error' => $reason,
        ];
    }
}
