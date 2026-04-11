<?php

namespace App\Services\Skyscanner;

use App\Models\VendorLocation;
use App\Services\CountryCodeResolver;
use Illuminate\Support\Collection;

class CarHireLocationsService
{
    public function export(): array
    {
        return $this->canonicalLocations()->all();
    }

    private function canonicalLocations(): Collection
    {
        return VendorLocation::query()
            ->where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->whereHas('vehicles', function ($query) {
                $query->whereIn('status', ['active', 'available']);
            })
            ->orderByRaw("case when location_type = 'airport' then 0 else 1 end")
            ->orderBy('name')
            ->get()
            ->map(fn (VendorLocation $location) => [
                'office_id' => (string) $location->id,
                'name' => $location->name,
                'address' => $this->joinAddress([
                    $location->address_line_1,
                    $location->address_line_2,
                    $location->city,
                    $location->state,
                    $location->country,
                ]),
                'city' => $location->city,
                'state' => $location->state,
                'country' => $location->country,
                'country_code' => $this->nullableString($location->country_code)
                    ? strtoupper((string) $location->country_code)
                    : CountryCodeResolver::resolve($location->country),
                'location_type' => strtolower((string) $location->location_type),
                'iata' => $this->nullableString($location->iata_code),
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'phone' => $this->nullableString($location->phone),
                'pickup_instructions' => $this->nullableString($location->pickup_instructions),
                'dropoff_instructions' => $this->nullableString($location->dropoff_instructions),
            ])
            ->values();
    }

    private function joinAddress(array $parts): ?string
    {
        $parts = array_values(array_filter(array_map(
            fn ($value) => $this->nullableString($value),
            $parts
        )));

        if ($parts === []) {
            return null;
        }

        return implode(', ', $parts);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}
