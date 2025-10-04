<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\CurrencyDetectionService;
use App\Services\CurrencyConversionService;
use Exception;

class CurrencyController extends Controller
{
    private CurrencyDetectionService $currencyDetectionService;
    private CurrencyConversionService $currencyConversionService;

    public function __construct(
        CurrencyDetectionService $currencyDetectionService,
        CurrencyConversionService $currencyConversionService
    ) {
        $this->currencyDetectionService = $currencyDetectionService;
        $this->currencyConversionService = $currencyConversionService;
    }

    /**
     * Get all supported currencies
     */
    public function index(): JsonResponse
    {
        try {
            // Try to load currencies from storage first
            if (Storage::disk('public')->exists('currencies.json')) {
                $currenciesJson = Storage::disk('public')->get('currencies.json');
                $currencies = json_decode($currenciesJson, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($currencies)) {
                    return response()->json([
                        'success' => true,
                        'data' => $currencies,
                        'source' => 'file',
                        'timestamp' => Storage::disk('public')->lastModified('currencies.json')
                    ]);
                }
            }

            // Fallback to service method
            $currencies = $this->currencyDetectionService->getSupportedCurrencies();

            return response()->json([
                'success' => true,
                'data' => $currencies,
                'source' => 'service',
                'timestamp' => now()->timestamp
            ]);

        } catch (Exception $e) {
            Log::error('Failed to load currencies', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to load currencies',
                'message' => config('app.debug') ? $e->getMessage() : 'Service temporarily unavailable',
                'fallback_data' => [
                    ['code' => 'USD', 'symbol' => '$', 'name' => 'US Dollar'],
                    ['code' => 'EUR', 'symbol' => '€', 'name' => 'Euro'],
                    ['code' => 'GBP', 'symbol' => '£', 'name' => 'British Pound']
                ]
            ], 500);
        }
    }

    /**
     * Detect currency based on IP address
     */
    public function detectCurrency(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'ip_address' => 'sometimes|ip'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'messages' => $validator->errors()
                ], 422);
            }

            $ipAddress = $request->input('ip_address');
            $result = $this->currencyDetectionService->detectCurrencyByIp($ipAddress);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'currency' => $result['currency'],
                        'country_code' => $result['country_code'],
                        'detection_method' => $result['detection_method'],
                        'confidence' => $result['confidence']
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Currency detection failed',
                    'fallback_currency' => $result['currency'],
                    'detection_method' => $result['detection_method'],
                    'confidence' => $result['confidence']
                ], 200);
            }

        } catch (Exception $e) {
            Log::warning('Currency detection endpoint failed', [
                'error' => $e->getMessage(),
                'ip' => $request->input('ip_address', 'unknown')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Currency detection service unavailable',
                'fallback_currency' => config('currency.default', 'USD'),
                'message' => config('app.debug') ? $e->getMessage() : 'Service temporarily unavailable'
            ], 503);
        }
    }

    /**
     * Detect currency from locale
     */
    public function detectCurrencyFromLocale(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'locale' => 'required|string|max:10'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'messages' => $validator->errors()
                ], 422);
            }

            $locale = $request->input('locale');
            $result = $this->currencyDetectionService->detectCurrencyFromLocale($locale);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'currency' => $result['currency'],
                        'country_code' => $result['country_code'],
                        'detection_method' => $result['detection_method'],
                        'confidence' => $result['confidence']
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Locale detection failed',
                    'fallback_currency' => $result['currency'],
                    'detection_method' => $result['detection_method'],
                    'confidence' => $result['confidence']
                ], 200);
            }

        } catch (Exception $e) {
            Log::warning('Locale currency detection failed', [
                'error' => $e->getMessage(),
                'locale' => $request->input('locale', 'unknown')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Locale detection service unavailable',
                'fallback_currency' => config('currency.default', 'USD'),
                'message' => config('app.debug') ? $e->getMessage() : 'Service temporarily unavailable'
            ], 503);
        }
    }

    /**
     * Convert currency
     */
    public function convert(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:0',
                'from' => 'required|string|size:3|alpha',
                'to' => 'required|string|size:3|alpha'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'messages' => $validator->errors()
                ], 422);
            }

            $amount = (float) $request->input('amount');
            $from = strtoupper($request->input('from'));
            $to = strtoupper($request->input('to'));

            $result = $this->currencyConversionService->convert($amount, $from, $to);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'original_amount' => $result['original_amount'],
                        'converted_amount' => $result['converted_amount'],
                        'from_currency' => $result['from_currency'],
                        'to_currency' => $result['to_currency'],
                        'rate' => $result['rate'],
                        'rate_timestamp' => $result['rate_timestamp'],
                        'conversion_method' => $result['conversion_method'],
                        'cache_hit' => $result['cache_hit'] ?? false
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Currency conversion failed',
                    'fallback_used' => $result['fallback_used'] ?? false,
                    'original_amount' => $result['original_amount'],
                    'from_currency' => $result['from_currency'],
                    'to_currency' => $result['to_currency']
                ], 200);
            }

        } catch (Exception $e) {
            Log::error('Currency conversion failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Currency conversion service unavailable',
                'message' => config('app.debug') ? $e->getMessage() : 'Service temporarily unavailable',
                'original_amount' => $request->input('amount', 0),
                'from_currency' => $request->input('from', 'USD'),
                'to_currency' => $request->input('to', 'USD')
            ], 503);
        }
    }

    /**
     * Get exchange rate
     */
    public function getExchangeRate(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'from' => 'required|string|size:3|alpha',
                'to' => 'required|string|size:3|alpha'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'messages' => $validator->errors()
                ], 422);
            }

            $from = strtoupper($request->input('from'));
            $to = strtoupper($request->input('to'));

            $result = $this->currencyConversionService->getExchangeRate($from, $to);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'from_currency' => $from,
                        'to_currency' => $to,
                        'rate' => $result['rate'],
                        'timestamp' => $result['timestamp'],
                        'provider' => $result['provider'],
                        'cache_hit' => $result['cache_hit'] ?? false
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Failed to get exchange rate',
                    'from_currency' => $from,
                    'to_currency' => $to,
                    'fallback_rate' => 1.0
                ], 200);
            }

        } catch (Exception $e) {
            Log::error('Exchange rate fetch failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Exchange rate service unavailable',
                'message' => config('app.debug') ? $e->getMessage() : 'Service temporarily unavailable',
                'from_currency' => $request->input('from', 'USD'),
                'to_currency' => $request->input('to', 'USD'),
                'fallback_rate' => 1.0
            ], 503);
        }
    }

    /**
     * Get all exchange rates for a base currency
     */
    public function getAllExchangeRates(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'base' => 'sometimes|string|size:3|alpha'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'messages' => $validator->errors()
                ], 422);
            }

            $base = strtoupper($request->input('base', 'USD'));

            $result = $this->currencyConversionService->getAllExchangeRates($base);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'base_currency' => $result['base_currency'],
                        'rates' => $result['rates'],
                        'timestamp' => $result['timestamp'],
                        'provider' => $result['provider'],
                        'cache_hit' => $result['cache_hit'] ?? false
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Failed to get exchange rates',
                    'base_currency' => $base,
                    'fallback_rates' => []
                ], 200);
            }

        } catch (Exception $e) {
            Log::error('All exchange rates fetch failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Exchange rate service unavailable',
                'message' => config('app.debug') ? $e->getMessage() : 'Service temporarily unavailable',
                'base_currency' => $request->input('base', 'USD'),
                'fallback_rates' => []
            ], 503);
        }
    }

    /**
     * Get service health and statistics
     */
    public function health(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'detection_service' => $this->currencyDetectionService->getDetectionStats(),
                    'conversion_service' => $this->currencyConversionService->getStats(),
                    'timestamp' => now()->toISOString(),
                    'status' => 'healthy'
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Currency service health check failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Health check failed',
                'message' => config('app.debug') ? $e->getMessage() : 'Service temporarily unavailable',
                'timestamp' => now()->toISOString(),
                'status' => 'unhealthy'
            ], 503);
        }
    }

    /**
     * Clear currency cache (admin only)
     */
    public function clearCache(Request $request): JsonResponse
    {
        try {
            if (!$request->user() || !$request->user()->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'from' => 'sometimes|string|size:3|alpha',
                'to' => 'sometimes|string|size:3|alpha'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'messages' => $validator->errors()
                ], 422);
            }

            $from = $request->input('from');
            $to = $request->input('to');

            $this->currencyConversionService->clearCache($from, $to);

            if ($from && $to) {
                $this->currencyDetectionService->clearDetectionCache();
            }

            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully',
                'cleared_at' => now()->toISOString()
            ]);

        } catch (Exception $e) {
            Log::error('Currency cache clear failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to clear cache',
                'message' => config('app.debug') ? $e->getMessage() : 'Operation failed'
            ], 500);
        }
    }
}
