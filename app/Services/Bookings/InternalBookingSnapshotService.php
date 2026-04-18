<?php

namespace App\Services\Bookings;

use App\Models\Vehicle;

class InternalBookingSnapshotService
{
    public function buildForVehicle(Vehicle $vehicle, array $overrides = []): array
    {
        $vehicle->loadMissing(['vendorLocation', 'vendorProfileData', 'vendor']);

        $pickupLocation = LocationDetailsNormalizer::normalize($this->buildLocationDetails(
            $vehicle,
            $overrides['pickup_location'] ?? null,
        ));

        $dropoffLocation = LocationDetailsNormalizer::normalize($this->buildLocationDetails(
            $vehicle,
            $overrides['return_location'] ?? $overrides['pickup_location'] ?? null,
        ));

        return array_filter([
            'provider' => 'internal',
            'booking_currency' => $overrides['booking_currency'] ?? null,
            'pickup_location_id' => $vehicle->vendor_location_id,
            'dropoff_location_id' => $vehicle->vendor_location_id,
            'location' => $pickupLocation,
            'pickup_location_details' => $pickupLocation,
            'dropoff_location_details' => $dropoffLocation,
            'pickup_instructions' => $pickupLocation['pickup_instructions'] ?? null,
            'dropoff_instructions' => $dropoffLocation['dropoff_instructions'] ?? null,
            'vendor_snapshot' => array_filter([
                'name' => $vehicle->vendorProfileData?->company_name ?? $vehicle->vendor?->name,
                'email' => $vehicle->vendorProfileData?->company_email ?? $vehicle->vendor?->email,
                'phone' => $vehicle->vendorProfileData?->company_phone_number,
                'address' => $vehicle->vendorProfileData?->company_address,
            ], fn ($value) => $value !== null && $value !== ''),
        ], fn ($value) => $value !== null && $value !== []);
    }

    public function mergeMissingIntoMetadata(?array $metadata, Vehicle $vehicle, array $overrides = []): array
    {
        $metadata = is_array($metadata) ? $metadata : [];
        $snapshot = $this->buildForVehicle($vehicle, $overrides);

        $merged = $metadata;

        foreach ($snapshot as $key => $value) {
            if (empty($merged[$key])) {
                $merged[$key] = $value;
            }
        }

        foreach (['location', 'pickup_location_details', 'dropoff_location_details', 'vendor_snapshot'] as $key) {
            if (empty($metadata[$key]) && ! empty($snapshot[$key])) {
                $merged[$key] = $snapshot[$key];
            }
        }

        return $merged;
    }

    private function buildLocationDetails(Vehicle $vehicle, ?string $locationLabel = null): array
    {
        $location = $vehicle->vendorLocation;

        return array_filter([
            'name' => $locationLabel
                ?: $location?->name
                ?: $vehicle->location
                ?: $vehicle->full_vehicle_address,
            'location_name' => $location?->name ?: $vehicle->location ?: $vehicle->full_vehicle_address,
            'location_type' => $location?->location_type ?: $vehicle->location_type,
            'iata_code' => $location?->iata_code,
            'telephone' => $location?->phone ?: $vehicle->location_phone,
            'phone' => $location?->phone ?: $vehicle->location_phone,
            'pickup_instructions' => $location?->pickup_instructions ?: $vehicle->pickup_instructions,
            'dropoff_instructions' => $location?->dropoff_instructions ?: $vehicle->dropoff_instructions,
            'collection_details' => $location?->pickup_instructions ?: $vehicle->pickup_instructions,
            'return_instructions' => $location?->dropoff_instructions ?: $vehicle->dropoff_instructions,
            'address_1' => $location?->address_line_1 ?: $vehicle->full_vehicle_address,
            'address_2' => $location?->address_line_2,
            'address_city' => $location?->city ?: $vehicle->city,
            'address_county' => $location?->state ?: $vehicle->state,
            'address_country' => $location?->country ?: $vehicle->country,
            'city' => $location?->city ?: $vehicle->city,
            'country' => $location?->country ?: $vehicle->country,
            'latitude' => $location?->latitude ?? $vehicle->latitude,
            'longitude' => $location?->longitude ?? $vehicle->longitude,
        ], fn ($value) => $value !== null && $value !== '');
    }
}
