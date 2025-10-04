<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class CurrencyDetectionService
{
    private array $ipGeolocationProviders;
    private int $requestTimeout;
    private array $countryCurrencyMap;
    private array $fallbackCurrencies;

    public function __construct()
    {
        $this->ipGeolocationProviders = [
            'primary' => [
                'url' => 'https://ipapi.co/{ip}/json/',
                'timeout' => 5,
                'rate_limit' => 1000 // requests per hour
            ],
            'secondary' => [
                'url' => 'https://ipinfo.io/{ip}/json',
                'timeout' => 5,
                'rate_limit' => 1000
            ],
            'tertiary' => [
                'url' => 'https://api.ipify.org?format=json',
                'timeout' => 3,
                'rate_limit' => 1000
            ]
        ];

        $this->requestTimeout = config('currency.request_timeout', 5);

        // Country to currency mapping
        $this->countryCurrencyMap = [
            'US' => 'USD', 'CA' => 'CAD', 'GB' => 'GBP', 'DE' => 'EUR', 'FR' => 'EUR',
            'IT' => 'EUR', 'ES' => 'EUR', 'NL' => 'EUR', 'BE' => 'EUR', 'AT' => 'EUR',
            'JP' => 'JPY', 'AU' => 'AUD', 'NZ' => 'NZD', 'CH' => 'CHF', 'SE' => 'SEK',
            'NO' => 'NOK', 'DK' => 'DKK', 'SG' => 'SGD', 'HK' => 'HKD', 'IN' => 'INR',
            'CN' => 'CNY', 'KR' => 'KRW', 'MX' => 'MXN', 'BR' => 'BRL', 'RU' => 'RUB',
            'ZA' => 'ZAR', 'AE' => 'AED', 'SA' => 'SAR', 'IL' => 'ILS', 'TH' => 'THB',
            'MY' => 'MYR', 'PH' => 'PHP', 'ID' => 'IDR', 'VN' => 'VND', 'EG' => 'EGP'
        ];

        $this->fallbackCurrencies = config('currency.fallback_currencies', ['USD', 'EUR', 'GBP']);
    }

    /**
     * Detect currency based on user's IP address
     */
    public function detectCurrencyByIp(?string $ipAddress = null): array
    {
        try {
            $ip = $ipAddress ?? $this->getClientIp();

            if (!$this->isValidIp($ip)) {
                return $this->buildFallbackResponse('Invalid IP address');
            }

            // Check cache first
            $cacheKey = "currency_detection_{$ip}";
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            // Try each geolocation provider
            $result = $this->tryGeolocationProviders($ip);

            // Cache the result for 1 hour
            Cache::put($cacheKey, $result, 3600);

            return $result;

        } catch (Exception $e) {
            Log::warning('Currency detection failed', [
                'error' => $e->getMessage(),
                'ip' => $ipAddress ?? 'unknown'
            ]);

            return $this->buildFallbackResponse('Geolocation service unavailable');
        }
    }

    /**
     * Detect currency from browser locale
     */
    public function detectCurrencyFromLocale(string $locale): array
    {
        try {
            $localeParts = explode('-', $locale);
            $countryCode = strtoupper(end($localeParts));

            $currency = $this->getCurrencyByCountry($countryCode);

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
            Log::warning('Locale currency detection failed', [
                'error' => $e->getMessage(),
                'locale' => $locale
            ]);

            return $this->buildFallbackResponse('Locale detection failed');
        }
    }

    /**
     * Get supported currencies with metadata
     */
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
                'AED' => ['symbol' => 'AED', 'name' => 'UAE Dirham', 'code' => 'AED']
            ];
        });
    }

    /**
     * Try geolocation providers in order of preference
     */
    private function tryGeolocationProviders(string $ip): array
    {
        $providers = ['primary', 'secondary', 'tertiary'];

        foreach ($providers as $provider) {
            try {
                $result = $this->callGeolocationProvider($ip, $provider);
                if ($result['success']) {
                    return $result;
                }
            } catch (Exception $e) {
                Log::info("Geolocation provider {$provider} failed", [
                    'error' => $e->getMessage(),
                    'ip' => $ip
                ]);
                continue;
            }
        }

        return $this->buildFallbackResponse('All geolocation providers failed');
    }

    /**
     * Call specific geolocation provider
     */
    private function callGeolocationProvider(string $ip, string $provider): array
    {
        $config = $this->ipGeolocationProviders[$provider];
        $url = str_replace('{ip}', $ip, $config['url']);

        $response = Http::timeout($config['timeout'])
            ->withHeaders([
                'User-Agent' => 'CarRental/1.0 Currency Detection',
                'Accept' => 'application/json'
            ])
            ->get($url);

        if (!$response->successful()) {
            throw new Exception("HTTP {$response->status()}: {$response->reason()}");
        }

        $data = $response->json();

        if (!$data || !is_array($data)) {
            throw new Exception('Invalid response format');
        }

        return $this->parseGeolocationResponse($data, $provider);
    }

    /**
     * Parse geolocation response based on provider
     */
    private function parseGeolocationResponse(array $data, string $provider): array
    {
        $countryCode = null;

        switch ($provider) {
            case 'primary': // ipapi.co
                $countryCode = $data['country_code'] ?? null;
                break;
            case 'secondary': // ipinfo.io
                $countryCode = $data['country'] ?? null;
                break;
            case 'tertiary': // ipify (only provides IP, use fallback)
                return $this->buildFallbackResponse('IP-only provider, using fallback');
        }

        if (!$countryCode || strlen($countryCode) !== 2) {
            throw new Exception('Invalid country code in response');
        }

        $currency = $this->getCurrencyByCountry($countryCode);

        if (!$currency) {
            return $this->buildFallbackResponse('Country not supported');
        }

        return [
            'success' => true,
            'currency' => $currency,
            'country_code' => $countryCode,
            'detection_method' => "ip_{$provider}",
            'confidence' => 'high',
            'raw_response' => $data
        ];
    }

    /**
     * Get currency by country code
     */
    private function getCurrencyByCountry(string $countryCode): ?string
    {
        return $this->countryCurrencyMap[strtoupper($countryCode)] ?? null;
    }

    /**
     * Get client IP address
     */
    private function getClientIp(): ?string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);

                if ($this->isValidIp($ip)) {
                    return $ip;
                }
            }
        }

        return null;
    }

    /**
     * Validate IP address
     */
    private function isValidIp(?string $ip): bool
    {
        if (!$ip) {
            return false;
        }

        // Filter out private and reserved IPs
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Build fallback response
     */
    private function buildFallbackResponse(string $reason): array
    {
        return [
            'success' => false,
            'currency' => config('currency.default', 'USD'),
            'country_code' => null,
            'detection_method' => 'fallback',
            'confidence' => 'low',
            'error' => $reason,
            'fallback_reason' => $reason
        ];
    }

    /**
     * Clear detection cache for specific IP
     */
    public function clearDetectionCache(?string $ipAddress = null): void
    {
        $ip = $ipAddress ?? $this->getClientIp();
        if ($ip) {
            Cache::forget("currency_detection_{$ip}");
        }
    }

    /**
     * Get detection statistics
     */
    public function getDetectionStats(): array
    {
        return [
            'providers_configured' => count($this->ipGeolocationProviders),
            'countries_supported' => count($this->countryCurrencyMap),
            'fallback_currencies' => $this->fallbackCurrencies,
            'cache_ttl' => 3600,
            'request_timeout' => $this->requestTimeout
        ];
    }
}