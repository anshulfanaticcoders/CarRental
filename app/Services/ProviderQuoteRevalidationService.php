<?php

namespace App\Services;

use App\Services\Vehicles\GatewayVehicleTransformer;
use Illuminate\Support\Facades\Log;

class ProviderQuoteRevalidationService
{
    private const PRICE_TOLERANCE = 0.01;

    public function __construct(
        private readonly GatewaySearchParamsBuilder $gatewaySearchParamsBuilder,
        private readonly VrooemGatewayService $gatewayService,
        private readonly GatewayVehicleTransformer $vehicleTransformer,
        private readonly PriceVerificationService $priceVerificationService,
    ) {}

    public function revalidate(array $validated, array $verifiedPrices, string $searchSessionId): array
    {
        $selectedVehicle = is_array($validated['vehicle'] ?? null) ? $validated['vehicle'] : [];
        $provider = $this->normalizeProvider($selectedVehicle['source'] ?? ($verifiedPrices['provider'] ?? null));

        if ($provider === '' || $provider === 'internal') {
            return [
                'valid' => true,
                'vehicle' => $selectedVehicle,
                'verified_prices' => $verifiedPrices,
                'gateway_search_id' => $validated['gateway_search_id'] ?? ($selectedVehicle['gateway_search_id'] ?? null),
                'extras' => $validated['detailed_extras'] ?? [],
                'gateway_vehicle_context' => $verifiedPrices['gateway_vehicle_context'] ?? [],
            ];
        }

        $searchParams = $this->buildSearchParams($validated, $selectedVehicle, $verifiedPrices, $provider);
        if (! ($searchParams['valid'] ?? false)) {
            return $this->fail('FRESH_PROVIDER_CONTEXT_MISSING', $searchParams['error'] ?? 'Fresh provider quote validation is missing search context.');
        }

        $gatewayResult = $this->gatewayService->searchVehicles($searchParams['params']);
        $freshGatewaySearchId = $this->stringOrNull($gatewayResult['search_id'] ?? null);
        $rawVehicles = is_array($gatewayResult['vehicles'] ?? null) ? $gatewayResult['vehicles'] : [];

        if ($rawVehicles === []) {
            Log::warning('Fresh provider quote validation found no vehicles', [
                'provider' => $provider,
                'search_session' => $searchSessionId,
                'gateway_error' => $this->gatewayService->getLastError(),
            ]);

            return $this->fail('FRESH_PROVIDER_QUOTE_UNAVAILABLE', 'Supplier availability changed. Please refresh search results before checkout.');
        }

        $rentalDays = max(1, (int) ($validated['number_of_days'] ?? 1));
        $freshVehicles = $this->transformFreshVehicles($rawVehicles, $rentalDays, $freshGatewaySearchId);
        $matchedVehicle = $this->bestMatch($freshVehicles, $selectedVehicle, $provider, $validated['package'] ?? null);

        if (! $matchedVehicle) {
            Log::warning('Fresh provider quote validation could not match selected vehicle', [
                'provider' => $provider,
                'selected_vehicle_id' => $selectedVehicle['id'] ?? null,
                'selected_provider_vehicle_id' => $selectedVehicle['provider_vehicle_id'] ?? null,
                'selected_sipp_code' => $selectedVehicle['sipp_code'] ?? null,
                'fresh_count' => count($freshVehicles),
            ]);

            return $this->fail('FRESH_PROVIDER_QUOTE_CHANGED', 'Supplier availability changed. Please refresh search results before checkout.');
        }

        $freshPrices = $this->priceVerificationService->buildOriginalPriceData(
            $freshGatewaySearchId ?: $searchSessionId,
            $matchedVehicle
        );

        if (! is_array($freshPrices)) {
            return $this->fail('FRESH_PROVIDER_PRICE_CONTEXT_MISSING', 'Supplier pricing context changed. Please refresh search results before checkout.');
        }

        $priceValidation = $this->validateSelectedPackagePrice($validated['package'] ?? null, $verifiedPrices, $freshPrices);
        if (! ($priceValidation['valid'] ?? false)) {
            Log::warning('Fresh provider quote validation blocked changed price/package', [
                'provider' => $provider,
                'package' => $validated['package'] ?? null,
                'reason' => $priceValidation['reason'] ?? null,
            ]);

            return $this->fail('FRESH_PROVIDER_PRICE_CHANGED', 'The supplier updated this offer before payment. Please refresh search results and try again.');
        }

        $extrasValidation = $this->priceVerificationService->verifyAndResolveExtras(
            $validated['detailed_extras'] ?? [],
            $freshPrices,
            $validated['package'] ?? null
        );

        if (! ($extrasValidation['valid'] ?? false)) {
            Log::warning('Fresh provider quote validation blocked changed extras', [
                'provider' => $provider,
                'error' => $extrasValidation['error'] ?? null,
            ]);

            return $this->fail('FRESH_PROVIDER_EXTRAS_CHANGED', $extrasValidation['error'] ?? 'Selected extras changed. Please refresh search results before checkout.');
        }

        $matchedVehicle['gateway_search_id'] = $freshGatewaySearchId ?: ($matchedVehicle['gateway_search_id'] ?? null);

        return [
            'valid' => true,
            'vehicle' => $matchedVehicle,
            'verified_prices' => $freshPrices,
            'gateway_search_id' => $freshGatewaySearchId,
            'extras' => $extrasValidation['extras'] ?? [],
            'gateway_vehicle_context' => $freshPrices['gateway_vehicle_context'] ?? [],
        ];
    }

    private function buildSearchParams(array $validated, array $vehicle, array $verifiedPrices, string $provider): array
    {
        $vehicleContext = is_array($verifiedPrices['vehicle_context'] ?? null) ? $verifiedPrices['vehicle_context'] : [];
        $unifiedLocationId = $this->positiveIntegerOrNull($this->firstFilled(
            $validated['unified_location_id'] ?? null,
            $vehicle['unified_location_id'] ?? null,
            $vehicleContext['unified_location_id'] ?? null,
            data_get($validated, 'location_details.unified_location_id'),
            data_get($vehicle, 'location_details.unified_location_id'),
            data_get($vehicle, 'location.pickup.unified_location_id')
        ));
        $dropoffUnifiedLocationId = $this->positiveIntegerOrNull($this->firstFilled(
            $validated['dropoff_unified_location_id'] ?? null,
            $vehicle['dropoff_unified_location_id'] ?? null,
            $vehicleContext['dropoff_unified_location_id'] ?? null,
            data_get($validated, 'dropoff_location_details.unified_location_id'),
            data_get($vehicle, 'dropoff_location_details.unified_location_id'),
            data_get($vehicle, 'location.dropoff.unified_location_id')
        ));
        $providerPickupId = $this->firstFilled(
            $validated['provider_pickup_id'] ?? null,
            $vehicle['provider_pickup_id'] ?? null,
            $vehicleContext['provider_pickup_id'] ?? null,
            data_get($vehicle, 'location_details.provider_location_id'),
            data_get($vehicle, 'location.pickup.provider_location_id')
        );

        if ($unifiedLocationId === null && $providerPickupId === null) {
            return ['valid' => false, 'error' => 'Fresh provider quote validation is missing pickup location context.'];
        }

        try {
            $params = $this->gatewaySearchParamsBuilder->build([
                'unified_location_id' => $unifiedLocationId ?? 0,
                'dropoff_unified_location_id' => $dropoffUnifiedLocationId,
                'provider' => $provider,
                'provider_pickup_id' => $providerPickupId,
                'where' => $validated['pickup_location'] ?? ($vehicle['full_vehicle_address'] ?? null),
                'country' => data_get($validated, 'location_details.country_code') ?? data_get($vehicle, 'country_code'),
                'date_from' => $validated['pickup_date'] ?? null,
                'date_to' => $validated['dropoff_date'] ?? null,
                'start_time' => $validated['pickup_time'] ?? null,
                'end_time' => $validated['dropoff_time'] ?? null,
                'age' => $validated['customer']['driver_age'] ?? ($validated['age'] ?? ($vehicle['min_driver_age'] ?? 30)),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Fresh provider quote validation could not build search params', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return ['valid' => false, 'error' => 'Fresh provider quote validation could not resolve the selected pickup location.'];
        }

        $params['providers'] = $provider;
        $params['currency'] = strtoupper((string) ($validated['currency'] ?? data_get($vehicle, 'pricing.currency', 'EUR')));
        $params['bypass_cache'] = true;

        return ['valid' => true, 'params' => $params];
    }

    private function transformFreshVehicles(array $rawVehicles, int $rentalDays, ?string $gatewaySearchId): array
    {
        $vehicles = [];

        foreach ($rawVehicles as $rawVehicle) {
            if (! is_array($rawVehicle)) {
                continue;
            }

            try {
                $vehicle = $this->vehicleTransformer->transform($rawVehicle, $rentalDays);
                if ($gatewaySearchId) {
                    $vehicle['gateway_search_id'] = $gatewaySearchId;
                }
                $vehicles[] = $vehicle;
            } catch (\Throwable $e) {
                Log::warning('Fresh provider quote validation skipped untransformable vehicle', [
                    'vehicle_id' => $rawVehicle['id'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $vehicles;
    }

    private function bestMatch(array $freshVehicles, array $selectedVehicle, string $provider, ?string $selectedPackage): ?array
    {
        $matches = [];

        foreach ($freshVehicles as $vehicle) {
            if ($this->normalizeProvider($vehicle['source'] ?? null) !== $provider) {
                continue;
            }

            if (! $this->sameWhenBothPresent($selectedVehicle['provider_pickup_id'] ?? null, $vehicle['provider_pickup_id'] ?? null)) {
                continue;
            }

            if (! $this->sameWhenBothPresent(
                $selectedVehicle['provider_dropoff_id'] ?? ($selectedVehicle['provider_return_id'] ?? null),
                $vehicle['provider_dropoff_id'] ?? ($vehicle['provider_return_id'] ?? null)
            )) {
                continue;
            }

            $score = 0;
            $score += $this->scoreTokenMatch($selectedVehicle['provider_vehicle_id'] ?? null, $vehicle['provider_vehicle_id'] ?? null, 30);
            $score += $this->scoreTokenMatch($selectedVehicle['supplier_vehicle_id'] ?? null, $vehicle['supplier_vehicle_id'] ?? null, 20);
            $score += $this->scoreTokenMatch($selectedVehicle['provider_product_id'] ?? null, $vehicle['provider_product_id'] ?? null, 20);
            $score += $this->scoreTokenMatch($selectedVehicle['provider_rate_id'] ?? null, $vehicle['provider_rate_id'] ?? null, 15);
            $score += $this->scoreTokenMatch($selectedVehicle['sipp_code'] ?? null, $vehicle['sipp_code'] ?? null, 20);
            $score += $this->scoreTokenMatch($selectedVehicle['brand'] ?? null, $vehicle['brand'] ?? null, 5);
            $score += $this->scoreTokenMatch($selectedVehicle['model'] ?? null, $vehicle['model'] ?? null, 5);

            if ($selectedPackage && $this->productTotal($vehicle['products'] ?? [], $selectedPackage) !== null) {
                $score += 10;
            }

            if ($score >= 25) {
                $matches[] = ['score' => $score, 'vehicle' => $vehicle];
            }
        }

        if ($matches === []) {
            return null;
        }

        usort($matches, static fn (array $left, array $right): int => $right['score'] <=> $left['score']);

        return $matches[0]['vehicle'];
    }

    private function validateSelectedPackagePrice(?string $selectedPackage, array $oldPrices, array $freshPrices): array
    {
        $oldCurrency = strtoupper((string) ($oldPrices['currency'] ?? 'EUR'));
        $freshCurrency = strtoupper((string) ($freshPrices['currency'] ?? 'EUR'));

        if ($oldCurrency !== $freshCurrency) {
            return ['valid' => false, 'reason' => 'currency_changed'];
        }

        if ($this->isSurpriceFdwPackage($selectedPackage, $oldPrices, $freshPrices)) {
            return $this->validateSurpriceFdwPackage($oldPrices, $freshPrices);
        }

        $oldProducts = is_array($oldPrices['products'] ?? null) ? $oldPrices['products'] : [];
        $freshProducts = is_array($freshPrices['products'] ?? null) ? $freshPrices['products'] : [];
        $oldProductTotal = $this->productTotal($oldProducts, $selectedPackage);
        $freshProductTotal = $this->productTotal($freshProducts, $selectedPackage);

        if ($selectedPackage && ($oldProducts !== [] || $freshProducts !== []) && ($oldProductTotal === null || $freshProductTotal === null)) {
            return ['valid' => false, 'reason' => 'selected_package_missing'];
        }

        $oldTotal = $oldProductTotal ?? $this->floatOrNull($oldPrices['original_total'] ?? null);
        $freshTotal = $freshProductTotal ?? $this->floatOrNull($freshPrices['original_total'] ?? null);

        if ($oldTotal === null || $freshTotal === null) {
            return ['valid' => false, 'reason' => 'missing_package_price'];
        }

        if (abs($oldTotal - $freshTotal) > self::PRICE_TOLERANCE) {
            return ['valid' => false, 'reason' => 'package_price_changed'];
        }

        return ['valid' => true];
    }

    private function isSurpriceFdwPackage(?string $selectedPackage, array $oldPrices, array $freshPrices): bool
    {
        if (strtoupper(trim((string) $selectedPackage)) !== 'FDW') {
            return false;
        }

        return $this->normalizeProvider($oldPrices['provider'] ?? null) === 'surprice'
            || $this->normalizeProvider($freshPrices['provider'] ?? null) === 'surprice';
    }

    private function validateSurpriceFdwPackage(array $oldPrices, array $freshPrices): array
    {
        $oldFdw = $this->surpriceFdwContext($oldPrices);
        $freshFdw = $this->surpriceFdwContext($freshPrices);

        if ($oldFdw['total'] === null || $freshFdw['total'] === null) {
            return ['valid' => false, 'reason' => 'selected_package_missing'];
        }

        if ($freshFdw['vendor_rate_id'] === null || $freshFdw['rate_code'] === null) {
            return ['valid' => false, 'reason' => 'surprice_fdw_context_missing'];
        }

        if ($oldFdw['rate_code'] !== null && $oldFdw['rate_code'] !== $freshFdw['rate_code']) {
            return ['valid' => false, 'reason' => 'surprice_fdw_rate_code_changed'];
        }

        if (abs($oldFdw['total'] - $freshFdw['total']) > self::PRICE_TOLERANCE) {
            return ['valid' => false, 'reason' => 'package_price_changed'];
        }

        return ['valid' => true];
    }

    private function surpriceFdwContext(array $prices): array
    {
        return [
            'total' => $this->firstFloatFromPaths($prices, [
                'vehicle_context.supplier_data.fdw_total_amount',
                'gateway_vehicle_context.supplier_data.fdw_total_amount',
                'gateway_vehicle_context.raw_payload.fdw_total_amount',
                'supplier_data.fdw_total_amount',
            ]),
            'vendor_rate_id' => $this->firstStringFromPaths($prices, [
                'vehicle_context.supplier_data.fdw_vendor_rate_id',
                'gateway_vehicle_context.supplier_data.fdw_vendor_rate_id',
                'gateway_vehicle_context.raw_payload.fdw_vendor_rate_id',
                'supplier_data.fdw_vendor_rate_id',
            ]),
            'rate_code' => $this->firstStringFromPaths($prices, [
                'vehicle_context.supplier_data.fdw_rate_code',
                'gateway_vehicle_context.supplier_data.fdw_rate_code',
                'gateway_vehicle_context.raw_payload.fdw_rate_code',
                'supplier_data.fdw_rate_code',
            ]),
        ];
    }

    private function productTotal(array $products, ?string $selectedPackage): ?float
    {
        if (! $selectedPackage || $products === []) {
            return null;
        }

        foreach ($products as $product) {
            if (! is_array($product)) {
                continue;
            }

            if (strtoupper(trim((string) ($product['type'] ?? ''))) !== strtoupper(trim($selectedPackage))) {
                continue;
            }

            return $this->floatOrNull($product['total'] ?? ($product['total_price'] ?? null));
        }

        return null;
    }

    private function scoreTokenMatch(mixed $left, mixed $right, int $score): int
    {
        $left = $this->normalizeToken($left);
        $right = $this->normalizeToken($right);

        return $left !== '' && $right !== '' && $left === $right ? $score : 0;
    }

    private function sameWhenBothPresent(mixed $left, mixed $right): bool
    {
        $left = $this->normalizeToken($left);
        $right = $this->normalizeToken($right);

        return $left === '' || $right === '' || $left === $right;
    }

    private function normalizeProvider(mixed $provider): string
    {
        return match (strtolower(trim((string) $provider))) {
            'adobe_car' => 'adobe',
            'green_motion' => 'greenmotion',
            'ok_mobility' => 'okmobility',
            'record_go' => 'recordgo',
            'u_save' => 'usave',
            default => strtolower(trim((string) $provider)),
        };
    }

    private function normalizeToken(mixed $value): string
    {
        return strtolower(trim((string) $value));
    }

    private function positiveIntegerOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $integer = (int) $value;

        return $integer > 0 ? $integer : null;
    }

    private function stringOrNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function floatOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function firstFloatFromPaths(array $values, array $paths): ?float
    {
        foreach ($paths as $path) {
            $float = $this->floatOrNull(data_get($values, $path));
            if ($float !== null) {
                return $float;
            }
        }

        return null;
    }

    private function firstStringFromPaths(array $values, array $paths): ?string
    {
        foreach ($paths as $path) {
            $string = $this->stringOrNull(data_get($values, $path));
            if ($string !== null) {
                return $string;
            }
        }

        return null;
    }

    private function firstFilled(mixed ...$values): ?string
    {
        foreach ($values as $value) {
            $normalized = trim((string) $value);
            if ($normalized !== '') {
                return $normalized;
            }
        }

        return null;
    }

    private function fail(string $code, string $message): array
    {
        return [
            'valid' => false,
            'code' => $code,
            'error' => $message,
        ];
    }
}
