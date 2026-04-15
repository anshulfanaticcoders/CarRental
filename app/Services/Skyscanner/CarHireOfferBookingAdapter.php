<?php

namespace App\Services\Skyscanner;

use Carbon\CarbonImmutable;

class CarHireOfferBookingAdapter
{
    public function build(array $quote): array
    {
        $pickupLocation = $this->normalizeLocationDetails($quote['pickup_location_details'] ?? []);
        $dropoffLocation = $this->normalizeLocationDetails($quote['dropoff_location_details'] ?? []);
        $products = $this->normalizeProducts($quote['products'] ?? []);
        $optionalExtras = $this->normalizeOptionalExtras($quote['extras_preview'] ?? []);
        $search = is_array($quote['search'] ?? null) ? $quote['search'] : [];
        $pricing = is_array($quote['pricing'] ?? null) ? $quote['pricing'] : [];
        $vehicle = is_array($quote['vehicle'] ?? null) ? $quote['vehicle'] : [];
        $supplier = is_array($quote['supplier'] ?? null) ? $quote['supplier'] : [];
        $benefits = $this->buildBenefits($quote);
        $currency = $this->stringOrNull($pricing['currency'] ?? ($search['currency'] ?? 'EUR'));
        $totalPrice = $this->floatOrNull($pricing['total_price'] ?? null);
        $pricePerDay = $this->floatOrNull($pricing['price_per_day'] ?? null);
        $depositAmount = $this->floatOrNull($pricing['deposit_amount'] ?? null);
        $source = $this->stringOrNull($vehicle['source'] ?? null)
            ?? $this->stringOrNull($supplier['code'] ?? null)
            ?? 'internal';
        $baseProviderPayload = is_array(data_get($vehicle, 'booking_context.provider_payload'))
            ? data_get($vehicle, 'booking_context.provider_payload')
            : [];
        $providerPayload = array_merge($baseProviderPayload, [
            'source' => $source,
            'currency' => $currency,
            'security_deposit' => $depositAmount,
            'vendorPlans' => $this->buildVendorPlans($products),
            'vendor_plans' => $this->buildVendorPlans($products),
            'vendorProfileData' => array_merge(
                is_array($baseProviderPayload['vendorProfileData'] ?? null) ? $baseProviderPayload['vendorProfileData'] : [],
                [
                    'company_name' => $this->stringOrNull($supplier['name'] ?? ($vehicle['supplier_name'] ?? 'Vrooem Internal Fleet')),
                    'currency' => $currency,
                    'phone' => $pickupLocation['telephone'] ?? null,
                    'city' => $pickupLocation['address_city'] ?? null,
                    'country' => $pickupLocation['address_country'] ?? null,
                ]
            ),
            'vendor_profile_data' => array_merge(
                is_array($baseProviderPayload['vendor_profile_data'] ?? null) ? $baseProviderPayload['vendor_profile_data'] : [],
                [
                    'company_name' => $this->stringOrNull($supplier['name'] ?? ($vehicle['supplier_name'] ?? 'Vrooem Internal Fleet')),
                    'currency' => $currency,
                    'phone' => $pickupLocation['telephone'] ?? null,
                    'city' => $pickupLocation['address_city'] ?? null,
                    'country' => $pickupLocation['address_country'] ?? null,
                ]
            ),
            'benefits' => $benefits,
            'addons' => $optionalExtras,
            'images' => $this->buildImages($vehicle),
        ]);
        $adapterExtras = $this->buildAdapterExtras($providerPayload['extras'] ?? null, $optionalExtras);
        $pickupOffice = is_array($providerPayload['pickup_office'] ?? null) ? $providerPayload['pickup_office'] : null;
        $dropoffOffice = is_array($providerPayload['dropoff_office'] ?? null) ? $providerPayload['dropoff_office'] : null;

        return [
            'vehicle' => [
                'id' => $this->stringOrNull($vehicle['provider_vehicle_id'] ?? null),
                'provider_vehicle_id' => $this->stringOrNull($vehicle['provider_vehicle_id'] ?? null),
                'source' => $source,
                'provider_code' => $this->stringOrNull($vehicle['provider_code'] ?? ($supplier['code'] ?? ($vehicle['supplier_code'] ?? 'internal'))),
                'provider_product_id' => $this->stringOrNull($vehicle['provider_product_id'] ?? null),
                'provider_rate_id' => $this->stringOrNull($vehicle['provider_rate_id'] ?? null),
                'brand' => $this->stringOrNull($vehicle['brand'] ?? null),
                'model' => $this->stringOrNull($vehicle['model'] ?? null),
                'display_name' => $this->stringOrNull($vehicle['display_name'] ?? null),
                'category' => $this->stringOrNull($vehicle['category'] ?? null),
                'image' => $this->stringOrNull($vehicle['image_url'] ?? null),
                'currency' => $currency,
                'total_price' => $totalPrice,
                'price_per_day' => $pricePerDay,
                'security_deposit' => $depositAmount,
                'deposit' => $depositAmount,
                'benefits' => $benefits,
                'extras' => $adapterExtras,
                'supplier_data' => is_array($providerPayload['supplier_data'] ?? null) ? $providerPayload['supplier_data'] : [],
                'preview_value' => $this->floatOrNull($providerPayload['preview_value'] ?? $totalPrice),
                'value_without_tax' => $this->floatOrNull($providerPayload['value_without_tax'] ?? null),
                'tax_rate' => $this->floatOrNull($providerPayload['tax_rate'] ?? null),
                'tax_value' => $this->floatOrNull($providerPayload['tax_value'] ?? null),
                'extras_included' => $this->arrayValue($providerPayload['extras_included'] ?? null),
                'extras_required' => $this->arrayValue($providerPayload['extras_required'] ?? null),
                'extras_available' => $this->arrayValue($providerPayload['extras_available'] ?? null),
                'pickup_station_name' => $this->stringOrNull($providerPayload['pickup_station_name'] ?? ($pickupOffice['name'] ?? null)),
                'dropoff_station_name' => $this->stringOrNull($providerPayload['dropoff_station_name'] ?? ($dropoffOffice['name'] ?? null)),
                'station' => $this->stringOrNull($providerPayload['station'] ?? ($providerPayload['pickup_station_name'] ?? ($pickupOffice['name'] ?? null))),
                'pickup_address' => $this->stringOrNull($providerPayload['pickup_address'] ?? ($pickupOffice['address'] ?? null)),
                'dropoff_address' => $this->stringOrNull($providerPayload['dropoff_address'] ?? ($dropoffOffice['address'] ?? null)),
                'pickup_office' => $pickupOffice,
                'dropoff_office' => $dropoffOffice,
                'rate_name' => $this->stringOrNull($providerPayload['rate_name'] ?? null),
                'payment_type' => $this->stringOrNull($providerPayload['payment_type'] ?? null),
                'mileage' => $this->stringOrNull($providerPayload['mileage'] ?? null),
                'fuel_policy' => $this->stringOrNull($providerPayload['fuel_policy'] ?? ($quote['policies']['fuel_policy'] ?? null)),
                'cancellation' => is_array($providerPayload['cancellation'] ?? null) ? $providerPayload['cancellation'] : ($quote['policies']['cancellation'] ?? null),
                'options' => $this->arrayValue($providerPayload['options'] ?? null),
                'insurance_options' => $this->arrayValue($providerPayload['insurance_options'] ?? null),
                'provider_gross_amount' => $this->floatOrNull($providerPayload['provider_gross_amount'] ?? null),
                'provider_net_amount' => $this->floatOrNull($providerPayload['provider_net_amount'] ?? null),
                'provider_vat_amount' => $this->floatOrNull($providerPayload['provider_vat_amount'] ?? null),
                'gateway_vehicle_id' => $this->stringOrNull($providerPayload['gateway_vehicle_id'] ?? null),
                'connector_id' => $this->stringOrNull($providerPayload['connector_id'] ?? null),
                'provider_pickup_office_id' => $this->stringOrNull($providerPayload['provider_pickup_office_id'] ?? null),
                'provider_dropoff_office_id' => $this->stringOrNull($providerPayload['provider_dropoff_office_id'] ?? null),
                'pricelist_id' => $this->stringOrNull($providerPayload['pricelist_id'] ?? null),
                'pricelist_code' => $this->stringOrNull($providerPayload['pricelist_code'] ?? null),
                'price_date' => $this->stringOrNull($providerPayload['price_date'] ?? null),
                'prepaid' => (bool) ($providerPayload['prepaid'] ?? false),
                'pricing' => [
                    'currency' => $currency,
                    'total_price' => $totalPrice,
                    'price_per_day' => $pricePerDay,
                    'deposit_amount' => $depositAmount,
                    'deposit_currency' => $this->stringOrNull($pricing['deposit_currency'] ?? ($pricing['currency'] ?? $search['currency'] ?? 'EUR')),
                    'excess_amount' => $this->floatOrNull($pricing['excess_amount'] ?? null),
                    'excess_theft_amount' => $this->floatOrNull($pricing['excess_theft_amount'] ?? null),
                ],
                'products' => $products,
                'location' => [
                    'pickup' => [
                        'name' => $pickupLocation['name'] ?? null,
                    ],
                    'dropoff' => [
                        'name' => $dropoffLocation['name'] ?? null,
                    ],
                ],
                'location_details' => $pickupLocation,
                'location_instructions' => $pickupLocation['collection_details'] ?? null,
                'full_vehicle_address' => $pickupLocation['address_1'] ?? null,
                'location_phone' => $pickupLocation['telephone'] ?? null,
                'pickup_instructions' => $pickupLocation['collection_details'] ?? null,
                'dropoff_instructions' => $dropoffLocation['dropoff_instructions'] ?? null,
                'city' => $pickupLocation['address_city'] ?? null,
                'state' => $pickupLocation['address_county'] ?? null,
                'country' => $pickupLocation['address_country'] ?? null,
                'country_code' => $pickupLocation['country_code'] ?? null,
                'location_type' => $pickupLocation['location_type'] ?? null,
                'iata_code' => $pickupLocation['iata'] ?? null,
                'latitude' => $pickupLocation['latitude'] ?? null,
                'longitude' => $pickupLocation['longitude'] ?? null,
                'provider_pickup_id' => $this->stringOrNull($pickupLocation['provider_location_id'] ?? null),
                'provider_return_id' => $this->stringOrNull($dropoffLocation['provider_location_id'] ?? ($pickupLocation['provider_location_id'] ?? null)),
                'quoteid' => $this->stringOrNull($quote['quote_id'] ?? null),
                'sipp_code' => $this->stringOrNull($vehicle['sipp_code'] ?? null),
                'transmission' => $this->stringOrNull($vehicle['transmission'] ?? null),
                'fuel' => $this->stringOrNull($vehicle['fuel_type'] ?? null),
                'seating_capacity' => $this->floatOrNull($vehicle['seats'] ?? null),
                'doors' => $this->floatOrNull($vehicle['doors'] ?? null),
                'booking_context' => [
                    'provider_payload' => [
                        ...$providerPayload,
                    ],
                ],
            ],
            'initial_package' => $this->resolveInitialPackage($products),
            'initial_protection_code' => null,
            'optional_extras' => $optionalExtras,
            'location_name' => $pickupLocation['name'] ?? null,
            'pickup_location' => $pickupLocation['name'] ?? null,
            'dropoff_location' => $dropoffLocation['name'] ?? ($pickupLocation['name'] ?? null),
            'dropoff_latitude' => $dropoffLocation['latitude'] ?? null,
            'dropoff_longitude' => $dropoffLocation['longitude'] ?? null,
            'pickup_date' => $this->stringOrNull($search['pickup_date'] ?? null),
            'pickup_time' => $this->stringOrNull($search['pickup_time'] ?? null),
            'dropoff_date' => $this->stringOrNull($search['dropoff_date'] ?? null),
            'dropoff_time' => $this->stringOrNull($search['dropoff_time'] ?? null),
            'number_of_days' => $this->calculateRentalDays(
                $this->stringOrNull($search['pickup_date'] ?? null),
                $this->stringOrNull($search['pickup_time'] ?? null),
                $this->stringOrNull($search['dropoff_date'] ?? null),
                $this->stringOrNull($search['dropoff_time'] ?? null),
            ),
            'location_instructions' => $pickupLocation['collection_details'] ?? null,
            'location_details' => $pickupLocation,
            'driver_requirements' => null,
            'terms' => [],
            'payment_percentage' => 15,
        ];
    }

    private function normalizeLocationDetails(array $details): array
    {
        return [
            'provider_location_id' => $this->stringOrNull($details['provider_location_id'] ?? null),
            'name' => $this->stringOrNull($details['name'] ?? null),
            'address_1' => $this->stringOrNull($details['address'] ?? null),
            'address_city' => $this->stringOrNull($details['city'] ?? null),
            'address_county' => $this->stringOrNull($details['state'] ?? null),
            'address_country' => $this->stringOrNull($details['country'] ?? null),
            'country_code' => $this->stringOrNull($details['country_code'] ?? null),
            'location_type' => $this->stringOrNull($details['location_type'] ?? null),
            'iata' => $this->stringOrNull($details['iata'] ?? null),
            'telephone' => $this->stringOrNull($details['phone'] ?? null),
            'collection_details' => $this->stringOrNull($details['pickup_instructions'] ?? null),
            'dropoff_instructions' => $this->stringOrNull($details['dropoff_instructions'] ?? null),
            'latitude' => $this->floatOrNull($details['latitude'] ?? null),
            'longitude' => $this->floatOrNull($details['longitude'] ?? null),
        ];
    }

    private function normalizeProducts(array $products): array
    {
        return array_values(array_filter(array_map(function ($product) {
            if (!is_array($product)) {
                return null;
            }

            return [
                'type' => $this->stringOrNull($product['type'] ?? null),
                'name' => $this->stringOrNull($product['name'] ?? null),
                'subtitle' => $this->stringOrNull($product['subtitle'] ?? null),
                'total' => $this->floatOrNull($product['total'] ?? null),
                'price_per_day' => $this->floatOrNull($product['price_per_day'] ?? null),
                'deposit' => $this->floatOrNull($product['deposit'] ?? null),
                'deposit_currency' => $this->stringOrNull($product['deposit_currency'] ?? null),
                'excess' => $this->floatOrNull($product['excess'] ?? null),
                'excess_theft_amount' => $this->floatOrNull($product['excess_theft_amount'] ?? null),
                'benefits' => is_array($product['benefits'] ?? null) ? $product['benefits'] : [],
                'currency' => $this->stringOrNull($product['currency'] ?? null),
                'is_basic' => (bool) ($product['is_basic'] ?? false),
            ];
        }, $products)));
    }

    private function normalizeOptionalExtras(array $extras): array
    {
        return array_values(array_filter(array_map(function ($extra) {
            if (!is_array($extra)) {
                return null;
            }

            return [
                'id' => $this->stringOrNull($extra['id'] ?? null),
                'name' => $this->stringOrNull($extra['name'] ?? null),
                'addon_id' => $this->stringOrNull($extra['code'] ?? ($extra['id'] ?? null)),
                'extra_name' => $this->stringOrNull($extra['name'] ?? null),
                'description' => $this->stringOrNull($extra['description'] ?? null),
                'price' => $this->floatOrNull($extra['daily_rate'] ?? null),
                'quantity' => (int) ($extra['max_quantity'] ?? 1),
                'daily_rate' => $this->floatOrNull($extra['daily_rate'] ?? null),
                'total_for_booking' => $this->floatOrNull($extra['total_for_booking'] ?? null),
                'currency' => $this->stringOrNull($extra['currency'] ?? null),
            ];
        }, $extras)));
    }

    private function buildBenefits(array $quote): array
    {
        $pricing = is_array($quote['pricing'] ?? null) ? $quote['pricing'] : [];
        $policies = is_array($quote['policies'] ?? null) ? $quote['policies'] : [];
        $cancellation = is_array($policies['cancellation'] ?? null) ? $policies['cancellation'] : [];

        return [
            'deposit_amount' => $this->floatOrNull($pricing['deposit_amount'] ?? null),
            'deposit_currency' => $this->stringOrNull($pricing['deposit_currency'] ?? ($pricing['currency'] ?? null)),
            'excess_amount' => $this->floatOrNull($pricing['excess_amount'] ?? null),
            'excess_theft_amount' => $this->floatOrNull($pricing['excess_theft_amount'] ?? null),
            'limited_km_per_day' => ($policies['mileage_policy'] ?? null) === 'limited' ? 1 : 0,
            'limited_km_per_day_range' => $this->floatOrNull($policies['mileage_limit_km'] ?? null),
            'cancellation_available_per_day' => ($cancellation['available'] ?? false) ? 1 : 0,
            'cancellation_available_per_day_date' => $this->floatOrNull($cancellation['days_before_pickup'] ?? null),
            'fuel_policy' => $this->stringOrNull($policies['fuel_policy'] ?? null),
        ];
    }

    private function buildVendorPlans(array $products): array
    {
        $vendorPlans = [];

        foreach ($products as $product) {
            if (($product['type'] ?? null) === 'BAS') {
                continue;
            }

            $vendorPlans[] = [
                'plan_type' => $product['type'] ?? null,
                'plan_description' => $product['subtitle'] ?? null,
                'price' => $product['price_per_day'] ?? null,
                'features' => is_array($product['benefits'] ?? null) ? $product['benefits'] : [],
            ];
        }

        return $vendorPlans;
    }

    private function buildImages(array $vehicle): array
    {
        $imageUrl = $this->stringOrNull($vehicle['image_url'] ?? null);

        if ($imageUrl === null) {
            return [];
        }

        return [[
            'image_type' => 'primary',
            'image_url' => $imageUrl,
        ]];
    }

    private function buildAdapterExtras(mixed $providerExtras, array $fallbackExtras): array
    {
        if (is_array($providerExtras) && $providerExtras !== []) {
            return array_values(array_filter(array_map(function ($extra, int $index) {
                if (!is_array($extra)) {
                    return null;
                }

                return [
                    'id' => $this->stringOrNull($extra['id'] ?? $extra['extraID'] ?? $extra['extraId'] ?? $extra['extra_id'] ?? $extra['code'] ?? $index),
                    'code' => $this->stringOrNull($extra['code'] ?? $extra['extraID'] ?? $extra['extraId'] ?? $extra['extra_id'] ?? $extra['id'] ?? $index),
                    'name' => $this->stringOrNull($extra['name'] ?? $extra['extra'] ?? $extra['description'] ?? $extra['displayName'] ?? $extra['code'] ?? null),
                    'description' => $this->stringOrNull($extra['description'] ?? $extra['displayDescription'] ?? null),
                    'price' => $this->floatOrNull($extra['priceWithTax'] ?? $extra['valueWithTax'] ?? $extra['value'] ?? $extra['price'] ?? $extra['amount'] ?? null),
                    'daily_rate' => $this->floatOrNull($extra['daily_rate'] ?? $extra['price_per_day'] ?? null),
                    'total_for_booking' => $this->floatOrNull($extra['total_for_booking'] ?? $extra['total_price'] ?? $extra['price'] ?? null),
                    'currency' => $this->stringOrNull($extra['currency'] ?? null),
                    'required' => (bool) ($extra['required'] ?? ($extra['extra_Required'] ?? false)),
                    'included' => (bool) ($extra['included'] ?? ($extra['extra_Included'] ?? false)),
                    'max_quantity' => (int) ($extra['max_quantity'] ?? $extra['allow_quantity'] ?? $extra['numberAllowed'] ?? 1),
                ];
            }, $providerExtras, array_keys($providerExtras))));
        }

        return $fallbackExtras;
    }

    private function resolveInitialPackage(array $products): string
    {
        foreach ($products as $product) {
            if (($product['is_basic'] ?? false) === true && !empty($product['type'])) {
                return (string) $product['type'];
            }
        }

        foreach ($products as $product) {
            if (!empty($product['type'])) {
                return (string) $product['type'];
            }
        }

        return 'BAS';
    }

    private function calculateRentalDays(?string $pickupDate, ?string $pickupTime, ?string $dropoffDate, ?string $dropoffTime): int
    {
        if ($pickupDate === null || $dropoffDate === null) {
            return 1;
        }

        $pickup = CarbonImmutable::parse(trim($pickupDate . ' ' . ($pickupTime ?? '09:00')), 'UTC');
        $dropoff = CarbonImmutable::parse(trim($dropoffDate . ' ' . ($dropoffTime ?? '09:00')), 'UTC');

        if ($dropoff->lessThanOrEqualTo($pickup)) {
            return 1;
        }

        return max(1, (int) ceil($pickup->diffInMinutes($dropoff) / 1440));
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function floatOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function arrayValue(mixed $value): array
    {
        return is_array($value) ? array_values($value) : [];
    }
}
