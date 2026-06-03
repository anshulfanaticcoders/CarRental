<?php

namespace App\Console\Commands;

use App\Models\Vehicle;
use App\Services\LocationSearchService;
use App\Services\Search\InternalLocationResolver;
use App\Services\Vehicles\GatewayVehicleTransformer;
use App\Services\VrooemGatewayService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AuditSearchProviderParity extends Command
{
    protected $signature = 'search:audit-provider-parity
        {--providers=* : Provider IDs to audit}
        {--date-from= : Pickup date, defaults to 2027-safe future date}
        {--date-to= : Dropoff date, defaults to pickup + 4 days}
        {--start-time=09:00 : Pickup time}
        {--end-time=09:00 : Dropoff time}
        {--dropoff-unified-location-id= : Optional dropoff unified location ID for one-way provider audits}
        {--currency=EUR : Search currency}
        {--age=35 : Driver age}
        {--scan-limit=5000 : Gateway locations to scan for provider mappings}
        {--candidate-limit=25 : Provider location candidates to try before reporting no vehicles}
        {--vehicles-per-provider=3 : Vehicle samples to summarize per provider}
        {--output= : Output directory for raw captures}';

    protected $description = 'Audit gateway raw search data against Laravel transformed vehicle data for all enabled providers.';

    public function handle(
        VrooemGatewayService $gatewayService,
        LocationSearchService $locationSearchService,
        InternalLocationResolver $internalLocationResolver,
        GatewayVehicleTransformer $transformer
    ): int {
        $dateFrom = $this->resolveDateFrom();
        $dateTo = $this->resolveDateTo($dateFrom);
        $dropoffUnifiedLocationId = $this->resolveOptionalInt('dropoff-unified-location-id');
        $rentalDays = max(1, $dateFrom->diffInDays($dateTo));
        $outputDir = $this->resolveOutputDir();
        File::ensureDirectoryExists($outputDir);

        $supplierPayload = $gatewayService->listSuppliers();
        $this->writeJson($outputDir.'/gateway-suppliers.json', $supplierPayload);

        $requestedProviders = collect((array) $this->option('providers'))
            ->map(fn ($provider) => $this->normalizeProviderId($provider))
            ->filter()
            ->values();

        $suppliers = collect($supplierPayload['suppliers'] ?? [])
            ->filter(fn ($supplier) => is_array($supplier))
            ->filter(fn (array $supplier): bool => (bool) ($supplier['enabled'] ?? false) && (bool) ($supplier['has_adapter'] ?? false))
            ->when($requestedProviders->isNotEmpty(), fn ($items) => $items->filter(
                fn (array $supplier): bool => $requestedProviders->contains($this->normalizeProviderId($supplier['id'] ?? ''))
            ))
            ->values();

        if ($suppliers->isEmpty()) {
            $this->error('No enabled gateway suppliers with adapters were found.');

            return self::FAILURE;
        }

        $locations = $locationSearchService->getAllLocations((int) $this->option('scan-limit'));
        $rows = [];
        $gapEmails = [];

        foreach ($suppliers as $supplier) {
            $supplierId = (string) ($supplier['id'] ?? '');
            $row = $supplierId === 'internal'
                ? $this->auditInternalProvider($supplier, $locations, $internalLocationResolver, $outputDir)
                : $this->auditGatewayProvider($supplier, $locations, $locationSearchService, $gatewayService, $transformer, $dateFrom, $dateTo, $dropoffUnifiedLocationId, $rentalDays, $outputDir);

            $rows[] = $row;
            if (($row['gaps'] ?? []) !== []) {
                $gapEmails[] = $this->gapEmailBlock($row);
            }
        }

        $summary = [
            'generated_at' => now()->toIso8601String(),
            'search' => [
                'pickup_date' => $dateFrom->toDateString(),
                'dropoff_date' => $dateTo->toDateString(),
                'pickup_time' => (string) $this->option('start-time'),
                'dropoff_time' => (string) $this->option('end-time'),
                'dropoff_unified_location_id' => $dropoffUnifiedLocationId,
                'currency' => strtoupper((string) $this->option('currency')),
                'driver_age' => (int) $this->option('age'),
            ],
            'providers' => $rows,
        ];

        $this->writeJson($outputDir.'/summary.json', $summary);
        File::put($outputDir.'/provider-data-gap-email.md', implode("\n\n---\n\n", $gapEmails));

        $this->table(
            ['Provider', 'Status', 'Location', 'Raw', 'Mapped', 'Packages', 'Protection', 'Extras', 'Qty Extras', 'Price Warnings', 'Gaps'],
            collect($rows)->map(fn (array $row): array => [
                $row['provider'],
                $row['status'],
                $row['location_name'] ?? 'none',
                (string) ($row['raw_vehicle_count'] ?? 0),
                (string) ($row['transformed_vehicle_count'] ?? 0),
                (string) ($row['package_count'] ?? 0),
                (string) ($row['protection_count'] ?? 0),
                (string) ($row['extras_count'] ?? 0),
                (string) ($row['quantity_extra_count'] ?? 0),
                (string) ($row['pricing_warning_count'] ?? 0),
                implode('; ', $row['gaps'] ?? []),
            ])->all()
        );

        $this->info('Audit written to '.$outputDir);

        return self::SUCCESS;
    }

    private function auditGatewayProvider(
        array $supplier,
        array $locations,
        LocationSearchService $locationSearchService,
        VrooemGatewayService $gatewayService,
        GatewayVehicleTransformer $transformer,
        CarbonImmutable $dateFrom,
        CarbonImmutable $dateTo,
        ?int $dropoffUnifiedLocationId,
        int $rentalDays,
        string $outputDir
    ): array {
        $supplierId = (string) ($supplier['id'] ?? '');
        $locations = $this->locationsWithProviderHints($supplierId, $locations, $locationSearchService);
        $candidates = $this->findProviderLocations($locations, $supplierId);

        if ($candidates === []) {
            return $this->baseRow($supplier, [
                'status' => 'no_location_mapping',
                'gaps' => ['No unified location mapping found in scanned gateway locations.'],
            ]);
        }

        $attempts = [];
        $location = null;
        $providerEntry = null;
        $params = [];
        $raw = [];
        $candidateLimit = max(1, (int) $this->option('candidate-limit'));

        foreach (array_slice($candidates, 0, $candidateLimit) as $candidate) {
            [$candidateLocation, $candidateEntry] = $candidate;
            $providerForGateway = strtolower((string) ($candidateEntry['provider'] ?? $supplierId));
            $candidateParams = [
                'unified_location_id' => (int) $candidateLocation['unified_location_id'],
                'pickup_date' => $dateFrom->toDateString(),
                'dropoff_date' => $dateTo->toDateString(),
                'pickup_time' => (string) $this->option('start-time'),
                'dropoff_time' => (string) $this->option('end-time'),
                'driver_age' => (int) $this->option('age'),
                'currency' => strtoupper((string) $this->option('currency')),
                'providers' => $providerForGateway,
                'provider_locations' => [$candidateEntry],
                'country_code' => $candidateLocation['country_code'] ?? null,
            ];
            if ($dropoffUnifiedLocationId !== null) {
                $candidateParams['dropoff_unified_location_id'] = $dropoffUnifiedLocationId;
            }

            $candidateRaw = $gatewayService->searchVehicles($candidateParams);
            $gatewayError = $gatewayService->getLastError();
            $vehicleCount = count($candidateRaw['vehicles'] ?? []);
            $attempts[] = [
                'unified_location_id' => $candidateLocation['unified_location_id'] ?? null,
                'location_name' => $candidateLocation['name'] ?? null,
                'pickup_id' => $candidateEntry['pickup_id'] ?? null,
                'vehicle_count' => $vehicleCount,
                'supplier_results' => $candidateRaw['supplier_results'] ?? [],
                'provider_status' => $candidateRaw['provider_status'] ?? [],
                'gateway_error' => $gatewayError,
            ];

            if ($raw === [] || $vehicleCount > 0) {
                $location = $candidateLocation;
                $providerEntry = $candidateEntry;
                $params = $candidateParams;
                $raw = $candidateRaw;
            }

            if ($vehicleCount > 0) {
                break;
            }
        }

        $safeProvider = $this->safeFilename($supplierId);
        $this->writeJson($outputDir."/raw-gateway-{$safeProvider}.json", [
            'request' => $params,
            'attempts' => $attempts,
            'response' => $raw,
        ]);

        $transformErrors = [];
        $transformed = collect($raw['vehicles'] ?? [])
            ->filter(fn ($vehicle) => is_array($vehicle))
            ->map(function (array $vehicle) use ($transformer, $rentalDays, &$transformErrors) {
                try {
                    return $transformer->transform($vehicle, $rentalDays);
                } catch (\Throwable $e) {
                    $transformErrors[] = [
                        'vehicle_id' => $vehicle['id'] ?? null,
                        'error' => $e->getMessage(),
                    ];

                    return null;
                }
            })
            ->filter()
            ->values()
            ->all();

        $this->writeJson($outputDir."/laravel-transformed-{$safeProvider}.json", $transformed);

        $analysis = $this->analyzeProviderData($raw, $transformed);
        $gaps = $analysis['gaps'];
        $gatewayErrorCount = collect($attempts)->filter(fn (array $attempt): bool => ! empty($attempt['gateway_error']))->count();
        $providerErrorCount = collect($attempts)->filter(function (array $attempt): bool {
            $supplierErrors = collect($attempt['supplier_results'] ?? [])
                ->contains(fn ($result): bool => is_array($result) && trim((string) ($result['error'] ?? '')) !== '');
            $providerFailures = collect($attempt['provider_status'] ?? [])
                ->contains(fn ($status): bool => is_array($status) && trim((string) ($status['message'] ?? '')) !== '');

            return $supplierErrors || $providerFailures;
        })->count();
        if ($transformErrors !== []) {
            $gaps[] = count($transformErrors).' vehicle transform error(s).';
        }
        if ($gatewayErrorCount > 0) {
            $gaps[] = $gatewayErrorCount.' gateway request error(s); see raw capture attempts.';
        }
        if ($providerErrorCount > 0) {
            $gaps[] = $providerErrorCount.' provider search error(s); see supplier_results/provider_status in raw capture.';
        }
        if (($raw['vehicles'] ?? []) === [] && $gatewayErrorCount === 0 && $providerErrorCount === 0) {
            $gaps[] = 'Provider returned no vehicles for the sampled location/date.';
        }

        return $this->baseRow($supplier, [
            'status' => ($raw['vehicles'] ?? []) === []
                ? ($gatewayErrorCount > 0 ? 'gateway_error' : ($providerErrorCount > 0 ? 'provider_error' : 'no_vehicles'))
                : ($transformErrors === [] ? 'ok' : 'transform_warnings'),
            'location_id' => $location['unified_location_id'] ?? null,
            'location_name' => $location['name'] ?? null,
            'provider_pickup_id' => $providerEntry['pickup_id'] ?? null,
            'candidate_attempts' => $attempts,
            'gateway_error_count' => $gatewayErrorCount,
            'provider_error_count' => $providerErrorCount,
            'raw_vehicle_count' => count($raw['vehicles'] ?? []),
            'transformed_vehicle_count' => count($transformed),
            'supplier_results' => $raw['supplier_results'] ?? [],
            'provider_status' => $raw['provider_status'] ?? [],
            'transform_errors' => $transformErrors,
            'gaps' => array_values(array_unique($gaps)),
        ] + $analysis);
    }

    private function auditInternalProvider(
        array $supplier,
        array $locations,
        InternalLocationResolver $internalLocationResolver,
        string $outputDir
    ): array {
        foreach ($locations as $location) {
            $internalLocation = $internalLocationResolver->resolveForUnifiedLocation($location);
            if (! $internalLocation) {
                continue;
            }

            $count = Vehicle::query()
                ->where('vendor_location_id', $internalLocation->id)
                ->whereIn('status', Vehicle::searchableStatuses())
                ->count();

            $this->writeJson($outputDir.'/internal-location.json', [
                'gateway_location' => $location,
                'internal_location_id' => $internalLocation->id,
                'searchable_vehicle_count' => $count,
            ]);

            return $this->baseRow($supplier, [
                'status' => $count > 0 ? 'ok' : 'no_vehicles',
                'location_id' => $location['unified_location_id'] ?? null,
                'location_name' => $location['name'] ?? null,
                'provider_pickup_id' => (string) $internalLocation->id,
                'raw_vehicle_count' => $count,
                'transformed_vehicle_count' => $count,
                'gaps' => $count > 0 ? [] : ['Internal location resolved but has no searchable vehicles.'],
            ]);
        }

        return $this->baseRow($supplier, [
            'status' => 'no_location_mapping',
            'gaps' => ['No verified Laravel internal location with searchable vehicles found in scanned gateway locations.'],
        ]);
    }

    private function analyzeProviderData(array $raw, array $transformed): array
    {
        $rawVehicles = collect($raw['vehicles'] ?? [])->filter(fn ($vehicle) => is_array($vehicle))->values();
        $mapped = collect($transformed)->filter(fn ($vehicle) => is_array($vehicle))->values();
        $pricingWarnings = [];

        foreach ($rawVehicles->zip($mapped) as $pair) {
            [$rawVehicle, $mappedVehicle] = $pair;
            if (! is_array($rawVehicle) || ! is_array($mappedVehicle)) {
                continue;
            }

            $rawTotal = data_get($rawVehicle, 'pricing.total_price');
            $mappedTotal = data_get($mappedVehicle, 'pricing.total_price', $mappedVehicle['total_price'] ?? null);
            if ($this->numericMismatch($rawTotal, $mappedTotal)) {
                $pricingWarnings[] = ['vehicle_id' => $rawVehicle['id'] ?? null, 'field' => 'total_price', 'raw' => $rawTotal, 'mapped' => $mappedTotal];
            }

            $rawDaily = data_get($rawVehicle, 'pricing.price_per_day');
            $mappedDaily = data_get($mappedVehicle, 'pricing.price_per_day', $mappedVehicle['price_per_day'] ?? null);
            if ($this->numericMismatch($rawDaily, $mappedDaily)) {
                $pricingWarnings[] = ['vehicle_id' => $rawVehicle['id'] ?? null, 'field' => 'price_per_day', 'raw' => $rawDaily, 'mapped' => $mappedDaily];
            }

            $rawCurrency = strtoupper((string) data_get($rawVehicle, 'pricing.currency', ''));
            $mappedCurrency = strtoupper((string) data_get($mappedVehicle, 'pricing.currency', $mappedVehicle['currency'] ?? ''));
            if ($rawCurrency !== '' && $mappedCurrency !== '' && $rawCurrency !== $mappedCurrency) {
                $pricingWarnings[] = ['vehicle_id' => $rawVehicle['id'] ?? null, 'field' => 'currency', 'raw' => $rawCurrency, 'mapped' => $mappedCurrency];
            }
        }

        $packageCount = $mapped->sum(fn (array $vehicle): int => count($vehicle['products'] ?? []));
        $protectionCount = $mapped->sum(fn (array $vehicle): int => count($vehicle['insurance_options'] ?? []) + count($vehicle['protections'] ?? []));
        $extrasCount = $mapped->sum(fn (array $vehicle): int => count($vehicle['extras'] ?? []));
        $quantityExtraCount = $mapped->sum(function (array $vehicle): int {
            return collect($vehicle['extras'] ?? [])->filter(function ($extra): bool {
                return is_array($extra) && (int) ($extra['numberAllowed'] ?? $extra['max_quantity'] ?? 1) > 1;
            })->count();
        });

        $gaps = [];
        if ($mapped->isNotEmpty() && $mapped->contains(fn (array $vehicle): bool => trim((string) ($vehicle['image'] ?? '')) === '')) {
            $gaps[] = 'One or more sampled vehicles are missing images.';
        }
        if ($mapped->isNotEmpty() && $packageCount === 0) {
            $gaps[] = 'No package/product data mapped for sampled vehicles.';
        }
        if ($mapped->isNotEmpty() && $extrasCount === 0) {
            $gaps[] = 'No customer extras/add-ons mapped for sampled vehicles.';
        }
        if ($mapped->isNotEmpty() && $protectionCount === 0) {
            $gaps[] = 'No protection/insurance data mapped for sampled vehicles.';
        }
        if ($pricingWarnings !== []) {
            $gaps[] = count($pricingWarnings).' pricing parity warning(s).';
        }

        return [
            'package_count' => $packageCount,
            'protection_count' => $protectionCount,
            'extras_count' => $extrasCount,
            'quantity_extra_count' => $quantityExtraCount,
            'pricing_warning_count' => count($pricingWarnings),
            'pricing_warnings' => $pricingWarnings,
            'sample_vehicle_names' => $mapped->take((int) $this->option('vehicles-per-provider'))->map(fn (array $vehicle) => $vehicle['model'] ?: ($vehicle['brand'] ?? $vehicle['id'] ?? 'Vehicle'))->values()->all(),
            'gaps' => $gaps,
        ];
    }

    private function findProviderLocations(array $locations, string $supplierId): array
    {
        $lookupIds = $this->providerLookupIds($supplierId);
        $matches = [];
        foreach ($locations as $location) {
            foreach (($location['providers'] ?? []) as $provider) {
                if (! is_array($provider)) {
                    continue;
                }
                $providerId = strtolower(trim((string) ($provider['provider'] ?? '')));
                if (in_array($providerId, $lookupIds, true) && trim((string) ($provider['pickup_id'] ?? '')) !== '') {
                    $matches[] = [$location, $provider];
                }
            }
        }

        return $matches;
    }

    private function baseRow(array $supplier, array $overrides = []): array
    {
        return array_merge([
            'provider' => (string) ($supplier['id'] ?? 'unknown'),
            'name' => (string) ($supplier['name'] ?? $supplier['id'] ?? 'unknown'),
            'status' => 'unknown',
            'enabled' => (bool) ($supplier['enabled'] ?? false),
            'has_adapter' => (bool) ($supplier['has_adapter'] ?? false),
            'supports_one_way' => (bool) ($supplier['supports_one_way'] ?? false),
            'configured_supports_one_way' => $supplier['configured_supports_one_way'] ?? null,
            'adapter_supports_one_way' => $supplier['adapter_supports_one_way'] ?? null,
            'supports_one_way_mismatch' => (bool) ($supplier['supports_one_way_mismatch'] ?? false),
            'countries' => $supplier['countries'] ?? [],
            'raw_vehicle_count' => 0,
            'transformed_vehicle_count' => 0,
            'package_count' => 0,
            'protection_count' => 0,
            'extras_count' => 0,
            'quantity_extra_count' => 0,
            'pricing_warning_count' => 0,
            'pricing_warnings' => [],
            'gaps' => [],
        ], $overrides);
    }

    private function gapEmailBlock(array $row): string
    {
        $lines = [
            'Provider: '.$row['name'].' ('.$row['provider'].')',
            'Sample location: '.($row['location_name'] ?? 'No mapped location'),
            'Issue summary:',
        ];

        foreach ($row['gaps'] ?? [] as $gap) {
            $lines[] = '- '.$gap;
        }

        $lines[] = '';
        $lines[] = 'Could you please confirm the correct live API fields for these items so Vrooem can display and book them directly from supplier data without static assumptions?';

        return implode("\n", $lines);
    }

    private function providerLookupIds(string $supplierId): array
    {
        $normalized = $this->normalizeProviderId($supplierId);
        $aliases = [
            'adobe_car' => ['adobe_car', 'adobe'],
            'green_motion' => ['green_motion', 'greenmotion'],
            'ok_mobility' => ['ok_mobility', 'okmobility'],
        ];

        return array_values(array_unique($aliases[$normalized] ?? [$normalized]));
    }

    private function locationsWithProviderHints(string $supplierId, array $fallbackLocations, LocationSearchService $locationSearchService): array
    {
        $hintLocations = [];
        foreach ($this->providerSearchHints($supplierId) as $term) {
            foreach ($locationSearchService->searchLocations($term, 10) as $location) {
                if (is_array($location)) {
                    $hintLocations[] = $location;
                }
            }
        }

        return collect($hintLocations)
            ->merge($fallbackLocations)
            ->filter(fn ($location) => is_array($location))
            ->unique(fn (array $location) => (string) ($location['unified_location_id'] ?? md5(json_encode($location))))
            ->values()
            ->all();
    }

    private function providerSearchHints(string $supplierId): array
    {
        return [
            'adobe_car' => ['San Jose Airport', 'Costa Rica'],
            'click2rent' => ['Mauritius Airport', 'Mauritius'],
            'easirent' => ['Orlando Airport', 'Miami Airport', 'Dublin Airport'],
            'emr' => ['Istanbul Airport', 'Antalya Airport'],
            'favrica' => ['Istanbul Airport', 'Tivat Airport'],
            'green_motion' => ['Dubai Airport', 'London Heathrow', 'Manchester Airport', 'Milan Airport'],
            'locauto_rent' => ['Malpensa', 'Milan Airport', 'Rome Airport'],
            'ok_mobility' => ['Dubai Airport', 'Malpensa', 'Barcelona Airport', 'Madrid Airport'],
            'recordgo' => ['Mallorca Airport', 'Alicante Airport', 'Lisbon Airport'],
            'renteon' => ['Dubai Airport', 'Malpensa', 'Istanbul Airport'],
            'sicily_by_car' => ['Malpensa', 'Rome Airport', 'Catania Airport'],
            'surprice' => ['Dubai Airport', 'Malpensa', 'Athens Airport', 'Lisbon Airport'],
            'usave' => ['Dubai Airport', 'London Heathrow', 'Orlando Airport'],
            'wheelsys' => ['Athens Airport', 'Thessaloniki Airport'],
            'xdrive' => ['Dubai Airport', 'Istanbul Airport'],
        ][$this->normalizeProviderId($supplierId)] ?? [];
    }

    private function normalizeProviderId(mixed $provider): string
    {
        return strtolower(trim((string) $provider));
    }

    private function numericMismatch(mixed $left, mixed $right): bool
    {
        if (! is_numeric($left) || ! is_numeric($right)) {
            return false;
        }

        return abs((float) $left - (float) $right) > 0.01;
    }

    private function resolveDateFrom(): CarbonImmutable
    {
        $date = $this->option('date-from');
        if (is_string($date) && trim($date) !== '') {
            return CarbonImmutable::parse($date);
        }

        return CarbonImmutable::now()->addMonthsNoOverflow(7)->startOfMonth()->addDays(11);
    }

    private function resolveDateTo(CarbonImmutable $dateFrom): CarbonImmutable
    {
        $date = $this->option('date-to');
        if (is_string($date) && trim($date) !== '') {
            return CarbonImmutable::parse($date);
        }

        return $dateFrom->addDays(4);
    }

    private function resolveOutputDir(): string
    {
        $output = $this->option('output');
        if (is_string($output) && trim($output) !== '') {
            return $output;
        }

        return storage_path('app/search-parity-audits/'.now()->format('Ymd_His_u').'_'.getmypid());
    }

    private function resolveOptionalInt(string $option): ?int
    {
        $value = $this->option($option);

        if (! is_scalar($value) || trim((string) $value) === '') {
            return null;
        }

        $resolved = (int) $value;

        return $resolved > 0 ? $resolved : null;
    }

    private function safeFilename(string $value): string
    {
        return preg_replace('/[^a-z0-9_-]+/i', '_', $value) ?: 'provider';
    }

    private function writeJson(string $path, mixed $payload): void
    {
        File::put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
