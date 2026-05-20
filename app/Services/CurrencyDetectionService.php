<?php

namespace App\Services;

use App\Support\CurrencyRegistry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Drivers\IpInfo;
use Stevebauman\Location\LocationRequest;
use Stevebauman\Location\Position;
use Exception;

class CurrencyDetectionService
{
    public function __construct(private CurrencyRegistry $currencies)
    {
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
                $currency = $this->currencies->currencyForCountry($countryCode);

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
            $currency = $this->currencies->currencyForCountry($countryCode);

            if ($this->currencies->isKnown($currency)) {
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
            return collect($this->currencies->publicPayload())
                ->mapWithKeys(fn (array $currency) => [$currency['code'] => $currency])
                ->all();
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
