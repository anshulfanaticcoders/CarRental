<?php

namespace App\Services\Skyscanner;

use App\Models\VendorLocation;
use App\Services\CountryCodeResolver;
use App\Services\LocationSearchService;
use Illuminate\Support\Collection;

class CarHireLocationsService
{
    public function __construct(
        private readonly LocationSearchService $locationSearchService,
    ) {
    }

    public function export(): array
    {
        if ($this->usesUnifiedLocationInventory()) {
            return $this->unifiedLocations()->all();
        }

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

    private function unifiedLocations(): Collection
    {
        return collect($this->locationSearchService->getAllLocations(1000))
            ->filter(fn ($location) => is_array($location) && $this->supportsSkyscannerLocation($location))
            ->sortBy(fn (array $location) => sprintf(
                '%d|%s',
                strtolower((string) ($location['location_type'] ?? '')) === 'airport' ? 0 : 1,
                strtolower((string) ($location['name'] ?? ''))
            ))
            ->values()
            ->map(function (array $location): array {
                $country = $this->nullableString($location['country'] ?? null);
                $countryCode = $this->nullableString($location['country_code'] ?? null);

                return [
                    'office_id' => (string) ($location['unified_location_id'] ?? ''),
                    'name' => $this->nullableString($location['name'] ?? null),
                    'address' => $this->joinAddress([
                        $location['address'] ?? null,
                        $location['city'] ?? null,
                        $location['state'] ?? null,
                        $country,
                    ]),
                    'city' => $this->nullableString($location['city'] ?? null),
                    'state' => $this->nullableString($location['state'] ?? null),
                    'country' => $country,
                    'country_code' => $countryCode !== null
                        ? strtoupper($countryCode)
                        : CountryCodeResolver::resolve($country),
                    'location_type' => strtolower((string) ($location['location_type'] ?? 'unknown')),
                    'iata' => $this->nullableString($location['iata'] ?? null),
                    'latitude' => $location['latitude'] ?? null,
                    'longitude' => $location['longitude'] ?? null,
                    'phone' => null,
                    'pickup_instructions' => null,
                    'dropoff_instructions' => null,
                ];
            })
            ->filter(fn (array $location) => $location['office_id'] !== '' && $location['name'] !== null);
    }

    private function supportsSkyscannerLocation(array $location): bool
    {
        $providers = collect($location['providers'] ?? [])
            ->filter(fn ($provider) => is_array($provider));

        if ($providers->isEmpty()) {
            return false;
        }

        if (!$this->whitelistedProviders()->isEmpty()) {
            $providers = $providers->filter(function (array $provider): bool {
                $providerName = strtolower(trim((string) ($provider['provider'] ?? '')));

                return $providerName === 'internal' || $this->whitelistedProviders()->contains($providerName);
            });
        }

        return $providers->isNotEmpty();
    }

    private function usesUnifiedLocationInventory(): bool
    {
        return in_array((string) config('skyscanner.inventory_scope', 'internal'), ['providers', 'mixed'], true);
    }

    private function whitelistedProviders(): Collection
    {
        return collect(config('skyscanner.provider_whitelist', []))
            ->map(function ($provider) {
                $provider = strtolower(trim((string) ($provider ?? '')));

                return $provider === '' ? null : $provider;
            })
            ->filter()
            ->values();
    }

    private function joinAddress(array $parts): ?string
    {
        $segments = [];
        $seen = [];

        foreach ($parts as $value) {
            $value = $this->nullableString($value);

            if ($value === null) {
                continue;
            }

            foreach (preg_split('/\s*,\s*/', $value) ?: [] as $segment) {
                $segment = $this->nullableString($segment);

                if ($segment === null) {
                    continue;
                }

                $key = mb_strtolower($segment);
                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $segments[] = $segment;
            }
        }

        if ($segments === []) {
            return null;
        }

        return implode(', ', $segments);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}
