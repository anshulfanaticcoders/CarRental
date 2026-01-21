<?php

namespace App\Services;

use App\Models\CurrencyRate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Exception;
use Carbon\Carbon;

class CurrencyConversionService
{
    private array $exchangeRateProviders;
    private int $requestTimeout;
    private int $maxRetries;
    private int $retryDelay;
    private int $cacheTtl;
    private array $circuitBreakerState;
    private string $defaultBaseCurrency;

    public function __construct()
    {
        $exchangeRateKey = env('EXCHANGERATE_API_KEY', env('VITE_EXCHANGERATE_API_KEY'));
        $exchangeRateBaseUrl = env('EXCHANGERATE_API_BASE_URL', env('VITE_EXCHANGERATE_API_BASE_URL', 'https://v6.exchangerate-api.com'));

        // Optimized ExchangeRate-API configuration
        $this->exchangeRateProviders = [];
        if (!empty($exchangeRateKey)) {
            $this->exchangeRateProviders['primary'] = [
                'name' => 'ExchangeRate-API',
                'url' => $exchangeRateBaseUrl . '/v6/' . $exchangeRateKey . '/latest/{base}',
                'timeout' => 5,
                'rate_limit' => 2000, // requests per hour
                'requires_key' => false // Key is embedded in URL
            ];
        }

        $this->requestTimeout = config('currency.request_timeout', 5);
        $this->maxRetries = config('currency.max_retries', 3);
        $this->retryDelay = config('currency.retry_delay', 1000); // milliseconds
        $this->cacheTtl = config('currency.cache_ttl', 3600); // 1 hour
        $this->defaultBaseCurrency = config('currency.base_currency', 'USD');

        // Initialize circuit breaker state
        $this->circuitBreakerState = [];
        foreach (array_keys($this->exchangeRateProviders) as $provider) {
            $this->circuitBreakerState[$provider] = [
                'failures' => 0,
                'last_failure' => null,
                'state' => 'closed'
            ];
        }
    }

    /**
     * Get all exchange rates for a base currency from ExchangeRate-API (Optimized)
     * This fetches all rates in ONE request and caches them locally
     */
    public function getAllExchangeRates(string $baseCurrency = 'USD'): array
    {
        try {
            $baseCurrency = strtoupper(trim($baseCurrency));

            $dbRates = $this->getRatesFromDatabase($baseCurrency);
            if ($dbRates) {
                return [
                    'success' => true,
                    'rates' => $dbRates['rates'],
                    'timestamp' => $dbRates['timestamp'],
                    'base_currency' => $baseCurrency,
                    'provider' => 'database',
                    'cache_hit' => true
                ];
            }
            return [
                'success' => false,
                'error' => 'Rates unavailable in database',
                'rates' => [],
                'base_currency' => $baseCurrency,
                'timestamp' => now()->timestamp,
                'provider' => 'database'
            ];

        } catch (Exception $e) {
            Log::error('Failed to fetch exchange rates', [
                'base_currency' => $baseCurrency,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'base_currency' => $baseCurrency
            ];
        }
    }

    /**
     * Batch convert multiple amounts using locally cached rates (Optimized)
     * This prevents rate limiting by using cached rates instead of individual API calls
     */
    public function batchConvert(array $conversions): array
    {
        try {
            $results = [];
            $requiredRatePairs = [];

            // Group conversions by rate pairs to optimize fetching
            foreach ($conversions as $index => $conversion) {
                // Normalize currency codes (convert symbols to ISO codes)
                $from = $this->normalizeCurrencyCode($conversion['from_currency']);
                $to = $this->normalizeCurrencyCode($conversion['to_currency']);

                if ($from === $to) {
                    // Same currency - no conversion needed
                    $results[$index] = [
                        'success' => true,
                        'original_amount' => floatval($conversion['amount']),
                        'converted_amount' => floatval($conversion['amount']),
                        'from_currency' => $from,
                        'to_currency' => $to,
                        'rate' => 1.0,
                        'conversion_method' => 'same_currency'
                    ];
                    continue;
                }

                $requiredRatePairs[] = "{$from}_{$to}";
            }

            // Get unique base currencies needed
            $uniqueBases = array_unique(array_map(function($pair) {
                return explode('_', $pair)[0];
            }, $requiredRatePairs));

            // Fetch all rates for each unique base currency (one API call per base)
            $allRates = [];
            foreach ($uniqueBases as $base) {
                $rateData = $this->getAllExchangeRates($base);
                if ($rateData['success']) {
                    $allRates[$base] = $rateData['rates'];
                } else {
                    Log::warning("Failed to fetch rates for base currency: {$base}");
                }
            }

            // Process conversions using cached rates
            foreach ($conversions as $index => $conversion) {
                // Normalize currency codes (convert symbols to ISO codes)
                $from = $this->normalizeCurrencyCode($conversion['from_currency']);
                $to = $this->normalizeCurrencyCode($conversion['to_currency']);
                $amount = floatval($conversion['amount']);

                if ($from === $to) {
                    continue; // Already handled above
                }

                // Calculate conversion using cached rates
                if (isset($allRates[$from][$to])) {
                    $rate = $allRates[$from][$to];
                    $convertedAmount = round($amount * $rate, 2);

                    $results[$index] = [
                        'success' => true,
                        'original_amount' => $amount,
                        'converted_amount' => $convertedAmount,
                        'from_currency' => $from,
                        'to_currency' => $to,
                        'rate' => $rate,
                        'conversion_method' => 'cached_rates',
                        'cache_hit' => true
                    ];
                } else {
                    // Try reverse conversion (USD to EUR, but we have EUR to USD)
                    if (isset($allRates[$to][$from])) {
                        $rate = 1 / $allRates[$to][$from];
                        $convertedAmount = round($amount * $rate, 2);

                        $results[$index] = [
                            'success' => true,
                            'original_amount' => $amount,
                            'converted_amount' => $convertedAmount,
                            'from_currency' => $from,
                            'to_currency' => $to,
                            'rate' => $rate,
                            'conversion_method' => 'reverse_cached_rates',
                            'cache_hit' => true
                        ];
                    } else {
                        // Rate not available
                        $results[$index] = [
                            'success' => false,
                            'error' => "Exchange rate not available for {$from} to {$to}",
                            'original_amount' => $amount,
                            'from_currency' => $from,
                            'to_currency' => $to,
                            'fallback_used' => true,
                            'converted_amount' => $amount
                        ];
                    }
                }
            }

            return [
                'success' => true,
                'conversions' => $results,
                'total_processed' => count($conversions),
                'successful' => count(array_filter($results, fn($r) => $r['success'] ?? false)),
                'method' => 'batch_cached_conversion'
            ];

        } catch (Exception $e) {
            Log::error('Batch currency conversion failed', [
                'conversions_count' => count($conversions),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'conversions' => []
            ];
        }
    }

    /**
     * Convert currency symbols to ISO codes
     */
    private function normalizeCurrencyCode(string $currency): string
    {
        // Map common currency symbols to ISO codes
        $symbolToCode = [
            '$' => 'USD',
            '€' => 'EUR',
            '£' => 'GBP',
            '¥' => 'JPY',
            '₹' => 'INR',
            '₽' => 'RUB',
            '₩' => 'KRW',
            '₺' => 'TRY',
            '₨' => 'PKR',
            '₦' => 'NGN',
            '₡' => 'CRC',
            '₴' => 'UAH',
            '₫' => 'VND',
            '₭' => 'LAK',
            '₮' => 'MNT',
            '₲' => 'PYG',
            '₱' => 'PHP',
            '﷼' => 'IRR',
            '៛' => 'KHR',
            '₪' => 'ILS',
            '؋' => 'AFN',
            '₸' => 'KZT',
            '₼' => 'AZN',
            '€' => 'EUR',
            // Common currency code variations
            'R' => 'ZAR', // South African Rand
            'RM' => 'MYR', // Malaysian Ringgit
            'C$' => 'CAD', // Canadian Dollar
            'A$' => 'AUD', // Australian Dollar
            'HK$' => 'HKD', // Hong Kong Dollar
            'S$' => 'SGD', // Singapore Dollar
            'NZ$' => 'NZD', // New Zealand Dollar
            'Fr' => 'CHF', // Swiss Franc
        ];

        // Remove whitespace and convert to uppercase
        $currency = trim($currency);

        // Check if it's already a valid 3-letter ISO code
        if (strlen($currency) === 3 && ctype_alpha($currency)) {
            return strtoupper($currency);
        }

        // Check if it's a known symbol
        if (isset($symbolToCode[$currency])) {
            return $symbolToCode[$currency];
        }

        // Log warning for unknown currency
        Log::warning('Unknown currency symbol, defaulting to USD', [
            'original_currency' => $currency,
            'normalized_currency' => 'USD'
        ]);

        return 'USD'; // Default fallback
    }

    /**
     * Convert amount from one currency to another
     */
    public function convert(float $amount, string $from, string $to): array
    {
        try {
            // Validate inputs
            if ($amount < 0) {
                throw new Exception('Amount cannot be negative');
            }

            // Normalize currency codes (convert symbols to ISO codes)
            $from = $this->normalizeCurrencyCode($from);
            $to = $this->normalizeCurrencyCode($to);

            if ($from === $to) {
                return [
                    'success' => true,
                    'original_amount' => $amount,
                    'converted_amount' => $amount,
                    'from_currency' => $from,
                    'to_currency' => $to,
                    'rate' => 1.0,
                    'conversion_method' => 'same_currency'
                ];
            }

            $crossRate = $this->getCrossRateFromBase($from, $to);
            if (!($crossRate['success'] ?? false)) {
                throw new Exception($crossRate['error'] ?? 'Rates unavailable in database');
            }

            $convertedAmount = round($amount * $crossRate['rate'], 2);

            return [
                'success' => true,
                'original_amount' => $amount,
                'converted_amount' => $convertedAmount,
                'from_currency' => $from,
                'to_currency' => $to,
                'rate' => $crossRate['rate'],
                'rate_timestamp' => $crossRate['timestamp'],
                'conversion_method' => $crossRate['provider'],
                'cache_hit' => $crossRate['cache_hit'] ?? false
            ];

        } catch (Exception $e) {
            Log::warning('Currency conversion failed', [
                'amount' => $amount,
                'from' => $from,
                'to' => $to,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'original_amount' => $amount,
                'from_currency' => $from,
                'to_currency' => $to,
                'fallback_used' => true,
                'converted_amount' => $amount // Return original amount as fallback
            ];
        }
    }

    private function getCrossRateFromBase(string $from, string $to): array
    {
        $baseCurrency = $this->defaultBaseCurrency;
        $rateData = $this->getAllExchangeRates($baseCurrency);

        if (!$rateData['success']) {
            if ($baseCurrency !== 'USD') {
                $rateData = $this->getAllExchangeRates('USD');
            }
        }

        if (!($rateData['success'] ?? false)) {
            return [
                'success' => false,
                'error' => $rateData['error'] ?? 'Base currency rates unavailable',
                'rate' => null,
                'timestamp' => null,
                'provider' => 'none'
            ];
        }

        $rates = $rateData['rates'] ?? [];
        if (!isset($rates[$from], $rates[$to])) {
            return [
                'success' => false,
                'error' => "Missing rate for {$from} or {$to}",
                'rate' => null,
                'timestamp' => $rateData['timestamp'] ?? null,
                'provider' => $rateData['provider'] ?? 'cache'
            ];
        }

        $rate = $rates[$to] / $rates[$from];

        return [
            'success' => true,
            'rate' => $rate,
            'timestamp' => $rateData['timestamp'] ?? null,
            'provider' => $rateData['provider'] ?? 'cache',
            'cache_hit' => $rateData['cache_hit'] ?? false
        ];
    }

    /**
     * Get exchange rate between two currencies
     */
    public function getExchangeRate(string $from, string $to): array
    {
        try {
            // Normalize currency codes (convert symbols to ISO codes)
            $from = $this->normalizeCurrencyCode($from);
            $to = $this->normalizeCurrencyCode($to);

            if (!$this->isValidCurrencyCode($from) || !$this->isValidCurrencyCode($to)) {
                throw new Exception('Invalid currency code');
            }

            $result = $this->getCrossRateFromBase($from, $to);

            if (!($result['success'] ?? false)) {
                throw new Exception($result['error'] ?? 'Rates unavailable in database');
            }

            return [
                'success' => true,
                'rate' => $result['rate'],
                'timestamp' => $result['timestamp'],
                'provider' => $result['provider'] ?? 'database',
                'cache_hit' => $result['cache_hit'] ?? false
            ];

        } catch (Exception $e) {
            Log::error('Exchange rate fetch failed', [
                'from' => $from,
                'to' => $to,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'rate' => null,
                'timestamp' => null,
                'provider' => null
            ];
        }
    }

  
    /**
     * Fetch exchange rate from available providers
     */
    private function fetchExchangeRateFromProviders(string $from, string $to): array
    {
        $providers = array_keys($this->exchangeRateProviders);

        if (empty($providers)) {
            return [
                'success' => false,
                'error' => 'No exchange rate providers configured',
                'rate' => 1.0,
                'timestamp' => now()->timestamp,
                'provider' => 'none',
                'fallback_used' => true
            ];
        }

        foreach ($providers as $provider) {
            if ($this->isCircuitBreakerOpen($provider)) {
                continue;
            }

            try {
                $result = $this->fetchFromProvider($provider, $from, $to);

                if ($result['success']) {
                    $this->recordSuccess($provider);
                    return $result;
                }

                $this->recordFailure($provider);

            } catch (Exception $e) {
                $this->recordFailure($provider);
                Log::warning("Provider {$provider} failed", [
                    'error' => $e->getMessage(),
                    'from' => $from,
                    'to' => $to
                ]);
            }
        }

        // All providers failed, return fallback
        return [
            'success' => false,
            'error' => 'All exchange rate providers unavailable',
            'rate' => 1.0,
            'timestamp' => now()->timestamp,
            'provider' => 'fallback',
            'fallback_used' => true
        ];
    }

    private function getRatesFromDatabase(string $baseCurrency): ?array
    {
        $rates = CurrencyRate::query()
            ->where('base_currency', $baseCurrency)
            ->get(['target_currency', 'rate', 'fetched_at']);

        if ($rates->isEmpty()) {
            return null;
        }

        $mapped = $rates->pluck('rate', 'target_currency')->toArray();
        $mapped[$baseCurrency] = 1.0;

        return [
            'rates' => $mapped,
            'timestamp' => $rates->max('fetched_at')?->timestamp ?? now()->timestamp,
        ];
    }

    /**
     * Fetch all rates from providers
     */
    private function fetchAllRatesFromProviders(string $base): array
    {
        $providers = array_keys($this->exchangeRateProviders);

        if (empty($providers)) {
            return [
                'success' => false,
                'error' => 'No exchange rate providers configured',
                'rates' => [],
                'base_currency' => $base,
                'timestamp' => now()->timestamp,
                'provider' => 'none'
            ];
        }

        foreach ($providers as $provider) {
            if ($this->isCircuitBreakerOpen($provider)) {
                continue;
            }

            try {
                $result = $this->fetchAllRatesFromSingleProvider($provider, $base);

                if ($result['success']) {
                    $this->recordSuccess($provider);
                    return $result;
                }

                $this->recordFailure($provider);

            } catch (Exception $e) {
                $this->recordFailure($provider);
                Log::warning("Provider {$provider} failed for all rates", [
                    'error' => $e->getMessage(),
                    'base' => $base
                ]);
            }
        }

        return [
            'success' => false,
            'error' => 'All providers failed to fetch rates',
            'rates' => [],
            'base_currency' => $base,
            'timestamp' => now()->timestamp,
            'provider' => 'none'
        ];
    }

    /**
     * Fetch from specific provider with retry logic
     */
    private function fetchFromProvider(string $provider, string $from, string $to): array
    {
        $config = $this->exchangeRateProviders[$provider];
        $attempt = 0;

        while ($attempt < $this->maxRetries) {
            try {
                $url = $this->buildProviderUrl($config, $from);

                $response = Http::timeout($config['timeout'])
                    ->withHeaders([
                        'User-Agent' => 'CarRental/1.0 Currency Conversion',
                        'Accept' => 'application/json'
                    ])
                    ->get($url);

                if (!$response->successful()) {
                    throw new Exception("HTTP {$response->status()}: {$response->reason()}");
                }

                $data = $response->json();
                $rate = $this->parseProviderResponse($config, $data, $to);

                return [
                    'success' => true,
                    'rate' => $rate,
                    'timestamp' => $data['timestamp'] ?? now()->timestamp,
                    'provider' => $config['name'],
                    'cache_hit' => false
                ];

            } catch (Exception $e) {
                $attempt++;

                if ($attempt < $this->maxRetries) {
                    usleep($this->retryDelay * 1000); // Convert to microseconds
                }
            }
        }

        throw new Exception("Provider {$provider} failed after {$this->maxRetries} attempts");
    }

    /**
     * Fetch all rates from single provider
     */
    private function fetchAllRatesFromSingleProvider(string $provider, string $base): array
    {
        $config = $this->exchangeRateProviders[$provider];
        $attempt = 0;

        while ($attempt < $this->maxRetries) {
            try {
                $url = $this->buildProviderUrl($config, $base);

                $response = Http::timeout($config['timeout'])
                    ->withHeaders([
                        'User-Agent' => 'CarRental/1.0 Currency Conversion',
                        'Accept' => 'application/json'
                    ])
                    ->get($url);

                if (!$response->successful()) {
                    throw new Exception("HTTP {$response->status()}: {$response->reason()}");
                }

                $data = $response->json();
                $rates = $this->parseAllRatesResponse($config, $data);

                return [
                    'success' => true,
                    'rates' => $rates,
                    'base_currency' => $base,
                    'timestamp' => $data['timestamp'] ?? now()->timestamp,
                    'provider' => $config['name'],
                    'cache_hit' => false
                ];

            } catch (Exception $e) {
                $attempt++;

                if ($attempt < $this->maxRetries) {
                    usleep($this->retryDelay * 1000);
                }
            }
        }

        throw new Exception("Provider {$provider} failed after {$this->maxRetries} attempts");
    }

    /**
     * Build provider URL with API key
     */
    private function buildProviderUrl(array $config, string $base): string
    {
        $url = $config['url'];

        if ($config['requires_key']) {
            $apiKey = $this->getProviderApiKey($config['name']);
            $url = str_replace('{api_key}', $apiKey, $url);
        }

        return str_replace('{base}', $base, $url);
    }

    /**
     * Get API key for provider from config
     */
    private function getProviderApiKey(string $providerName): string
    {
        $keyMap = [
            'ExchangeRate-API' => config('services.exchangerate_api.key'),
            'Open Exchange Rates' => config('services.open_exchange_rates.key'),
            'CurrencyLayer' => config('services.currencylayer.key')
        ];

        $key = $keyMap[$providerName] ?? null;

        if (!$key) {
            throw new Exception("API key not configured for {$providerName}");
        }

        return $key;
    }

    /**
     * Parse provider-specific response
     */
    private function parseProviderResponse(array $config, array $data, string $to): float
    {
        switch ($config['name']) {
            case 'ExchangeRate-API':
                return $data['conversion_rates'][$to] ?? throw new Exception("Rate for {$to} not found");

            case 'Open Exchange Rates':
                return $data['rates'][$to] ?? throw new Exception("Rate for {$to} not found");

            case 'CurrencyLayer':
                return $data["quotes{$config['source']}{$to}"] ?? throw new Exception("Rate for {$to} not found");

            default:
                throw new Exception("Unknown provider: {$config['name']}");
        }
    }

    /**
     * Parse all rates response
     */
    private function parseAllRatesResponse(array $config, array $data): array
    {
        switch ($config['name']) {
            case 'ExchangeRate-API':
                return $data['conversion_rates'] ?? [];

            case 'Open Exchange Rates':
                return $data['rates'] ?? [];

            case 'CurrencyLayer':
                return $data['quotes'] ?? [];

            default:
                throw new Exception("Unknown provider: {$config['name']}");
        }
    }

    /**
     * Validate currency code
     */
    private function isValidCurrencyCode(string $code): bool
    {
        return strlen($code) === 3 && ctype_alpha($code);
    }

    /**
     * Check if circuit breaker is open
     */
    private function isCircuitBreakerOpen(string $provider): bool
    {
        $state = $this->circuitBreakerState[$provider];

        if ($state['state'] === 'open') {
            // Check if timeout period has passed
            $timeout = config('currency.circuit_breaker_timeout', 300); // 5 minutes
            if ($state['last_failure'] &&
                Carbon::parse($state['last_failure'])->diffInSeconds() > $timeout) {
                $this->circuitBreakerState[$provider]['state'] = 'half-open';
                return false;
            }
            return true;
        }

        return false;
    }

    /**
     * Record success for circuit breaker
     */
    private function recordSuccess(string $provider): void
    {
        $this->circuitBreakerState[$provider]['failures'] = 0;
        $this->circuitBreakerState[$provider]['state'] = 'closed';
    }

    /**
     * Record failure for circuit breaker
     */
    private function recordFailure(string $provider): void
    {
        $this->circuitBreakerState[$provider]['failures']++;
        $this->circuitBreakerState[$provider]['last_failure'] = now();

        $threshold = config('currency.circuit_breaker_threshold', 5);
        if ($this->circuitBreakerState[$provider]['failures'] >= $threshold) {
            $this->circuitBreakerState[$provider]['state'] = 'open';
        }
    }

    /**
     * Clear currency cache
     */
    public function clearCache(?string $from = null, ?string $to = null): void
    {
        if ($from && $to) {
            Cache::forget("exchange_rate_{$from}_{$to}");
        } else {
            // Clear all currency-related cache
            $keys = Cache::getRedis()->keys("*exchange_rate*");
            foreach ($keys as $key) {
                Cache::forget($key);
            }
        }
    }

    /**
     * Get conversion statistics
     */
    public function getStats(): array
    {
        return [
            'providers_configured' => count($this->exchangeRateProviders),
            'circuit_breaker_states' => $this->circuitBreakerState,
            'cache_ttl' => $this->cacheTtl,
            'max_retries' => $this->maxRetries,
            'request_timeout' => $this->requestTimeout,
            'default_base_currency' => $this->defaultBaseCurrency
        ];
    }
}
