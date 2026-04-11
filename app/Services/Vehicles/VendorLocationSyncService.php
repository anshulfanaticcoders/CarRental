<?php

namespace App\Services\Vehicles;

use App\Models\VendorLocation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class VendorLocationSyncService
{
    private ?Collection $countries = null;

    public function sync(int $vendorId, array $attributes, ?int $currentVendorLocationId = null): ?VendorLocation
    {
        $name = $this->nullableString($attributes['location'] ?? null);
        $locationType = $this->normalizeLocationType($attributes['location_type'] ?? null);
        $latitude = $this->toFloat($attributes['latitude'] ?? null);
        $longitude = $this->toFloat($attributes['longitude'] ?? null);

        if ($name === null || $locationType === null || $latitude === null || $longitude === null) {
            return null;
        }

        $location = null;
        $vendorLocationId = $attributes['vendor_location_id'] ?? $currentVendorLocationId;

        if ($vendorLocationId) {
            $location = VendorLocation::query()
                ->where('vendor_id', $vendorId)
                ->find($vendorLocationId);
        }

        if (!$location) {
            $location = VendorLocation::firstOrNew([
                'vendor_id' => $vendorId,
                'name' => $name,
                'location_type' => $locationType,
                'city' => $this->nullableString($attributes['city'] ?? null) ?? '',
                'country' => $this->nullableString($attributes['country'] ?? null) ?? '',
            ]);
        }

        $location->fill([
            'vendor_id' => $vendorId,
            'name' => $name,
            'code' => $this->nullableString($attributes['location_code'] ?? null),
            'address_line_1' => $this->resolveAddressLine($attributes),
            'address_line_2' => $this->nullableString($attributes['address_line_2'] ?? null),
            'city' => $this->nullableString($attributes['city'] ?? null) ?? '',
            'state' => $this->nullableString($attributes['state'] ?? null),
            'country' => $this->nullableString($attributes['country'] ?? null) ?? '',
            'country_code' => $this->resolveCountryCode($attributes),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'location_type' => $locationType,
            'iata_code' => $this->resolveIataCode($attributes),
            'phone' => $this->nullableString($attributes['location_phone'] ?? null),
            'pickup_instructions' => $this->nullableString($attributes['pickup_instructions'] ?? null),
            'dropoff_instructions' => $this->nullableString($attributes['dropoff_instructions'] ?? null),
            'is_active' => true,
        ]);

        $location->save();

        return $location;
    }

    public function resolveSelectedLocation(int $vendorId, ?int $vendorLocationId): ?VendorLocation
    {
        if (!$vendorLocationId) {
            return null;
        }

        return VendorLocation::query()
            ->where('vendor_id', $vendorId)
            ->where('is_active', true)
            ->find($vendorLocationId);
    }

    public function vehicleLocationAttributes(VendorLocation $location): array
    {
        $locationType = Str::of((string) $location->location_type)
            ->replace(['-', '_'], ' ')
            ->trim()
            ->title()
            ->value();

        return [
            'vendor_location_id' => $location->id,
            'location' => $location->name,
            'location_type' => $locationType === '' ? 'Downtown' : $locationType,
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
            'city' => $location->city,
            'state' => $location->state,
            'country' => $location->country,
            'full_vehicle_address' => $location->address_line_1,
            'location_phone' => $location->phone,
            'pickup_instructions' => $location->pickup_instructions,
            'dropoff_instructions' => $location->dropoff_instructions,
        ];
    }

    private function resolveAddressLine(array $attributes): string
    {
        return $this->nullableString($attributes['full_vehicle_address'] ?? null)
            ?? $this->nullableString($attributes['location'] ?? null)
            ?? 'Unknown location';
    }

    private function resolveCountryCode(array $attributes): string
    {
        $countryCode = $this->nullableString($attributes['country_code'] ?? null);
        if ($countryCode !== null) {
            return strtoupper($countryCode);
        }

        $country = $this->nullableString($attributes['country'] ?? null);
        if ($country === null) {
            return 'UN';
        }

        $match = $this->countries()->first(function (array $entry) use ($country) {
            return mb_strtolower((string) ($entry['name'] ?? '')) === mb_strtolower($country);
        });

        return strtoupper((string) ($match['code'] ?? 'UN'));
    }

    private function resolveIataCode(array $attributes): ?string
    {
        $explicit = $this->nullableString($attributes['iata_code'] ?? null);
        if ($explicit !== null) {
            return strtoupper($explicit);
        }

        $name = $this->nullableString($attributes['location'] ?? null);
        if ($name === null) {
            return null;
        }

        if (preg_match('/\(([A-Z]{3})\)/', $name, $matches) === 1) {
            return strtoupper($matches[1]);
        }

        return null;
    }

    private function countries(): Collection
    {
        if ($this->countries !== null) {
            return $this->countries;
        }

        $path = public_path('countries.json');
        if (!is_file($path)) {
            return $this->countries = collect();
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return $this->countries = collect(is_array($decoded) ? $decoded : []);
    }

    private function normalizeLocationType(mixed $value): ?string
    {
        $value = $this->nullableString($value);

        return $value === null ? null : strtolower($value);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
