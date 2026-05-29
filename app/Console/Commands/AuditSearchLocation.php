<?php

namespace App\Console\Commands;

use App\Models\Vehicle;
use App\Services\LocationSearchService;
use App\Services\Search\InternalLocationResolver;
use App\Services\VrooemGatewayService;
use Illuminate\Console\Command;

class AuditSearchLocation extends Command
{
    protected $signature = 'search:audit-location {unified_location_id : Gateway unified location id}';

    protected $description = 'Audit gateway provider mappings and Laravel internal fleet matching for a search location.';

    public function handle(
        VrooemGatewayService $gatewayService,
        LocationSearchService $locationSearchService,
        InternalLocationResolver $internalLocationResolver
    ): int {
        $unifiedLocationId = (int) $this->argument('unified_location_id');
        if ($unifiedLocationId <= 0) {
            $this->error('unified_location_id must be a positive integer.');

            return self::FAILURE;
        }

        $rawLocation = $gatewayService->getLocation($unifiedLocationId);
        if (! is_array($rawLocation)) {
            $this->error("Gateway location {$unifiedLocationId} was not found.");

            return self::FAILURE;
        }

        $resolvedLocation = $locationSearchService->getLocationByUnifiedId($unifiedLocationId);
        if (! is_array($resolvedLocation)) {
            $this->error("Laravel could not resolve location {$unifiedLocationId}.");

            return self::FAILURE;
        }

        $internalLocation = $internalLocationResolver->resolveForUnifiedLocation($resolvedLocation);
        $internalVehicleCount = $internalLocation
            ? Vehicle::query()
                ->where('vendor_location_id', $internalLocation->id)
                ->whereIn('status', Vehicle::searchableStatuses())
                ->count()
            : 0;

        $rawProviders = $this->providerList($rawLocation);
        $verifiedProviders = $this->providerList($resolvedLocation);
        $externalProviders = array_values(array_filter(
            $verifiedProviders,
            fn (string $provider): bool => $provider !== 'internal'
        ));

        $this->line('Location');
        $this->table(
            ['Field', 'Value'],
            [
                ['Unified ID', (string) $unifiedLocationId],
                ['Name', (string) ($resolvedLocation['name'] ?? $rawLocation['name'] ?? '')],
                ['IATA', (string) ($resolvedLocation['iata'] ?? $rawLocation['iata'] ?? '')],
                ['Country code', (string) ($resolvedLocation['country_code'] ?? $rawLocation['country_code'] ?? '')],
                ['Gateway providers', implode(', ', $rawProviders) ?: 'none'],
                ['Verified providers', implode(', ', $verifiedProviders) ?: 'none'],
                ['Gateway-bound providers', implode(', ', $externalProviders) ?: 'none'],
                ['Internal pickup id', $internalLocation ? (string) $internalLocation->id : 'none'],
                ['Internal active vehicles', (string) $internalVehicleCount],
            ]
        );

        $warnings = [];
        if (in_array('internal', $rawProviders, true) && ! in_array('internal', $verifiedProviders, true)) {
            $warnings[] = 'Gateway has an internal provider row, but Laravel rejected it as stale or invalid.';
        }
        if (! in_array('internal', $rawProviders, true) && in_array('internal', $verifiedProviders, true)) {
            $warnings[] = 'Gateway is missing internal, but Laravel found a verified internal fleet match.';
        }
        if (in_array('internal', $verifiedProviders, true) && $internalVehicleCount === 0) {
            $warnings[] = 'Internal provider is present but no searchable vehicles were found.';
        }
        if ($externalProviders === []) {
            $warnings[] = 'No external provider mappings will be sent to gateway for this location.';
        }

        if ($warnings !== []) {
            $this->warn('Warnings');
            foreach ($warnings as $warning) {
                $this->warn('- '.$warning);
            }
        } else {
            $this->info('No integrity warnings found.');
        }

        return self::SUCCESS;
    }

    private function providerList(array $location): array
    {
        return collect($location['providers'] ?? [])
            ->filter(fn ($provider) => is_array($provider))
            ->map(fn (array $provider) => strtolower(trim((string) ($provider['provider'] ?? ''))))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
