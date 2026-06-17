<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PriceVerificationService
{
    /**
     * Store original provider prices when search results are generated
     *
     * @param  string  $searchSessionId  Unique identifier for this search session
     * @param  array  $vehicles  Array of vehicles from search results
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
            if (! $vehicleId) {
                continue;
            }

            // Store original prices - store by a composite key to avoid collisions
            $priceKey = $this->getPriceCacheKey($searchSessionId, $vehicleId);

            $offerFingerprint = app(OfferService::class)->getOfferFingerprint('search');

            $priceData = [
                'vehicle_id' => $vehicleId,
                'provider' => $vehicle['source'] ?? 'unknown',
                // Gateway vehicles use total_price/price_per_day while legacy uses total/daily_rate.
                'original_total' => $vehicle['total'] ?? ($vehicle['total_price'] ?? ($vehicle['pricing']['total_price'] ?? null)),
                'original_daily_rate' => $vehicle['daily_rate'] ?? ($vehicle['price_per_day'] ?? ($vehicle['pricing']['price_per_day'] ?? null)),
                'products' => $vehicle['products'] ?? [],
                'extras' => $vehicle['extras'] ?? ($vehicle['options'] ?? ($vehicle['extras_preview'] ?? [])),
                'price_per_day' => $vehicle['price_per_day'] ?? ($vehicle['pricing']['price_per_day'] ?? null),
                'currency' => $vehicle['currency'] ?? ($vehicle['pricing']['currency'] ?? 'EUR'),
                'stored_at' => now()->toIso8601String(),
                'search_session' => $searchSessionId,
                'offer_fingerprint' => $offerFingerprint,
                'vehicle_context' => $this->extractBookingContext($vehicle),
                'gateway_vehicle_context' => $this->extractGatewayVehicleContext($vehicle),
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
                'vehicle_id_hash' => hash('sha256', $vehicleId.$searchSessionId),
            ];
        }

        return $priceMap;
    }

    /**
     * Verify that client-sent prices match original stored prices
     *
     * @param  array  $vehicleData  Vehicle data sent from client
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

        if (! $vehicleId) {
            return [
                'valid' => false,
                'error' => 'Price verification failed: Missing vehicle ID',
            ];
        }

        $priceKey = $this->getPriceCacheKey($searchSessionId, $vehicleId);
        $storedData = Cache::get($priceKey);

        if (! $storedData) {
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

        if (! hash_equals($expectedHash, $clientSentHash ?? '')) {
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
        $clientTotal = $vehicleData['total'] ?? ($vehicleData['total_price'] ?? ($vehicleData['pricing']['total_price'] ?? null));
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
        if (isset($vehicleData['products']) && ! empty($storedData['products'])) {
            $clientProducts = $vehicleData['products'];
            $storedProducts = $storedData['products'];

            foreach ($storedProducts as $storedProduct) {
                $productType = $storedProduct['type'] ?? null;
                $storedProductTotal = $storedProduct['total'] ?? null;

                if ($productType && $storedProductTotal !== null) {
                    $matchingClientProduct = collect($clientProducts)->first(
                        fn ($p) => ($p['type'] ?? null) === $productType
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
            // Include offer state so hash changes if active offers change mid-session
            $priceData['offer_fingerprint'] ?? null,
        ]);

        return hash('sha256', $dataToHash.config('app.key'));
    }

    /**
     * Get cache key for price data
     */
    private function getPriceCacheKey(string $searchSessionId, string $vehicleId): string
    {
        return 'price_verify_'.sha1($searchSessionId.'_'.$vehicleId);
    }

    private function extractBookingContext(array $vehicle): array
    {
        $supplierData = is_array($vehicle['supplier_data'] ?? null) ? $vehicle['supplier_data'] : [];
        $source = strtolower((string) ($vehicle['source'] ?? 'unknown'));

        $context = [
            'id' => $vehicle['id'] ?? null,
            'gateway_vehicle_id' => $vehicle['gateway_vehicle_id'] ?? ($vehicle['id'] ?? null),
            'gateway_search_id' => $vehicle['gateway_search_id'] ?? null,
            'provider_vehicle_id' => $vehicle['provider_vehicle_id'] ?? null,
            'provider_product_id' => $vehicle['provider_product_id'] ?? null,
            'provider_rate_id' => $vehicle['provider_rate_id'] ?? ($supplierData['rate_id'] ?? ($supplierData['vendor_rate_id'] ?? null)),
            'provider_pickup_id' => $vehicle['provider_pickup_id'] ?? ($supplierData['pickup_code'] ?? null),
            'provider_return_id' => $vehicle['provider_return_id'] ?? ($vehicle['provider_dropoff_id'] ?? ($supplierData['dropoff_code'] ?? ($supplierData['pickup_code'] ?? null))),
            'provider_dropoff_id' => $vehicle['provider_dropoff_id'] ?? ($vehicle['provider_return_id'] ?? ($supplierData['dropoff_code'] ?? ($supplierData['pickup_code'] ?? null))),
            'source' => $source,
            'sipp_code' => $vehicle['sipp_code'] ?? ($supplierData['sipp_code'] ?? null),
            'supplier_data' => $supplierData,
            'quoteid' => $vehicle['quoteid'] ?? ($supplierData['quote_id'] ?? null),
            'rate_id' => $vehicle['rate_id'] ?? ($supplierData['rate_id'] ?? null),
            'availability_id' => $vehicle['availability_id'] ?? ($supplierData['availability_id'] ?? null),
            'connector_id' => $vehicle['connector_id'] ?? ($supplierData['connector_id'] ?? null),
            'provider_pickup_office_id' => $vehicle['provider_pickup_office_id'] ?? ($supplierData['pickup_office_id'] ?? null),
            'provider_dropoff_office_id' => $vehicle['provider_dropoff_office_id'] ?? ($supplierData['dropoff_office_id'] ?? null),
            'pricelist_id' => $vehicle['pricelist_id'] ?? ($supplierData['pricelist_id'] ?? null),
            'price_date' => $vehicle['price_date'] ?? ($supplierData['price_date'] ?? null),
            'prepaid' => $vehicle['prepaid'] ?? ($supplierData['prepaid'] ?? null),
            'ok_mobility_token' => $vehicle['ok_mobility_token'] ?? ($supplierData['token'] ?? null),
            'ok_mobility_group_id' => $vehicle['ok_mobility_group_id'] ?? ($supplierData['group_id'] ?? null),
            'ok_mobility_rate_code' => $vehicle['ok_mobility_rate_code'] ?? ($supplierData['rate_code'] ?? null),
            'recordgo_sellcode_ver' => $vehicle['recordgo_sellcode_ver'] ?? ($supplierData['sell_code_ver'] ?? null),
            'recordgo_selected_product' => $vehicle['recordgo_selected_product'] ?? null,
            'recordgo_products' => $vehicle['recordgo_products'] ?? null,
            'products' => $vehicle['products'] ?? null,
        ];

        if ($source === 'surprice') {
            $context['surprice_vendor_rate_id'] = $vehicle['surprice_vendor_rate_id']
                ?? $context['provider_rate_id']
                ?? ($supplierData['vendor_rate_id'] ?? null);
            $context['surprice_rate_code'] = $vehicle['surprice_rate_code'] ?? ($supplierData['rate_code'] ?? null);
            $context['surprice_extended_pickup_code'] = $vehicle['surprice_extended_pickup_code'] ?? ($supplierData['pickup_ext_code'] ?? null);
            $context['surprice_extended_dropoff_code'] = $vehicle['surprice_extended_dropoff_code'] ?? ($supplierData['dropoff_ext_code'] ?? null);
        }

        return array_filter($context, static fn ($value) => $value !== null && $value !== '');
    }

    private function extractGatewayVehicleContext(array $vehicle): array
    {
        $vehicleId = trim((string) ($vehicle['gateway_vehicle_id'] ?? $vehicle['id'] ?? ''));
        $source = strtolower(trim((string) ($vehicle['source'] ?? '')));

        if ($vehicleId === '' || $source === '' || $source === 'internal') {
            return [];
        }

        $supplierData = is_array($vehicle['supplier_data'] ?? null)
            ? $vehicle['supplier_data']
            : $this->extractLegacyProviderPayload($vehicle);
        $pricing = is_array($vehicle['pricing'] ?? null) ? $vehicle['pricing'] : [];
        $totalPrice = $vehicle['total']
            ?? $vehicle['total_price']
            ?? $pricing['total_price']
            ?? null;
        $dailyRate = $vehicle['daily_rate']
            ?? $vehicle['price_per_day']
            ?? $pricing['daily_rate']
            ?? $pricing['price_per_day']
            ?? null;

        $context = [
            'id' => $vehicleId,
            'gateway_vehicle_id' => $vehicleId,
            'search_id' => $vehicle['gateway_search_id'] ?? null,
            'gateway_search_id' => $vehicle['gateway_search_id'] ?? null,
            'supplier_id' => $source,
            'supplier_vehicle_id' => $vehicle['supplier_vehicle_id']
                ?? $vehicle['provider_vehicle_id']
                ?? $vehicleId,
            'provider_product_id' => $vehicle['provider_product_id'] ?? null,
            'provider_rate_id' => $vehicle['provider_rate_id']
                ?? $vehicle['rate_id']
                ?? ($supplierData['rate_id'] ?? null),
            'name' => $this->gatewayVehicleName($vehicle),
            'category' => $this->normalizeGatewayVehicleCategory($vehicle['category'] ?? $vehicle['vehicle_category'] ?? null),
            'make' => $vehicle['make'] ?? $vehicle['brand'] ?? null,
            'model' => $vehicle['model'] ?? null,
            'image_url' => $vehicle['image_url'] ?? $vehicle['image'] ?? null,
            'transmission' => $this->normalizeGatewayTransmission($vehicle['transmission'] ?? ($vehicle['specs']['transmission'] ?? null)),
            'fuel_type' => $this->normalizeGatewayFuel($vehicle['fuel_type'] ?? $vehicle['fuel'] ?? ($vehicle['specs']['fuel'] ?? null)),
            'seats' => $this->nullableInt($vehicle['seats'] ?? ($vehicle['specs']['seats'] ?? null)),
            'doors' => $this->nullableInt($vehicle['doors'] ?? ($vehicle['specs']['doors'] ?? null)),
            'bags_large' => $this->nullableInt($vehicle['bags_large'] ?? ($vehicle['specs']['bags_large'] ?? null)),
            'bags_small' => $this->nullableInt($vehicle['bags_small'] ?? ($vehicle['specs']['bags_small'] ?? null)),
            'air_conditioning' => $vehicle['air_conditioning'] ?? ($vehicle['specs']['air_conditioning'] ?? null),
            'mileage_policy' => $this->normalizeGatewayMileagePolicy($vehicle['mileage_policy'] ?? null),
            'mileage_limit_km' => $this->nullableInt($vehicle['mileage_limit_km'] ?? ($vehicle['policies']['mileage_limit_km'] ?? null)),
            'sipp_code' => $vehicle['sipp_code'] ?? ($supplierData['sipp_code'] ?? null),
            'pickup_location' => $this->normalizeGatewayLocation($vehicle['pickup_location'] ?? null),
            'dropoff_location' => $this->normalizeGatewayLocation($vehicle['dropoff_location'] ?? null),
            'pricing' => [
                'currency' => strtoupper((string) ($vehicle['currency'] ?? $pricing['currency'] ?? 'EUR')),
                'total_price' => (float) ($totalPrice ?? 0),
                'daily_rate' => (float) ($dailyRate ?? $totalPrice ?? 0),
                'deposit_amount' => isset($pricing['deposit_amount']) ? (float) $pricing['deposit_amount'] : null,
                'deposit_currency' => $pricing['deposit_currency'] ?? null,
            ],
            'supplier_data' => $supplierData,
            'raw_payload' => $vehicle['raw_payload'] ?? $this->extractLegacyProviderPayload($vehicle),
            'min_driver_age' => $this->nullableInt($vehicle['min_driver_age'] ?? null),
            'max_driver_age' => $this->nullableInt($vehicle['max_driver_age'] ?? null),
            'context_valid_until' => now()->addHours(2)->toIso8601String(),
        ];

        return $this->removeEmptyContextValues($context);
    }

    private function extractLegacyProviderPayload(array $vehicle): array
    {
        $payload = $vehicle['booking_context']['provider_payload'] ?? [];

        return is_array($payload) ? $payload : [];
    }

    private function gatewayVehicleName(array $vehicle): string
    {
        $brandModel = trim(implode(' ', array_filter([
            $vehicle['brand'] ?? $vehicle['make'] ?? null,
            $vehicle['model'] ?? null,
        ])));

        foreach ([$brandModel, $vehicle['display_name'] ?? null, $vehicle['name'] ?? null, $vehicle['provider_vehicle_id'] ?? null] as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return 'Rental vehicle';
    }

    private function normalizeGatewayVehicleCategory(mixed $value): string
    {
        $category = strtolower(trim((string) $value));

        return in_array($category, [
            'mini',
            'economy',
            'compact',
            'intermediate',
            'standard',
            'fullsize',
            'premium',
            'luxury',
            'suv',
            'van',
            'other',
        ], true) ? $category : 'other';
    }

    private function normalizeGatewayTransmission(mixed $value): ?string
    {
        $value = strtolower(trim((string) $value));
        if ($value === '') {
            return null;
        }

        return str_contains($value, 'auto') ? 'automatic' : 'manual';
    }

    private function normalizeGatewayFuel(mixed $value): ?string
    {
        $fuel = strtolower(trim((string) $value));
        if ($fuel === '') {
            return null;
        }

        foreach (['petrol', 'diesel', 'electric', 'hybrid', 'lpg'] as $allowed) {
            if (str_contains($fuel, $allowed)) {
                return $allowed;
            }
        }

        return 'unknown';
    }

    private function normalizeGatewayMileagePolicy(mixed $value): ?string
    {
        $policy = strtolower(trim((string) $value));
        if ($policy === '') {
            return null;
        }

        return str_contains($policy, 'unlimited') ? 'unlimited' : 'limited';
    }

    private function normalizeGatewayLocation(mixed $location): array
    {
        if (! is_array($location)) {
            return [];
        }

        return $this->removeEmptyContextValues([
            'id' => $location['id'] ?? $location['provider_location_id'] ?? null,
            'supplier_location_id' => $location['supplier_location_id'] ?? $location['provider_location_id'] ?? null,
            'name' => $location['name'] ?? null,
            'city' => $location['city'] ?? $location['address_city'] ?? null,
            'country_code' => $location['country_code'] ?? $location['country'] ?? null,
            'latitude' => isset($location['latitude']) ? (float) $location['latitude'] : null,
            'longitude' => isset($location['longitude']) ? (float) $location['longitude'] : null,
            'location_type' => $location['location_type'] ?? null,
            'airport_code' => $location['airport_code'] ?? null,
            'address' => $location['address'] ?? $location['address_1'] ?? null,
            'phone' => $location['phone'] ?? $location['telephone'] ?? null,
            'email' => $location['email'] ?? null,
            'is_airport' => $location['is_airport'] ?? null,
            'operating_hours' => $location['operating_hours'] ?? null,
            'pickup_instructions' => $location['pickup_instructions'] ?? $location['collection_details'] ?? null,
            'dropoff_instructions' => $location['dropoff_instructions'] ?? null,
            'out_of_hours' => $location['out_of_hours'] ?? null,
        ]);
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function removeEmptyContextValues(array $values): array
    {
        $clean = [];
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $value = $this->removeEmptyContextValues($value);
            }

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $clean[$key] = $value;
        }

        return $clean;
    }

    /**
     * Verify extras prices against stored vehicle data
     *
     * @param  array  $clientExtras  Extras sent from client
     * @param  array  $storedData  Original stored vehicle data
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

    public function verifyAndResolveExtras(array $clientExtras, array $storedData, ?string $selectedPackage = null): array
    {
        $resolvedExtras = [];

        foreach ($clientExtras as $extra) {
            if (! is_array($extra)) {
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
                ->first(fn ($candidate) => $this->resolveExtraIdentifier($candidate) === $extraId);
            $storedExtra ??= $this->resolveRecordGoProductComplement($extraId, $storedData, $selectedPackage);

            if (! $storedExtra) {
                // Skip validation for provider-built protection plans (not from search extras)
                if (str_starts_with($extraId, 'adobe_protection_') || str_starts_with($extraId, 'locauto_protection_') || str_starts_with($extraId, 'ins_')) {
                    $resolvedExtras[] = array_merge($extra, ['qty' => max(1, (int) ($extra['qty'] ?? $extra['quantity'] ?? 1))]);

                    continue;
                }

                Log::warning('Price manipulation detected - unknown selected extra', [
                    'extra_id' => $extraId,
                    'available_extra_ids' => $this->availableExtraIdentifiers($storedData, $selectedPackage),
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
            $maxQuantity = $this->resolveExtraMaxQuantity($storedExtra);
            if ($quantity > $maxQuantity) {
                Log::warning('Price manipulation detected - extra quantity exceeds supplier limit', [
                    'extra_id' => $extraId,
                    'max_quantity' => $maxQuantity,
                    'received_quantity' => $quantity,
                ]);

                return [
                    'valid' => false,
                    'error' => 'Price verification failed: Extra quantity exceeds supplier limit.',
                ];
            }

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

    private function resolveRecordGoProductComplement(string $extraId, array $storedData, ?string $selectedPackage): ?array
    {
        if (! str_starts_with($extraId, 'ext_recordgo_')) {
            return null;
        }

        foreach ($this->recordGoProductsForPackage($storedData, $selectedPackage) as $product) {
            $complements = $product['complements_associated']
                ?? $product['complementsAssociated']
                ?? [];
            if (! is_array($complements)) {
                continue;
            }

            foreach ($complements as $complement) {
                if (! is_array($complement)) {
                    continue;
                }

                $complementId = $complement['complementId'] ?? null;
                if ($complementId === null || "ext_recordgo_{$complementId}" !== $extraId) {
                    continue;
                }

                $total = $complement['priceTaxIncComplementDiscount']
                    ?? $complement['priceTaxIncComplement']
                    ?? $complement['total_for_booking']
                    ?? $complement['total_price']
                    ?? $complement['price']
                    ?? null;
                $daily = $complement['priceTaxIncDayDiscount']
                    ?? $complement['priceTaxIncDay']
                    ?? $complement['daily_rate']
                    ?? null;

                return array_filter([
                    'id' => $extraId,
                    'option_id' => $extraId,
                    'code' => (string) $complementId,
                    'name' => strip_tags((string) ($complement['complementName'] ?? 'RecordGo Extra')),
                    'description' => isset($complement['complementDescription'])
                        ? strip_tags((string) $complement['complementDescription'])
                        : null,
                    'total_for_booking' => $total !== null ? (float) $total : null,
                    'total_price' => $total !== null ? (float) $total : null,
                    'price' => $total !== null ? (float) $total : null,
                    'daily_rate' => $daily !== null ? (float) $daily : null,
                    'max_quantity' => $complement['maxUnits'] ?? $complement['maxQuantity'] ?? 1,
                    'type' => strtolower((string) ($complement['complementCategory'] ?? 'optional')),
                    'recordgo_payload' => $complement,
                ], static fn ($value) => $value !== null && $value !== '');
            }
        }

        return null;
    }

    private function recordGoProductsForPackage(array $storedData, ?string $selectedPackage): array
    {
        $products = [];
        foreach ([
            $storedData['recordgo_products'] ?? null,
            $storedData['vehicle_context']['recordgo_products'] ?? null,
            $storedData['vehicle_context']['supplier_data']['products'] ?? null,
            $storedData['supplier_data']['products'] ?? null,
            $storedData['products'] ?? null,
        ] as $candidateProducts) {
            if (is_array($candidateProducts)) {
                foreach ($candidateProducts as $product) {
                    if (is_array($product)) {
                        $products[] = $product;
                    }
                }
            }
        }

        if ($selectedPackage === null || $selectedPackage === '') {
            return $products;
        }

        return array_values(array_filter(
            $products,
            static fn (array $product): bool => (string) ($product['type'] ?? '') === $selectedPackage
        ));
    }

    private function availableExtraIdentifiers(array $storedData, ?string $selectedPackage): array
    {
        $identifiers = collect($storedData['extras'] ?? [])
            ->map(fn ($candidate) => is_array($candidate) ? $this->resolveExtraIdentifier($candidate) : null)
            ->filter()
            ->values()
            ->all();

        foreach ($this->recordGoProductsForPackage($storedData, $selectedPackage) as $product) {
            $complements = $product['complements_associated'] ?? [];
            if (! is_array($complements)) {
                continue;
            }
            foreach ($complements as $complement) {
                if (is_array($complement) && isset($complement['complementId'])) {
                    $identifiers[] = 'ext_recordgo_'.$complement['complementId'];
                }
            }
        }

        return array_values(array_unique($identifiers));
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

    private function resolveExtraMaxQuantity(array $extra): int
    {
        $raw = $extra['max_quantity']
            ?? $extra['maxQuantity']
            ?? $extra['numberAllowed']
            ?? $extra['allow_quantity']
            ?? 1;

        $max = (int) $raw;

        return $max > 0 ? $max : 1;
    }
}
