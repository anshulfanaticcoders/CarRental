<?php

namespace App\Console\Commands;

use App\Services\VrooemGatewayService;
use Illuminate\Console\Command;

/**
 * One-off diagnostic: reproduce a GreenMotion reservation via the gateway to see
 * whether a failure is our data (e.g. invalid licence) or the supplier side.
 * Does a fresh search, then attempts a booking with a valid licence and with the
 * junk "13;" licence. Any real reservation it creates is cancelled immediately.
 * Nothing is written to our database.
 */
class DiagnoseGreenmotionReservation extends Command
{
    protected $signature = 'gm:diagnose-reservation
        {--unified=922448855 : Unified location id}
        {--pickup=2026-08-10 : Pickup date}
        {--dropoff=2026-08-12 : Dropoff date}
        {--pickup-time=11:00}
        {--dropoff-time=18:00}
        {--currency=ZAR}
        {--age=35}
        {--lat=-26.164276 : Pickup latitude}
        {--long=28.230573 : Pickup longitude}
        {--country=ZA : Country code}';

    protected $description = 'Diagnose GreenMotion reservation (search + book valid/junk licence + auto-cancel). No DB writes.';

    public function handle(VrooemGatewayService $gateway): int
    {
        $search = $gateway->searchVehicles([
            'unified_location_id' => (int) $this->option('unified'),
            'pickup_latitude' => (float) $this->option('lat'),
            'pickup_longitude' => (float) $this->option('long'),
            'dropoff_latitude' => (float) $this->option('lat'),
            'dropoff_longitude' => (float) $this->option('long'),
            'country_code' => $this->option('country'),
            'pickup_date' => $this->option('pickup'),
            'pickup_time' => $this->option('pickup-time'),
            'dropoff_date' => $this->option('dropoff'),
            'dropoff_time' => $this->option('dropoff-time'),
            'currency' => $this->option('currency'),
            'driver_age' => (int) $this->option('age'),
        ]);

        $vehicles = $search['vehicles'] ?? [];
        $gm = collect($vehicles)->filter(fn ($v) => ($v['supplier_id'] ?? null) === 'green_motion')->values();
        $this->info('SEARCH search_id='.($search['search_id'] ?? 'null').' total='.count($vehicles).' gm='.$gm->count());
        $this->line('provider_status: '.json_encode($search['provider_status'] ?? null));

        if ($gm->isEmpty()) {
            $this->error('No GreenMotion vehicles returned — cannot test. (Location mapping or availability issue.)');

            return self::FAILURE;
        }

        $veh = $gm->first(fn ($v) => str_contains(strtolower($v['name'] ?? ''), 'polo vivo')) ?? $gm->first();
        $vid = $veh['id'] ?? null;
        $this->info('vehicle='.$vid.' name='.($veh['name'] ?? '').' price='.(($veh['pricing']['total_price'] ?? '?')).' '.($veh['pricing']['currency'] ?? ''));

        foreach (['8801015800083' => 'VALID-LICENCE', '13;' => 'JUNK-LICENCE'] as $licence => $label) {
            $this->line('');
            $this->info("--- Attempt: {$label} (licence={$licence}) ---");
            $result = $gateway->createBooking([
                'vehicle_id' => $vid,
                'search_id' => $search['search_id'] ?? null,
                'package' => 'BAS',
                'driver' => [
                    'first_name' => 'Diag', 'last_name' => 'Test', 'email' => 'diag.test@vrooem.com',
                    'phone' => '27821234567', 'age' => 35, 'driving_license_number' => $licence,
                    'driving_license_country' => 'ZA', 'address' => '1 Test Rd', 'city' => 'Cape Town',
                    'country' => 'South Africa', 'postal_code' => '8001',
                ],
                'pickup_date' => $this->option('pickup'), 'pickup_time' => $this->option('pickup-time'),
                'dropoff_date' => $this->option('dropoff'), 'dropoff_time' => $this->option('dropoff-time'),
                'laravel_booking_id' => 999999, 'laravel_booking_number' => 'TEST-DIAG',
            ]);

            if ($result === null) {
                $this->error('BOOK returned null. gateway lastError: '.json_encode($gateway->getLastError()));

                continue;
            }

            $this->line('BOOK status='.($result['status'] ?? '?').' supplier_booking_id='.($result['supplier_booking_id'] ?? '').' provider_status='.($result['provider_status'] ?? ''));
            $this->line('failure_reason: '.($result['failure_reason'] ?? '(none)'));
            $this->line('raw: '.substr(json_encode($result), 0, 1200));

            if (($result['status'] ?? null) === 'confirmed' && ! empty($result['supplier_booking_id'])) {
                $this->warn('Real reservation created — cancelling.');
                $cancel = $gateway->cancelBooking(
                    (string) ($result['id'] ?? $result['gateway_booking_id'] ?? ''),
                    'green_motion',
                    (string) $result['supplier_booking_id'],
                    'integration test'
                );
                $this->line('CANCEL: '.json_encode($cancel));
            }
        }

        return self::SUCCESS;
    }
}
