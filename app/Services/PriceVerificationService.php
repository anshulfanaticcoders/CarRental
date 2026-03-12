<?php

namespace App\Services;

use App\Services\PromoService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PriceVerificationService
{
    /**
     * Store original provider prices when search results are generated
     *
     * @param string $searchSessionId Unique identifier for this search session
     * @param array $vehicles Array of vehicles from search results
     * @return array Map of vehicle_id => [price_hash, price_token]
     */
    public function storeOriginalPrices(string $searchSessionId, array $vehicles): array
    {
        $priceMap = [];

        foreach ($vehicles as $vehicle) {
            $vehicleId = $vehicle['id']
                ?? $vehicle['gateway_vehicle_id']
                ?? $vehicle['provider_vehicle_id']
                ?? $vehicle['unified_location_id']
                ?? null;
            if (!$vehicleId) {
                continue;
            }

            // Store original prices - store by a composite key to avoid collisions
            $priceKey = $this->getPriceCacheKey($searchSessionId, $vehicleId);

            // Capture active promo at search time so hash includes promo state
            $activePromo = app(PromoService::class)->getActivePromo();

            $priceData = [
                'vehicle_id' => $vehicleId,
                'provider' => $vehicle['source'] ?? 'unknown',
                // Gateway vehicles use total_price/price_per_day while legacy uses total/daily_rate.
                'original_total' => $vehicle['total'] ?? ($vehicle['total_price'] ?? null),
                'original_daily_rate' => $vehicle['daily_rate'] ?? ($vehicle['price_per_day'] ?? null),
                'products' => $vehicle['products'] ?? [],
                'extras' => $vehicle['extras'] ?? ($vehicle['options'] ?? []),
                'price_per_day' => $vehicle['price_per_day'] ?? null,
                'currency' => $vehicle['currency'] ?? 'EUR',
                'stored_at' => now()->toIso8601String(),
                'search_session' => $searchSessionId,
                'promo_id' => $activePromo?->id,
                'promo_rate' => $activePromo ? (float) $activePromo->promo_markup_rate : 0,
            ];

            // Cache for 2 hours (typical user session duration)
            try {
                Cache::put($priceKey, $priceData, now()->addHours(2));
            } catch (\Exception $e) {
                Log::warning('Failed to cache price data', [
                    'key' => $priceKey,
                    'error' => $e->getMessage(),
                ]);
            }

            // Generate hash for client verification (HMAC signature)
            $priceMap[$vehicleId] = [
                'price_hash' => $this->generatePriceHash($priceData),
                'vehicle_id_hash' => hash('sha256', $vehicleId . $searchSessionId),
            ];
        }

        return $priceMap;
    }

    /**
     * Verify that client-sent prices match original stored prices
     *
     * @param string $searchSessionId
     * @param array $vehicleData Vehicle data sent from client
     * @return array ['valid' => bool, 'error' => string|null, 'original_prices' => array|null]
     */
    public function verifyPrices(string $searchSessionId, array $vehicleData): array
    {
        $vehicleId = $vehicleData['id']
            ?? $vehicleData['gateway_vehicle_id']
            ?? $vehicleData['provider_vehicle_id']
            ?? $vehicleData['unified_location_id']
            ?? null;
        $clientSentHash = $vehicleData['price_hash'] ?? null;

        if (!$vehicleId) {
            return [
                'valid' => false,
                'error' => 'Price verification failed: Missing vehicle ID',
            ];
        }

        $priceKey = $this->getPriceCacheKey($searchSessionId, $vehicleId);
        $storedData = Cache::get($priceKey);

        if (!$storedData) {
            Log::warning('Price verification failed: Original pricing data not found', [
                'vehicle_id' => $vehicleId,
                'search_session' => $searchSessionId,
                'cache_key' => $priceKey,
            ]);

            return [
                'valid' => false,
                'error' => 'Price verification failed: Original pricing data not found or expired. Please refresh search results.',
            ];
        }

        // Verify hash
        $expectedHash = $this->generatePriceHash($storedData);

        if (!hash_equals($expectedHash, $clientSentHash ?? '')) {
            Log::warning('Price manipulation detected - hash mismatch', [
                'vehicle_id' => $vehicleId,
                'search_session' => $searchSessionId,
                'expected_hash' => $expectedHash,
                'received_hash' => $clientSentHash,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return [
                'valid' => false,
                'error' => 'Price verification failed: Data integrity check failed',
            ];
        }

        // Verify total price matches (with small tolerance for rounding)
        $clientTotal = $vehicleData['total'] ?? ($vehicleData['total_price'] ?? null);
        $storedTotal = $storedData['original_total'];

        if ($clientTotal !== null && $storedTotal !== null) {
            $clientFloat = (float) $clientTotal;
            $storedFloat = (float) $storedTotal;
            $difference = abs($clientFloat - $storedFloat);

            // Allow 0.01 difference for rounding
            if ($difference > 0.01) {
                Log::warning('Price manipulation detected - total mismatch', [
                    'vehicle_id' => $vehicleId,
                    'search_session' => $searchSessionId,
                    'expected_total' => $storedTotal,
                    'received_total' => $clientTotal,
                    'difference' => $difference,
                    'ip' => request()->ip(),
                ]);

                return [
                    'valid' => false,
                    'error' => 'Price verification failed: Price mismatch detected. Please refresh search results.',
                ];
            }
        }

        // Verify products if present
        if (isset($vehicleData['products']) && !empty($storedData['products'])) {
            $clientProducts = $vehicleData['products'];
            $storedProducts = $storedData['products'];

            foreach ($storedProducts as $storedProduct) {
                $productType = $storedProduct['type'] ?? null;
                $storedProductTotal = $storedProduct['total'] ?? null;

                if ($productType && $storedProductTotal !== null) {
                    $matchingClientProduct = collect($clientProducts)->first(
                        fn($p) => ($p['type'] ?? null) === $productType
                    );

                    if ($matchingClientProduct) {
                        $clientProductTotal = $matchingClientProduct['total'] ?? null;
                        if ($clientProductTotal !== null) {
                            $difference = abs((float) $clientProductTotal - (float) $storedProductTotal);
                            if ($difference > 0.01) {
                                Log::warning('Price manipulation detected - product mismatch', [
                                    'vehicle_id' => $vehicleId,
                                    'product_type' => $productType,
                                    'expected_total' => $storedProductTotal,
                                    'received_total' => $clientProductTotal,
                                    'ip' => request()->ip(),
                                ]);

                                return [
                                    'valid' => false,
                                    'error' => 'Price verification failed: Package price mismatch detected.',
                                ];
                            }
                        }
                    }
                }
            }
        }

        return [
            'valid' => true,
            'original_prices' => $storedData,
        ];
    }

    /**
     * Get verified prices for booking (always use server-side values)
     *
     * @param string $searchSessionId
     * @param string $vehicleId
     * @return array|null Original price data
     */
    public function getVerifiedPrices(string $searchSessionId, string $vehicleId): ?array
    {
        $priceKey = $this->getPriceCacheKey($searchSessionId, $vehicleId);
        return Cache::get($priceKey);
    }

    /**
     * Generate price hash for verification
     */
    private function generatePriceHash(array $priceData): string
    {
        // Create a deterministic string from critical price data
        $dataToHash = json_encode([
            $priceData['vehicle_id'],
            $priceData['original_total'],
            $priceData['original_daily_rate'],
            $priceData['currency'],
            // Include products totals
            collect($priceData['products'])->pluck('total')->sort()->values()->toArray(),
            // Include promo state so hash changes if promo changes mid-session
            $priceData['promo_id'] ?? null,
            $priceData['promo_rate'] ?? 0,
        ]);

        return hash('sha256', $dataToHash . config('app.key'));
    }

    /**
     * Get cache key for price data
     */
    private function getPriceCacheKey(string $searchSessionId, string $vehicleId): string
    {
        return "price_verify_" . sha1($searchSessionId . '_' . $vehicleId);
    }

    /**
     * Verify extras prices against stored vehicle data
     *
     * @param array $clientExtras Extras sent from client
     * @param array $storedData Original stored vehicle data
     * @return array ['valid' => bool, 'error' => string|null]
     */
    public function verifyExtrasPrices(array $clientExtras, array $storedData): array
    {
        $result = $this->verifyAndResolveExtras($clientExtras, $storedData);

        return [
            'valid' => $result['valid'],
            'error' => $result['error'] ?? null,
        ];
    }

    public function verifyAndResolveExtras(array $clientExtras, array $storedData): array
    {
        $resolvedExtras = [];

        foreach ($clientExtras as $extra) {
            if (!is_array($extra)) {
                continue;
            }

            $extraId = $this->resolveExtraIdentifier($extra);
            if ($extraId === null) {
                return [
                    'valid' => false,
                    'error' => 'Price verification failed: Invalid extra selection.',
                ];
            }

            $storedExtra = collect($storedData['extras'] ?? [])
                ->first(fn($candidate) => $this->resolveExtraIdentifier($candidate) === $extraId);

            if (!$storedExtra) {
                Log::warning('Price manipulation detected - unknown selected extra', [
                    'extra_id' => $extraId,
                    'available_extra_ids' => collect($storedData['extras'] ?? [])
                        ->map(fn($candidate) => $this->resolveExtraIdentifier($candidate))
                        ->filter()
                        ->values()
                        ->all(),
                ]);

                return [
                    'valid' => false,
                    'error' => 'Price verification failed: Selected extra is no longer available.',
                ];
            }

            $clientPrice = $this->resolveExtraReferencePrice($extra);
            $storedPrice = $this->resolveExtraReferencePrice($storedExtra);

            if ($clientPrice !== null && $storedPrice !== null) {
                $difference = abs((float) $clientPrice - (float) $storedPrice);
                $tolerance = max(0.01, (float) $storedPrice * 0.05);

                if ($difference > $tolerance) {
                    Log::warning('Price manipulation detected - extra price mismatch', [
                        'extra_id' => $extraId,
                        'expected_price' => $storedPrice,
                        'received_price' => $clientPrice,
                        'difference' => $difference,
                    ]);

                    return [
                        'valid' => false,
                        'error' => 'Price verification failed: Extra price mismatch detected.',
                    ];
                }
            }

            $quantity = max(1, (int) ($extra['qty'] ?? $extra['quantity'] ?? 1));
            $resolvedExtras[] = array_merge($storedExtra, [
                'qty' => $quantity,
                'quantity' => $quantity,
            ]);
        }

        return [
            'valid' => true,
            'extras' => $resolvedExtras,
        ];
    }

    /**
     * Clear price data for a session (useful after successful booking)
     */
    public function clearPriceData(string $searchSessionId, ?string $vehicleId = null): void
    {
        if ($vehicleId) {
            Cache::forget($this->getPriceCacheKey($searchSessionId, $vehicleId));
        }
    }

    private function resolveExtraIdentifier(array $extra): ?string
    {
        $identifier = $extra['id']
            ?? $extra['option_id']
            ?? $extra['optionID']
            ?? $extra['extra_id']
            ?? $extra['extraId']
            ?? $extra['code']
            ?? null;

        if ($identifier === null) {
            return null;
        }

        $normalized = trim((string) $identifier);
        return $normalized !== '' ? $normalized : null;
    }

    private function resolveExtraReferencePrice(array $extra): ?float
    {
        $raw = $extra['total_for_booking']
            ?? $extra['Total_for_this_booking']
            ?? $extra['total_for_this_booking']
            ?? $extra['daily_rate']
            ?? $extra['dailyRate']
            ?? $extra['price']
            ?? $extra['amount']
            ?? null;

        return $raw !== null ? (float) $raw : null;
    }
}
