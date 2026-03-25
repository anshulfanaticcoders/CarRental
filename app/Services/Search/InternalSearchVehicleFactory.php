<?php

namespace App\Services\Search;

class InternalSearchVehicleFactory
{
    public function make(array $vehicle, int $rentalDays, array $searchContext = []): array
    {
        $legacyPayload = $this->buildLegacyPayload($vehicle);
        $benefits = is_array($legacyPayload['benefits'] ?? null) ? $legacyPayload['benefits'] : [];
        $currency = $this->resolveCurrency($legacyPayload);
        $depositAmount = $this->toFloat($benefits['deposit_amount'] ?? $legacyPayload['security_deposit'] ?? null);
        $depositCurrency = $this->normalizeCurrencyCode($benefits['deposit_currency'] ?? $currency);

        return [
            'id' => (string) ($vehicle['id'] ?? ''),
            'gateway_vehicle_id' => null,
            'provider_vehicle_id' => isset($vehicle['id']) ? (string) $vehicle['id'] : null,
            'source' => 'internal',
            'provider_code' => 'internal',
            'display_name' => $this->resolveDisplayName($vehicle),
            'brand' => $this->nullableString($vehicle['brand'] ?? null),
            'model' => $this->nullableString($vehicle['model'] ?? null),
            'category' => $this->resolveCategory($vehicle),
            'image' => $this->resolvePrimaryImage($legacyPayload['images'] ?? []),
            'specs' => [
                'transmission' => $this->normalizeTransmission($vehicle['transmission'] ?? null),
                'fuel' => $this->normalizeFuel($vehicle['fuel'] ?? null),
                'seating_capacity' => $this->toFloat($vehicle['seating_capacity'] ?? null),
                'doors' => $this->toFloat($vehicle['doors'] ?? null),
                'luggage_small' => $this->toFloat($vehicle['small_bags'] ?? null),
                'luggage_medium' => $this->toFloat($vehicle['medium_bags'] ?? null),
                'luggage_large' => $this->toFloat($vehicle['large_bags'] ?? null),
                'air_conditioning' => $this->toBool($vehicle['air_conditioning'] ?? null),
                'sipp_code' => null,
            ],
            'pricing' => [
                'currency' => $currency,
                'price_per_day' => $this->toFloat($vehicle['price_per_day'] ?? 0) ?? 0.0,
                'total_price' => round(($this->toFloat($vehicle['price_per_day'] ?? 0) ?? 0.0) * max(1, $rentalDays), 2),
                'deposit_amount' => $depositAmount,
                'deposit_currency' => $depositCurrency,
                'excess_amount' => $this->toFloat($benefits['excess_amount'] ?? null),
                'excess_theft_amount' => $this->toFloat($benefits['excess_theft_amount'] ?? null),
            ],
            'policies' => [
                'mileage_policy' => $this->resolveMileagePolicy($benefits),
                'mileage_limit_km' => $this->toFloat($benefits['limited_km_per_day_range'] ?? null),
                'fuel_policy' => $this->resolveFuelPolicy($benefits),
                'cancellation' => $this->resolveCancellation($benefits),
            ],
            'products' => $this->buildProducts($legacyPayload, $rentalDays, $currency, $benefits),
            'extras_preview' => $this->buildExtrasPreview($legacyPayload, $rentalDays, $currency),
            'location' => [
                'pickup' => [
                    'provider_location_id' => $this->nullableString($searchContext['pickup_location_id'] ?? null),
                    'name' => $this->nullableString($vehicle['location'] ?? null),
                    'latitude' => $this->toFloat($vehicle['latitude'] ?? null),
                    'longitude' => $this->toFloat($vehicle['longitude'] ?? null),
                ],
                'dropoff' => [
                    'provider_location_id' => $this->nullableString($searchContext['dropoff_location_id'] ?? ($searchContext['pickup_location_id'] ?? null)),
                    'name' => $this->nullableString($vehicle['location'] ?? null),
                    'latitude' => $this->toFloat($vehicle['latitude'] ?? null),
                    'longitude' => $this->toFloat($vehicle['longitude'] ?? null),
                ],
            ],
            'data_quality_flags' => [],
            'pricing_transparency_flags' => [],
            'ui_placeholders' => [
                'image' => $this->resolvePrimaryImage($legacyPayload['images'] ?? []) === null,
            ],
            'booking_context' => [
                'version' => 1,
                'provider_payload' => $legacyPayload,
            ],
        ];
    }

    private function buildLegacyPayload(array $vehicle): array
    {
        $payload = $vehicle;

        $payload['source'] = 'internal';
        $payload['currency'] = $this->resolveCurrency($vehicle);
        $payload['security_deposit'] = $this->toFloat($vehicle['security_deposit'] ?? null);
        $payload['vendorPlans'] = $vehicle['vendorPlans'] ?? $vehicle['vendor_plans'] ?? [];
        $payload['vendor_plans'] = $payload['vendorPlans'];
        $payload['vendorProfileData'] = $vehicle['vendorProfileData'] ?? $vehicle['vendor_profile_data'] ?? [];
        $payload['vendor_profile_data'] = $payload['vendorProfileData'];
        $payload['vendorProfile'] = $vehicle['vendorProfile'] ?? $vehicle['vendor_profile'] ?? [];
        $payload['vendor_profile'] = $payload['vendorProfile'];
        $payload['images'] = $vehicle['images'] ?? [];
        $payload['addons'] = $vehicle['addons'] ?? [];
        $payload['benefits'] = $vehicle['benefits'] ?? [];

        return $payload;
    }

    private function buildProducts(array $legacyPayload, int $rentalDays, string $currency, array $benefits): array
    {
        $products = [[
            'type' => 'BAS',
            'name' => 'Basic Rental',
            'subtitle' => 'Standard Package',
            'total' => round(($this->toFloat($legacyPayload['price_per_day'] ?? 0) ?? 0.0) * max(1, $rentalDays), 2),
            'price_per_day' => $this->toFloat($legacyPayload['price_per_day'] ?? 0) ?? 0.0,
            'deposit' => $this->toFloat($benefits['deposit_amount'] ?? $legacyPayload['security_deposit'] ?? null),
            'deposit_currency' => $this->normalizeCurrencyCode($benefits['deposit_currency'] ?? $currency),
            'excess' => $this->toFloat($benefits['excess_amount'] ?? null),
            'excess_theft_amount' => $this->toFloat($benefits['excess_theft_amount'] ?? null),
            'benefits' => [],
            'is_basic' => true,
            'currency' => $currency,
        ]];

        foreach (($legacyPayload['vendorPlans'] ?? []) as $index => $plan) {
            $features = $plan['features'] ?? [];
            if (is_string($features)) {
                $decoded = json_decode($features, true);
                $features = is_array($decoded) ? $decoded : [];
            }

            $products[] = [
                'type' => $this->nullableString($plan['plan_type'] ?? null) ?? "PLAN_{$index}",
                'name' => $this->nullableString($plan['plan_type'] ?? null) ?? 'Custom Plan',
                'subtitle' => $this->nullableString($plan['plan_description'] ?? null) ?? 'Vendor Package',
                'total' => round(($this->toFloat($plan['price'] ?? 0) ?? 0.0) * max(1, $rentalDays), 2),
                'price_per_day' => $this->toFloat($plan['price'] ?? 0) ?? 0.0,
                'deposit' => $this->toFloat($benefits['deposit_amount'] ?? $legacyPayload['security_deposit'] ?? null),
                'deposit_currency' => $this->normalizeCurrencyCode($benefits['deposit_currency'] ?? $currency),
                'excess' => $this->toFloat($benefits['excess_amount'] ?? null),
                'excess_theft_amount' => $this->toFloat($benefits['excess_theft_amount'] ?? null),
                'benefits' => is_array($features) ? array_values(array_filter($features, fn ($value) => is_string($value) && trim($value) !== '')) : [],
                'currency' => $currency,
                'vendor_plan_id' => $plan['id'] ?? null,
            ];
        }

        return $products;
    }

    private function buildExtrasPreview(array $legacyPayload, int $rentalDays, string $currency): array
    {
        $extras = [];

        foreach (($legacyPayload['addons'] ?? []) as $addon) {
            $dailyRate = $this->toFloat($addon['price'] ?? 0) ?? 0.0;
            $extras[] = [
                'id' => isset($addon['id']) ? "internal_addon_{$addon['id']}" : null,
                'code' => isset($addon['addon_id']) ? (string) $addon['addon_id'] : (isset($addon['id']) ? (string) $addon['id'] : null),
                'name' => $this->nullableString($addon['extra_name'] ?? null) ?? 'Extra',
                'description' => $this->nullableString($addon['description'] ?? null) ?? $this->nullableString($addon['extra_name'] ?? null) ?? 'Optional add-on',
                'daily_rate' => $dailyRate,
                'total_for_booking' => round($dailyRate * max(1, $rentalDays), 2),
                'currency' => $currency,
                'max_quantity' => (int) ($addon['quantity'] ?? 1),
            ];
        }

        return $extras;
    }

    private function resolveDisplayName(array $vehicle): string
    {
        $parts = array_values(array_filter([
            $this->nullableString($vehicle['brand'] ?? null),
            $this->nullableString($vehicle['model'] ?? null),
        ]));

        return trim(implode(' ', $parts));
    }

    private function resolveCategory(array $vehicle): ?string
    {
        $categoryName = $vehicle['category_name']
            ?? ($vehicle['category']['name'] ?? null)
            ?? ($vehicle['category']['title'] ?? null)
            ?? $vehicle['category'] ?? null;

        $categoryName = $this->nullableString($categoryName);
        if ($categoryName === null) {
            return null;
        }

        return strtolower($categoryName);
    }

    private function resolvePrimaryImage(array $images): ?string
    {
        foreach ($images as $image) {
            if (($image['image_type'] ?? null) === 'primary' && !empty($image['image_url'])) {
                return (string) $image['image_url'];
            }
        }

        foreach ($images as $image) {
            if (($image['image_type'] ?? null) === 'gallery' && !empty($image['image_url'])) {
                return (string) $image['image_url'];
            }
        }

        return null;
    }

    private function resolveMileagePolicy(array $benefits): ?string
    {
        if (!array_key_exists('limited_km_per_day', $benefits)) {
            return null;
        }

        return (int) $benefits['limited_km_per_day'] === 1 ? 'limited' : 'unlimited';
    }

    private function resolveFuelPolicy(array $benefits): ?string
    {
        return $this->nullableString($benefits['fuel_policy'] ?? null);
    }

    private function resolveCancellation(array $benefits): ?array
    {
        if ((int) ($benefits['cancellation_available_per_day'] ?? 0) !== 1) {
            return null;
        }

        return [
            'available' => true,
            'days_before_pickup' => (int) ($benefits['cancellation_available_per_day_date'] ?? 0),
        ];
    }

    private function resolveCurrency(array $vehicle): string
    {
        $rawCurrency = $vehicle['currency']
            ?? ($vehicle['vendorProfileData']['currency'] ?? null)
            ?? ($vehicle['vendor_profile_data']['currency'] ?? null)
            ?? ($vehicle['vendorProfile']['currency'] ?? null)
            ?? ($vehicle['vendor_profile']['currency'] ?? null)
            ?? ($vehicle['benefits']['deposit_currency'] ?? null)
            ?? 'USD';

        return $this->normalizeCurrencyCode($rawCurrency);
    }

    private function normalizeCurrencyCode(mixed $value): string
    {
        $raw = trim((string) ($value ?? ''));
        $map = [
            '€' => 'EUR',
            '$' => 'USD',
            '£' => 'GBP',
            '₹' => 'INR',
            '₽' => 'RUB',
            'A$' => 'AUD',
        ];

        return strtoupper($map[$raw] ?? ($raw !== '' ? $raw : 'USD'));
    }

    private function normalizeTransmission(mixed $value): ?string
    {
        $text = strtolower(trim((string) ($value ?? '')));
        if ($text === '') {
            return null;
        }
        return $text === 'automatic' ? 'automatic' : ($text === 'manual' ? 'manual' : null);
    }

    private function normalizeFuel(mixed $value): ?string
    {
        $text = strtolower(trim((string) ($value ?? '')));
        $allowed = ['petrol', 'diesel', 'electric', 'hybrid', 'lpg'];

        return in_array($text, $allowed, true) ? $text : null;
    }

    private function nullableString(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));
        return $text === '' ? null : $text;
    }

    private function toFloat(mixed $value): ?float
    {
        if (!is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function toBool(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === 1 || $value === '1' || $value === 'true') {
            return true;
        }

        if ($value === 0 || $value === '0' || $value === 'false') {
            return false;
        }

        return null;
    }
}
