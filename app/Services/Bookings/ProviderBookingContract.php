<?php

namespace App\Services\Bookings;

class ProviderBookingContract
{
    private const SOURCE_ALIASES = [
        'adobe' => 'adobe_car',
        'adobe_car' => 'adobe_car',
        'green_motion' => 'greenmotion',
        'greenmotion' => 'greenmotion',
        'record_go' => 'recordgo',
        'recordgo' => 'recordgo',
    ];

    public function validateCheckout(array $validated): array
    {
        $vehicle = is_array($validated['vehicle'] ?? null) ? $validated['vehicle'] : [];
        $source = $this->normalizeSource($vehicle['source'] ?? null);

        if ($source === '' || $source === 'internal') {
            return ['valid' => true, 'missing_fields' => []];
        }

        $missing = $this->missingCommonExternalFields($validated, $vehicle);

        $missing = array_merge($missing, match ($source) {
            'greenmotion', 'usave' => $this->missingGreenMotionFields($validated),
            'recordgo' => $this->missingRecordGoFields($validated),
            'sicily_by_car' => $this->missingSicilyByCarFields($validated),
            'surprice' => $this->missingSurpriceFields($validated),
            'renteon' => $this->missingRenteonFields($validated),
            'ok_mobility' => $this->missingOkMobilityFields($validated),
            default => [],
        });

        $missing = array_values(array_unique($missing));

        if ($missing === []) {
            return ['valid' => true, 'missing_fields' => []];
        }

        return [
            'valid' => false,
            'code' => 'PROVIDER_BOOKING_CONTEXT_MISSING',
            'message' => 'This supplier quote is missing reservation details. Please refresh search results and try again.',
            'missing_fields' => $missing,
        ];
    }

    public function gatewayVehicleId(array $validated): ?string
    {
        $vehicle = is_array($validated['vehicle'] ?? null) ? $validated['vehicle'] : [];

        return $this->selectedGatewayVehicleId($vehicle, $validated['package'] ?? null);
    }

    private function missingCommonExternalFields(array $validated, array $vehicle): array
    {
        $required = [
            'vehicle.source' => $vehicle['source'] ?? null,
            'vehicle.gateway_vehicle_id' => $this->selectedGatewayVehicleId($vehicle, $validated['package'] ?? null),
            'gateway_search_id' => $validated['gateway_search_id'] ?? null,
            'customer.name' => $validated['customer']['name'] ?? null,
            'customer.email' => $validated['customer']['email'] ?? null,
            'customer.phone' => $validated['customer']['phone'] ?? null,
            'customer.driver_age' => $validated['customer']['driver_age'] ?? null,
        ];

        return $this->missing($required);
    }

    private function missingGreenMotionFields(array $validated): array
    {
        return $this->missing([
            'customer.driver_license_number' => $validated['customer']['driver_license_number'] ?? null,
            'customer.address' => $validated['customer']['address'] ?? null,
            'customer.city' => $validated['customer']['city'] ?? null,
            'customer.postal_code' => $validated['customer']['postal_code'] ?? null,
            'customer.country' => $validated['customer']['country'] ?? null,
            'quoteid' => $validated['quoteid'] ?? ($validated['vehicle']['quoteid'] ?? null),
            'package' => $validated['package'] ?? null,
        ]);
    }

    private function missingRecordGoFields(array $validated): array
    {
        $vehicle = $validated['vehicle'] ?? [];
        $product = $vehicle['recordgo_selected_product'] ?? null;

        return $this->missing([
            'vehicle.provider_pickup_id' => $vehicle['provider_pickup_id'] ?? null,
            'vehicle.provider_dropoff_id' => $vehicle['provider_dropoff_id'] ?? ($vehicle['provider_return_id'] ?? null),
            'vehicle.sipp_code' => $vehicle['sipp_code'] ?? null,
            'vehicle.recordgo_sellcode_ver' => $vehicle['recordgo_sellcode_ver'] ?? null,
            'recordgo.product_id' => is_array($product) ? ($product['product_id'] ?? null) : null,
            'recordgo.product_ver' => is_array($product) ? ($product['product_ver'] ?? null) : null,
            'recordgo.rate_prod_ver' => is_array($product) ? ($product['rate_prod_ver'] ?? null) : null,
        ]);
    }

    private function missingSicilyByCarFields(array $validated): array
    {
        $vehicle = $validated['vehicle'] ?? [];

        return $this->missing([
            'vehicle.provider_pickup_id' => $vehicle['provider_pickup_id'] ?? null,
            'vehicle.provider_vehicle_id' => $vehicle['provider_vehicle_id'] ?? null,
            'vehicle.rate_id' => $vehicle['rate_id'] ?? null,
        ]);
    }

    private function missingSurpriceFields(array $validated): array
    {
        $vehicle = $validated['vehicle'] ?? [];
        $supplierData = is_array($vehicle['supplier_data'] ?? null) ? $vehicle['supplier_data'] : [];
        $useFdw = ($validated['package'] ?? null) === 'FDW';
        $pickupId = $this->firstFilled($vehicle['provider_pickup_id'] ?? null, $supplierData['pickup_code'] ?? null);
        $dropoffId = $this->firstFilled(
            $vehicle['provider_return_id'] ?? null,
            $vehicle['provider_dropoff_id'] ?? null,
            $supplierData['dropoff_code'] ?? null,
            $pickupId
        );

        return $this->missing([
            'vehicle.surprice_vendor_rate_id' => $useFdw
                ? ($supplierData['fdw_vendor_rate_id'] ?? null)
                : ($vehicle['surprice_vendor_rate_id']
                    ?? $vehicle['provider_rate_id']
                    ?? ($supplierData['vendor_rate_id'] ?? null)),
            'vehicle.surprice_rate_code' => $useFdw
                ? ($supplierData['fdw_rate_code'] ?? null)
                : ($vehicle['surprice_rate_code'] ?? ($supplierData['rate_code'] ?? null)),
            'vehicle.provider_pickup_id' => $pickupId,
            'vehicle.provider_return_id' => $dropoffId,
        ]);
    }

    private function missingRenteonFields(array $validated): array
    {
        $context = $this->selectedProductContext($validated['vehicle'] ?? [], $validated['package'] ?? null);

        return $this->missing([
            'vehicle.connector_id' => $context['connector_id'] ?? null,
            'vehicle.provider_pickup_office_id' => $context['provider_pickup_office_id'] ?? null,
            'vehicle.provider_dropoff_office_id' => $context['provider_dropoff_office_id'] ?? null,
            'vehicle.pricelist_id' => $context['pricelist_id'] ?? null,
            'vehicle.price_date' => $context['price_date'] ?? null,
        ]);
    }

    private function missingOkMobilityFields(array $validated): array
    {
        $vehicle = $validated['vehicle'] ?? [];
        $supplierData = is_array($vehicle['supplier_data'] ?? null) ? $vehicle['supplier_data'] : [];

        return $this->missing([
            'vehicle.ok_mobility_token' => $vehicle['ok_mobility_token'] ?? ($supplierData['token'] ?? null),
            'vehicle.ok_mobility_group_id' => $vehicle['ok_mobility_group_id'] ?? ($supplierData['group_id'] ?? null),
            'vehicle.ok_mobility_rate_code' => $vehicle['ok_mobility_rate_code'] ?? ($supplierData['rate_code'] ?? null),
        ]);
    }

    private function selectedGatewayVehicleId(array $vehicle, ?string $package): ?string
    {
        $context = $this->selectedProductContext($vehicle, $package);

        return $this->firstFilled(
            $context['gateway_vehicle_id'] ?? null,
            $vehicle['gateway_vehicle_id'] ?? null
        );
    }

    private function selectedProductContext(array $vehicle, ?string $package): array
    {
        $selected = $this->selectedProduct($vehicle, $package);
        if (! $selected) {
            return $vehicle;
        }

        return array_merge($vehicle, array_filter($selected, static fn ($value) => $value !== null && $value !== ''));
    }

    private function selectedProduct(array $vehicle, ?string $package): ?array
    {
        if (empty($vehicle['products']) || ! is_array($vehicle['products'])) {
            return null;
        }

        foreach ($vehicle['products'] as $product) {
            if (is_array($product) && $package !== null && ($product['type'] ?? null) === $package) {
                return $product;
            }
        }

        foreach ($vehicle['products'] as $product) {
            if (is_array($product)) {
                return $product;
            }
        }

        return null;
    }

    private function missing(array $fields): array
    {
        return collect($fields)
            ->filter(fn ($value) => $value === null || trim((string) $value) === '')
            ->keys()
            ->values()
            ->all();
    }

    private function normalizeSource(?string $source): string
    {
        $source = strtolower(trim((string) $source));

        return self::SOURCE_ALIASES[$source] ?? $source;
    }

    private function firstFilled(...$values): ?string
    {
        foreach ($values as $value) {
            if ($value === null) {
                continue;
            }

            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }
}
