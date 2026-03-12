<?php

namespace App\Services\Locations\Fetchers;

use App\Models\Vehicle;
use App\Services\LocationSearchService;

class InternalLocationFetcher extends AbstractProviderLocationFetcher
{
    public function __construct(
        private readonly LocationSearchService $locationSearchService
    ) {
    }

    public function key(): string
    {
        return 'internal';
    }

    public function fetch(): array
    {
        return Vehicle::select('city', 'state', 'country', 'latitude', 'longitude', 'location', 'location_type')
            ->whereNotNull('city')
            ->whereNotNull('country')
            ->get()
            ->map(function ($vehicle) {
                $label = $vehicle->city;
                if (!empty($vehicle->location_type)) {
                    $label .= ' ' . $vehicle->location_type;
                } elseif (!empty($vehicle->location)) {
                    $label = $vehicle->location;
                }

                return [
                    'id' => 'internal_' . md5($vehicle->city . $vehicle->state . $vehicle->country . $vehicle->location),
                    'provider_location_id' => 'internal_' . md5($vehicle->city . $vehicle->state . $vehicle->country . $vehicle->location),
                    'label' => $label,
                    'below_label' => implode(', ', array_filter([$vehicle->city, $vehicle->state, $vehicle->country])),
                    'location' => $vehicle->location,
                    'city' => $vehicle->city,
                    'state' => $vehicle->state,
                    'country' => $vehicle->country,
                    'latitude' => (float) $vehicle->latitude,
                    'longitude' => (float) $vehicle->longitude,
                    'location_type' => $vehicle->location_type,
                    'source' => 'internal',
                    'matched_field' => 'location',
                ];
            })
            ->unique(function ($item) {
                return $this->locationSearchService->normalizeString($item['label']) .
                    $this->locationSearchService->normalizeString($item['below_label']);
            })
            ->values()
            ->toArray();
    }
}
