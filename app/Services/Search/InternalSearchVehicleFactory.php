<?php

namespace App\Services\Search;

use App\Services\Vehicles\SippCodeSuggestionService;
use Illuminate\Support\Facades\Storage;

class InternalSearchVehicleFactory
{
    public function make(array $vehicle, int $rentalDays, array $searchContext = []): array
    {
        $legacyPayload = $this->buildLegacyPayload($vehicle);
        $benefits = is_array($legacyPayload['benefits'] ?? null) ? $legacyPayload['benefits'] : [];
        $currency = $this->resolveCurrency($legacyPayload);
        $depositAmount = $this->toFloat($benefits['deposit_amount'] ?? $legacyPayload['security_deposit'] ?? null);
        $depositCurrency = $this->normalizeCurrencyCode($benefits['deposit_currency'] ?? $currency);
        $supplier = $this->resolveSupplier($vehicle);
        $pickupLocation = $this->buildLocationPayload(
            $vehicle,
            $this->nullableString($searchContext['pickup_location_id'] ?? null)
        );
        $dropoffLocation = $this->buildLocationPayload(
            $vehicle,
            $this->nullableString($searchContext['dropoff_location_id'] ?? ($searchContext['pickup_location_id'] ?? null))
        );

        return [
            'id' => (string) ($vehicle['id'] ?? ''),
            'gateway_vehicle_id' => null,
            'provider_vehicle_id' => isset($vehicle['id']) ? (string) $vehicle['id'] : null,
            'source' => 'internal',
            'provider_code' => 'internal',
            'supplier' => $supplier,
            'display_name' => $this->resolveDisplayName($vehicle),
            'brand' => $this->nullableString($vehicle['brand'] ?? null),
            'model' => $this->nullableString($vehicle['model'] ?? null),
            'category' => $this->resolveCategory($vehicle),
            'image' => $this->resolvePrimaryImage($legacyPayload['images'] ?? []),
            'specs' => [
                'transmission' => $this->normalizeTransmission($vehicle['transmission'] ?? null),
                'fuel' => $this->normalizeFuel($vehicle['fuel'] ?? null),
                'seating_capacity' => $this->toFloat($vehicle['seating_capacity'] ?? null),
                'doors' => $this->toFloat($vehicle['number_of_doors'] ?? ($vehicle['doors'] ?? null)),
                'luggage_small' => $this->toFloat($vehicle['small_bags'] ?? null),
                'luggage_medium' => $this->toFloat($vehicle['medium_bags'] ?? null),
                'luggage_large' => $this->toFloat($vehicle['large_bags'] ?? ($vehicle['luggage_capacity'] ?? null)),
                'air_conditioning' => $this->toBool($vehicle['air_conditioning'] ?? null),
                'sipp_code' => $this->resolveSippCode($vehicle),
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
            'latitude' => $this->toFloat($vehicle['latitude'] ?? null),
            'longitude' => $this->toFloat($vehicle['longitude'] ?? null),
            'full_vehicle_address' => $this->nullableString($vehicle['full_vehicle_address'] ?? null),
            // Top-level aliases for filters and card display
            'seating_capacity' => $this->toFloat($vehicle['seating_capacity'] ?? null),
            'doors' => $this->toFloat($vehicle['number_of_doors'] ?? ($vehicle['doors'] ?? null)),
            'transmission' => $this->normalizeTransmission($vehicle['transmission'] ?? null),
            'fuel' => $this->normalizeFuel($vehicle['fuel'] ?? null),
            'city' => $this->nullableString($vehicle['city'] ?? null),
            'country' => $this->nullableString($vehicle['country'] ?? null),
            'location' => [
                'pickup' => $pickupLocation,
                'dropoff' => $dropoffLocation,
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
        $payload['images'] = $this->normalizeLegacyImages($vehicle['images'] ?? []);
        $payload['addons'] = $vehicle['addons'] ?? [];
        $payload['benefits'] = $vehicle['benefits'] ?? [];

        return $payload;
    }

    private function normalizeLegacyImages(mixed $images): array
    {
        if (!is_array($images)) {
            return [];
        }

        return array_values(array_map(function ($image): mixed {
            if (!is_array($image)) {
                return $image;
            }

            $resolved = $this->resolveImageUrl($image);
            if ($resolved === null) {
                return $image;
            }

            $image['image_url'] = $resolved;

            return $image;
        }, $images));
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

    private function resolveSupplier(array $vehicle): array
    {
        $vendorProfile = $this->extractVendorLocationMeta($vehicle, 'vendorProfile')
            ?: $this->extractVendorLocationMeta($vehicle, 'vendor_profile')
            ?: $this->extractVendorLocationMeta($vehicle, 'vendorProfileData')
            ?: $this->extractVendorLocationMeta($vehicle, 'vendor_profile_data');

        $supplierName = $this->nullableString($vendorProfile['company_name'] ?? null)
            ?? $this->nullableString($vendorProfile['name'] ?? null)
            ?? 'Vrooem Internal Fleet';

        return [
            'code' => 'internal',
            'name' => $supplierName,
        ];
    }

    private function buildLocationPayload(array $vehicle, ?string $providerLocationId): array
    {
        $vendorLocation = $this->extractVendorLocationMeta($vehicle, 'vendorLocation')
            ?: $this->extractVendorLocationMeta($vehicle, 'vendor_location');

        return [
            'provider_location_id' => $providerLocationId,
            'name' => $this->nullableString($vehicle['location'] ?? null)
                ?? $this->nullableString($vendorLocation['name'] ?? null)
                ?? $this->nullableString($vehicle['full_vehicle_address'] ?? null),
            'address' => $this->nullableString($vehicle['full_vehicle_address'] ?? null),
            'city' => $this->nullableString($vehicle['city'] ?? null),
            'state' => $this->nullableString($vehicle['state'] ?? null),
            'country' => $this->nullableString($vehicle['country'] ?? null),
            'country_code' => $this->nullableString($vehicle['country_code'] ?? ($vendorLocation['country_code'] ?? null)),
            'location_type' => $this->nullableString($vehicle['location_type'] ?? ($vendorLocation['location_type'] ?? null)),
            'iata' => $this->nullableString($vehicle['iata_code'] ?? ($vendorLocation['iata_code'] ?? null)),
            'phone' => $this->nullableString($vehicle['location_phone'] ?? ($vendorLocation['phone'] ?? null)),
            'pickup_instructions' => $this->nullableString($vehicle['pickup_instructions'] ?? ($vendorLocation['pickup_instructions'] ?? null)),
            'dropoff_instructions' => $this->nullableString($vehicle['dropoff_instructions'] ?? ($vendorLocation['dropoff_instructions'] ?? null)),
            'latitude' => $this->toFloat($vehicle['latitude'] ?? ($vendorLocation['latitude'] ?? null)),
            'longitude' => $this->toFloat($vehicle['longitude'] ?? ($vendorLocation['longitude'] ?? null)),
        ];
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
            if (($image['image_type'] ?? null) === 'primary') {
                $resolved = $this->resolveImageUrl($image);

                if ($resolved !== null) {
                    return $resolved;
                }
            }
        }

        foreach ($images as $image) {
            if (($image['image_type'] ?? null) === 'gallery') {
                $resolved = $this->resolveImageUrl($image);

                if ($resolved !== null) {
                    return $resolved;
                }
            }
        }

        return null;
    }

    private function resolveImageUrl(array $image): ?string
    {
        $path = $this->nullableString($image['image_path'] ?? null);
        if ($path !== null) {
            return $this->normalizePublicUrl(
                Storage::disk('upcloud')->url(ltrim($path, '/'))
            );
        }

        $url = $this->nullableString($image['image_url'] ?? null);

        return $url === null ? null : $this->normalizePublicUrl($url);
    }

    private function normalizePublicUrl(string $url): string
    {
        $parts = parse_url($url);

        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            return $url;
        }

        $path = $parts['path'] ?? '';
        $normalizedPath = implode('/', array_map(
            static fn (string $segment): string => rawurlencode(rawurldecode($segment)),
            array_filter(explode('/', $path), static fn (string $segment): bool => $segment !== '')
        ));

        $prefix = $parts['scheme'] . '://' . $parts['host'];

        if (isset($parts['port'])) {
            $prefix .= ':' . $parts['port'];
        }

        $rebuilt = $prefix;
        $rebuilt .= str_starts_with($path, '/') ? '/' : '';
        $rebuilt .= $normalizedPath;

        if (isset($parts['query'])) {
            $rebuilt .= '?' . $parts['query'];
        }

        if (isset($parts['fragment'])) {
            $rebuilt .= '#' . $parts['fragment'];
        }

        return $rebuilt;
    }

    private function extractVendorLocationMeta(array $vehicle, string $key): array
    {
        $value = $vehicle[$key] ?? null;

        return is_array($value) ? $value : [];
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

    private function resolveSippCode(array $vehicle): ?string
    {
        $value = $this->nullableString($vehicle['sipp_code'] ?? null);

        if ($value === null) {
            return (new SippCodeSuggestionService())->suggest([
                'category_name' => $vehicle['category_name']
                    ?? ($vehicle['category']['name'] ?? null)
                    ?? ($vehicle['category']['title'] ?? null)
                    ?? null,
                'body_style' => $vehicle['body_style'] ?? null,
                'seating_capacity' => $vehicle['seating_capacity'] ?? null,
                'number_of_doors' => $vehicle['number_of_doors'] ?? null,
                'horsepower' => $vehicle['horsepower'] ?? null,
                'transmission' => $vehicle['transmission'] ?? null,
                'fuel' => $vehicle['fuel'] ?? null,
                'air_conditioning' => $vehicle['air_conditioning'] ?? null,
                'brand' => $vehicle['brand'] ?? null,
                'model' => $vehicle['model'] ?? null,
            ]);
        }

        return strtoupper($value);
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
