<?php

namespace App\Console\Commands;

use App\Services\LocationSearchService;
use App\Services\VrooemGatewayService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * REAL end-to-end reservation test, one provider at a time: search → book the
 * cheapest vehicle → capture the supplier's provider reference → cancel the
 * reservation immediately. Suppliers have approved test bookings on the
 * condition that every successful booking is cancelled right away.
 *
 * Writes nothing to the Laravel database. Any reservation whose cancellation
 * fails is reported loudly with the supplier reference for manual cancellation.
 */
class TestProviderReservations extends Command
{
    protected $signature = 'providers:test-reservation
        {--providers=* : Limit to these provider ids}
        {--currency=EUR : Search currency}
        {--age=35 : Driver age}
        {--date-from= : Pickup date (defaults to +30 days)}
        {--date-to= : Dropoff date (defaults to pickup +3 days)}
        {--start-time=10:00 : Pickup time}
        {--end-time=10:00 : Dropoff time}
        {--scan-limit=2000 : Gateway locations to scan for provider mappings}
        {--candidate-limit=25 : Provider location candidates to try before reporting no inventory}
        {--yes : Skip the per-provider confirmation prompt}';

    protected $description = 'REAL reservation test per provider: book cheapest vehicle, capture provider ref, cancel immediately. Supplier-approved.';

    /** Adapters whose gateway create_booking raises NotImplementedError (search-only). */
    private const SEARCH_ONLY = ['easirent'];

    /**
     * adobe_car's adapter cancel is a no-op (Adobe exposes no cancel endpoint) —
     * a test booking there stays LIVE. Only testable when explicitly requested.
     */
    private const NO_REAL_CANCEL = ['adobe_car'];

    /** Providers whose adapter does not verify the cancel response — confirm at the portal. */
    private const CANCEL_UNVERIFIED = ['xdrive'];

    private const TEST_DRIVER = [
        'first_name' => 'Diag',
        'last_name' => 'Test',
        'email' => 'diag.test@vrooem.com',
        'phone' => '+27821234567',
        'age' => 35,
        'driving_license_number' => '8801015800083',
        'driving_license_country' => 'ZA',
        'address' => '1 Test Road',
        'city' => 'Cape Town',
        'country' => 'South Africa',
        'postal_code' => '8001',
    ];

    public function handle(VrooemGatewayService $gateway, LocationSearchService $locationService): int
    {
        $dateFrom = $this->resolveDate($this->option('date-from'), CarbonImmutable::now()->addDays(30));
        $dateTo = $this->resolveDate($this->option('date-to'), $dateFrom->addDays(3));

        $requested = collect((array) $this->option('providers'))
            ->map(fn ($p) => strtolower(trim((string) $p)))
            ->filter()
            ->values();

        $suppliers = collect($gateway->listSuppliers()['suppliers'] ?? [])
            ->filter(fn ($s) => is_array($s) && ($s['enabled'] ?? false) && ($s['has_adapter'] ?? false))
            ->filter(fn (array $s) => (string) ($s['id'] ?? '') !== 'internal')
            ->when($requested->isNotEmpty(), fn ($items) => $items->filter(
                fn (array $s) => $requested->contains(strtolower(trim((string) ($s['id'] ?? ''))))
            ))
            ->values();

        if ($suppliers->isEmpty()) {
            $this->error('No matching gateway suppliers found.');

            return self::FAILURE;
        }

        $locations = $locationService->getAllLocations((int) $this->option('scan-limit'));
        $rows = [];

        foreach ($suppliers as $supplier) {
            $rows[] = $this->testProvider($supplier, $locations, $gateway, $locationService, $dateFrom, $dateTo);
        }

        $this->renderTable($rows);
        $this->warnAboutStrandedReservations($rows);

        return self::SUCCESS;
    }

    private function testProvider(
        array $supplier,
        array $locations,
        VrooemGatewayService $gateway,
        LocationSearchService $locationService,
        CarbonImmutable $dateFrom,
        CarbonImmutable $dateTo
    ): array {
        $supplierId = strtolower(trim((string) ($supplier['id'] ?? 'unknown')));
        $row = [
            'provider' => $supplierId,
            'vehicle' => '',
            'price' => '',
            'provider_ref' => '',
            'cancel' => '',
            'detail' => '',
            'result' => '',
        ];

        $this->newLine();
        $this->info("=== {$supplierId} ===");

        if (in_array($supplierId, self::SEARCH_ONLY, true)) {
            return ['result' => '⏭️ SEARCH-ONLY', 'detail' => 'provider has no booking API'] + $row;
        }

        $explicitlyRequested = collect((array) $this->option('providers'))
            ->map(fn ($p) => strtolower(trim((string) $p)))
            ->contains($supplierId);
        if (in_array($supplierId, self::NO_REAL_CANCEL, true) && ! $explicitlyRequested) {
            return ['result' => '⏭️ SKIPPED', 'detail' => 'adapter cannot really cancel — booking would stay live; run with --providers='.$supplierId.' to force'] + $row;
        }

        $candidates = $this->findProviderLocations(
            $this->locationsWithHints($supplierId, $locations, $locationService),
            $supplierId
        );
        if ($candidates === []) {
            return ['result' => '⚠️ NO LOCATION'] + $row;
        }

        [$searchId, $vehicle, $locationName] = $this->findCheapestVehicle($candidates, $supplierId, $gateway, $dateFrom, $dateTo);
        if ($vehicle === null) {
            return ['result' => '⚠️ NO INVENTORY'] + $row;
        }

        $vehicleId = $vehicle['gateway_vehicle_id'] ?? $vehicle['id'] ?? null;
        $name = $vehicle['display_name'] ?? $vehicle['name'] ?? 'unknown';
        $price = ($vehicle['pricing']['total_price'] ?? '?').' '.($vehicle['pricing']['currency'] ?? '');
        $package = $this->firstPackage($vehicle);
        $row['vehicle'] = $name;
        $row['price'] = $price;

        $this->line("Location: {$locationName}");
        $this->line("Vehicle: {$name} ({$vehicleId}) — {$price} — package: ".($package ?? '(default)'));

        if (! $this->option('yes') && ! $this->confirm("Create a REAL {$supplierId} reservation for this vehicle (will be cancelled immediately)?", true)) {
            return ['result' => '⏭️ SKIPPED', 'detail' => 'declined by operator'] + $row;
        }

        $result = $gateway->createBooking([
            'vehicle_id' => $vehicleId,
            'search_id' => $searchId,
            'package' => $package,
            'driver' => self::TEST_DRIVER,
            'pickup_date' => $dateFrom->toDateString(),
            'pickup_time' => (string) $this->option('start-time'),
            'dropoff_date' => $dateTo->toDateString(),
            'dropoff_time' => (string) $this->option('end-time'),
            'laravel_booking_id' => 999999,
            'laravel_booking_number' => 'TEST-INTEGRATION',
        ]);

        if ($result === null) {
            $error = json_encode($gateway->getLastError());
            $this->error('BOOK failed: '.$error);

            return ['result' => '❌ REJECTED', 'detail' => mb_substr((string) $error, 0, 160)] + $row;
        }

        $status = (string) ($result['status'] ?? '');
        $supplierRef = (string) ($result['supplier_booking_id'] ?? '');
        $gatewayBookingId = (string) ($result['id'] ?? $result['gateway_booking_id'] ?? '');
        $this->line("BOOK status={$status} supplier_booking_id={$supplierRef} gateway_booking_id={$gatewayBookingId}");

        if ($status !== 'confirmed' || $supplierRef === '') {
            $detail = (string) ($result['failure_reason'] ?? $result['provider_status'] ?? 'no supplier reference returned');

            return ['result' => '❌ REJECTED', 'detail' => mb_substr($detail, 0, 160)] + $row;
        }

        $row['provider_ref'] = $supplierRef;
        $this->warn("REAL reservation created (ref {$supplierRef}) — cancelling immediately.");

        $cancel = $gateway->cancelBooking($gatewayBookingId, $supplierId, $supplierRef, 'Integration test - cancellation agreed with supplier');
        $cancelStatus = strtolower((string) ($cancel['status'] ?? ''));
        $this->line('CANCEL: '.json_encode($cancel));

        if ($cancel !== null && in_array($cancelStatus, ['cancelled', 'canceled', 'success', 'ok'], true)) {
            if (in_array($supplierId, self::CANCEL_UNVERIFIED, true)) {
                $row['cancel'] = '⚠️ cancelled (unverified)';

                return ['result' => '✅ PASS', 'detail' => 'adapter does not verify cancel responses — confirm ref '.$supplierRef.' is cancelled at the supplier portal'] + $row;
            }

            $fee = $cancel['cancellation_fee'] ?? $cancel['cancellationFee'] ?? null;
            $row['cancel'] = '✅ cancelled'.($fee !== null ? " (fee: {$fee})" : '');

            return ['result' => '✅ PASS'] + $row;
        }

        $row['cancel'] = '🚨 CANCEL FAILED';

        return [
            'result' => '🚨 MANUAL CANCEL NEEDED',
            'detail' => 'Reservation LIVE at supplier. Cancel ref '.$supplierRef.' via the '.$supplierId.' portal NOW. Gateway said: '.mb_substr((string) json_encode($cancel ?? $gateway->getLastError()), 0, 140),
        ] + $row;
    }

    /**
     * @return array{0: ?string, 1: ?array, 2: ?string} [search_id, cheapest matched vehicle, location name]
     */
    private function findCheapestVehicle(array $candidates, string $supplierId, VrooemGatewayService $gateway, CarbonImmutable $dateFrom, CarbonImmutable $dateTo): array
    {
        $lookupIds = $this->providerLookupIds($supplierId);

        foreach (array_slice($candidates, 0, max(1, (int) $this->option('candidate-limit'))) as [$location, $entry]) {
            $raw = $gateway->searchVehicles([
                'unified_location_id' => (int) $location['unified_location_id'],
                'pickup_date' => $dateFrom->toDateString(),
                'dropoff_date' => $dateTo->toDateString(),
                'pickup_time' => (string) $this->option('start-time'),
                'dropoff_time' => (string) $this->option('end-time'),
                'driver_age' => (int) $this->option('age'),
                'currency' => strtoupper((string) $this->option('currency')),
                'providers' => strtolower((string) ($entry['provider'] ?? $supplierId)),
                'provider_locations' => [$entry],
                'country_code' => $location['country_code'] ?? null,
            ]);

            $matched = collect($raw['vehicles'] ?? [])
                ->filter(fn ($v) => is_array($v) && in_array(strtolower((string) ($v['source'] ?? $v['supplier_id'] ?? '')), $lookupIds, true))
                // Sicily By Car sells prepaid (-PRE) and pay-on-arrival (-POA)
                // rates; a test booking must NEVER hit a prepaid rate.
                ->when($supplierId === 'sicily_by_car', fn ($vehicles) => $vehicles->filter(
                    fn (array $v) => ! str_contains(strtoupper(json_encode($v['booking_context']['provider_payload']['rate_id'] ?? ($v['supplier_data']['rate_id'] ?? ''))), '-PRE')
                ))
                ->sortBy(fn (array $v) => (float) ($v['pricing']['total_price'] ?? PHP_FLOAT_MAX))
                ->values();

            if ($matched->isNotEmpty()) {
                return [$raw['search_id'] ?? null, $matched->first(), (string) ($location['name'] ?? '')];
            }
        }

        return [null, null, null];
    }

    private function firstPackage(array $vehicle): ?string
    {
        foreach (($vehicle['products'] ?? []) as $product) {
            if (is_array($product) && trim((string) ($product['type'] ?? '')) !== '') {
                return (string) $product['type'];
            }
        }

        return null;
    }

    private function renderTable(array $rows): void
    {
        $this->newLine();
        $this->table(
            ['Provider', 'Vehicle', 'Price', 'Provider Ref', 'Cancel', 'Result', 'Detail'],
            collect($rows)->map(fn (array $r) => [
                $r['provider'],
                mb_substr($r['vehicle'], 0, 30),
                $r['price'],
                $r['provider_ref'] !== '' ? $r['provider_ref'] : '—',
                $r['cancel'] !== '' ? $r['cancel'] : '—',
                $r['result'],
                mb_substr($r['detail'], 0, 80),
            ])->all()
        );
    }

    private function warnAboutStrandedReservations(array $rows): void
    {
        $stranded = array_filter($rows, fn (array $r) => $r['result'] === '🚨 MANUAL CANCEL NEEDED');
        if ($stranded === []) {
            return;
        }

        $this->newLine();
        $this->error('!!! LIVE RESERVATIONS THAT COULD NOT BE CANCELLED — cancel these at the supplier portal NOW:');
        foreach ($stranded as $r) {
            $this->error("  {$r['provider']} — ref {$r['provider_ref']} — {$r['vehicle']}");
        }
    }

    // --- Location discovery (mirrors AuditSearchProviderParity) ---

    private function locationsWithHints(string $supplierId, array $fallback, LocationSearchService $locationService): array
    {
        $hits = [];
        foreach ($this->providerSearchHints($supplierId) as $term) {
            foreach ($locationService->searchLocations($term, 10) as $location) {
                if (is_array($location)) {
                    $hits[] = $location;
                }
            }
        }

        return collect($hits)
            ->merge($fallback)
            ->filter(fn ($l) => is_array($l))
            ->unique(fn (array $l) => (string) ($l['unified_location_id'] ?? md5(json_encode($l))))
            ->values()
            ->all();
    }

    /** @return array<int, array{0: array, 1: array}> */
    private function findProviderLocations(array $locations, string $supplierId): array
    {
        $lookupIds = $this->providerLookupIds($supplierId);
        $matches = [];
        foreach ($locations as $location) {
            foreach (($location['providers'] ?? []) as $provider) {
                if (! is_array($provider)) {
                    continue;
                }
                $id = strtolower(trim((string) ($provider['provider'] ?? '')));
                if (in_array($id, $lookupIds, true) && trim((string) ($provider['pickup_id'] ?? '')) !== '') {
                    $matches[] = [$location, $provider];
                }
            }
        }

        return $matches;
    }

    /** @return array<int, string> */
    private function providerLookupIds(string $supplierId): array
    {
        $aliases = [
            'adobe_car' => ['adobe_car', 'adobe'],
            'green_motion' => ['green_motion', 'greenmotion'],
            'ok_mobility' => ['ok_mobility', 'okmobility'],
        ];

        return array_values(array_unique($aliases[$supplierId] ?? [$supplierId]));
    }

    /** @return array<int, string> */
    private function providerSearchHints(string $supplierId): array
    {
        return [
            'adobe_car' => ['San Jose Airport', 'Costa Rica'],
            'click2rent' => ['Mauritius Airport', 'Mauritius'],
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
            'yesaway' => ['Dubai Airport', 'Bangkok Airport'],
        ][$supplierId] ?? [];
    }

    private function resolveDate(mixed $value, CarbonImmutable $default): CarbonImmutable
    {
        $value = trim((string) $value);

        return $value === '' ? $default : CarbonImmutable::parse($value);
    }
}
