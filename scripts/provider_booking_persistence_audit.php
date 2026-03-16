<?php

declare(strict_types=1);

use App\Http\Controllers\StripeCheckoutController;
use App\Models\StripeCheckoutPayload;
use App\Services\GatewaySearchParamsBuilder;
use App\Services\StripeBookingService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

require __DIR__ . '/../vendor/autoload.php';

class AuditStripeMetadata
{
    public function __construct(private array $data)
    {
    }

    public function __get(string $name)
    {
        return $this->data[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->data[$name] = $value;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$cases = [
    ['provider' => 'greenmotion', 'pickup_id' => '61334', 'pickup_date' => '2026-06-15', 'dropoff_date' => '2026-06-20', 'pickup_time' => '10:00', 'dropoff_time' => '10:00', 'driver_age' => 35],
    ['provider' => 'renteon', 'pickup_id' => 'GR-ATH-ATH', 'pickup_date' => '2026-06-15', 'dropoff_date' => '2026-06-20', 'pickup_time' => '10:00', 'dropoff_time' => '10:00', 'driver_age' => 30],
    ['provider' => 'surprice', 'pickup_id' => 'ATH', 'pickup_date' => '2026-06-15', 'dropoff_date' => '2026-06-20', 'pickup_time' => '10:00', 'dropoff_time' => '10:00', 'driver_age' => 35],
    ['provider' => 'recordgo', 'pickup_id' => '35001', 'pickup_date' => '2026-05-06', 'dropoff_date' => '2026-05-14', 'pickup_time' => '09:00', 'dropoff_time' => '09:00', 'driver_age' => 35],
    ['provider' => 'xdrive', 'pickup_id' => '52', 'pickup_date' => '2026-06-15', 'dropoff_date' => '2026-06-20', 'pickup_time' => '09:00', 'dropoff_time' => '09:00', 'driver_age' => 30],
    ['provider' => 'favrica', 'pickup_id' => '8', 'pickup_date' => '2026-06-15', 'dropoff_date' => '2026-06-20', 'pickup_time' => '09:00', 'dropoff_time' => '09:00', 'driver_age' => 30],
    ['provider' => 'okmobility', 'pickup_id' => '01', 'pickup_date' => '2026-06-15', 'dropoff_date' => '2026-06-20', 'pickup_time' => '10:00', 'dropoff_time' => '10:00', 'driver_age' => 35],
    ['provider' => 'locauto_rent', 'pickup_id' => 'FCO', 'pickup_date' => '2026-06-15', 'dropoff_date' => '2026-06-20', 'pickup_time' => '10:00', 'dropoff_time' => '10:00', 'driver_age' => 35],
    ['provider' => 'sicily_by_car', 'pickup_id' => 'IT011', 'pickup_date' => '2026-06-15', 'dropoff_date' => '2026-06-20', 'pickup_time' => '09:00', 'dropoff_time' => '09:00', 'driver_age' => 30],
    ['provider' => 'adobe', 'pickup_id' => 'SJO', 'pickup_date' => '2026-06-15', 'dropoff_date' => '2026-06-20', 'pickup_time' => '09:00', 'dropoff_time' => '09:00', 'driver_age' => 30],
    ['provider' => 'wheelsys', 'pickup_id' => 'MAIN', 'pickup_date' => '2026-06-15', 'dropoff_date' => '2026-06-20', 'pickup_time' => '10:00', 'dropoff_time' => '10:00', 'driver_age' => 35],
];

$locations = json_decode(file_get_contents(public_path('unified_locations.json')), true, 512, JSON_THROW_ON_ERROR);
$paramsBuilder = app(GatewaySearchParamsBuilder::class);
$bookingService = app(StripeBookingService::class);
$checkoutController = app(StripeCheckoutController::class);
$resolveCheckoutLocations = new ReflectionMethod($checkoutController, 'resolveCheckoutLocationDetails');
$resolveCheckoutLocations->setAccessible(true);

Notification::fake();

$results = [
    'generated_at' => now()->toIso8601String(),
    'cases' => [],
];

foreach ($cases as $case) {
    $provider = (string) $case['provider'];
    $pickupId = (string) $case['pickup_id'];
    $location = findLocation($locations, $provider, $pickupId);

    $row = [
        'provider' => $provider,
        'pickup_id' => $pickupId,
    ];

    if ($location === null) {
        $row['status'] = 'blocked';
        $row['reason'] = 'No matching unified location row in public/unified_locations.json';
        $results['cases'][] = $row;
        continue;
    }

    $query = [
        'provider' => $provider,
        'provider_pickup_id' => $pickupId,
        'unified_location_id' => $location['unified_location_id'],
        'date_from' => $case['pickup_date'],
        'date_to' => $case['dropoff_date'],
        'start_time' => $case['pickup_time'],
        'end_time' => $case['dropoff_time'],
        'age' => $case['driver_age'],
        'where' => $location['name'] ?? null,
    ];

    try {
        // Ensure gateway params still build for this provider/date.
        $paramsBuilder->build($query);
        $frontend = dispatchFrontendSearch($query);
        $vehicles = providerVehiclesFromFrontend($frontend, $provider);

        if (count($vehicles) === 0) {
            $row['status'] = 'no-inventory';
            $row['reason'] = 'No frontend vehicles available for this provider in current audit case.';
            $results['cases'][] = $row;
            continue;
        }

        $vehicle = $vehicles[0];
        $validatedForCheckout = [
            'vehicle' => $vehicle,
            'location_details' => $vehicle['location_details'] ?? null,
            'dropoff_location_details' => $vehicle['dropoff_location_details'] ?? null,
        ];
        [$pickupLocationDetails, $dropoffLocationDetails] = $resolveCheckoutLocations->invoke($checkoutController, $validatedForCheckout);

        DB::beginTransaction();
        try {
            $email = sprintf('audit+%s+%s@example.com', $provider, Str::lower(Str::random(8)));
            $sessionId = 'audit_sess_' . Str::lower(Str::random(12));
            $paymentIntent = 'audit_pi_' . Str::lower(Str::random(12));
            $providerRef = 'AUDIT-REF-' . strtoupper(substr($provider, 0, 8)) . '-' . strtoupper(Str::random(6));

            $fullMetadata = [
                'vehicle_source' => $provider,
                'vehicle_id' => (string) ($vehicle['id'] ?? ('audit_' . $provider)),
                'vehicle_brand' => $vehicle['brand'] ?? 'Audit',
                'vehicle_model' => $vehicle['model'] ?? 'Vehicle',
                'vehicle_image' => $vehicle['image'] ?? null,
                'pickup_date' => $case['pickup_date'],
                'pickup_time' => $case['pickup_time'],
                'dropoff_date' => $case['dropoff_date'],
                'dropoff_time' => $case['dropoff_time'],
                'pickup_location' => $location['name'] ?? 'Audit Pickup',
                'dropoff_location' => $location['name'] ?? 'Audit Dropoff',
                'number_of_days' => 3,
                'currency' => $vehicle['currency'] ?? 'EUR',
                'provider_currency' => $vehicle['currency'] ?? 'EUR',
                'total_amount' => 120,
                'total_amount_net' => 100,
                'payable_amount' => 20,
                'provider_grand_total' => 100,
                'provider_vehicle_total' => 90,
                'provider_extras_total' => 10,
                'customer_name' => 'Audit User',
                'customer_email' => $email,
                'customer_phone' => '+10000000000',
                'customer_driver_age' => 35,
                'payment_method' => 'card',
                'package' => 'BAS',
                'pickup_location_code' => $vehicle['provider_pickup_id'] ?? $pickupId,
                'return_location_code' => $vehicle['provider_return_id'] ?? ($vehicle['provider_pickup_id'] ?? $pickupId),
                'provider_booking_ref' => $providerRef,
            ];

            $payload = StripeCheckoutPayload::create([
                'payload' => [
                    'detailed_extras' => [],
                    'extras' => [],
                    'vehicle_source' => $provider,
                    'vehicle_id' => $vehicle['id'] ?? null,
                    'location_details' => $pickupLocationDetails,
                    'dropoff_location_details' => $dropoffLocationDetails,
                    'full_metadata' => $fullMetadata,
                ],
            ]);

            $metadata = new AuditStripeMetadata([
                'extras_payload_id' => (string) $payload->id,
                'vehicle_source' => $provider,
                'vehicle_id' => (string) ($vehicle['id'] ?? ('audit_' . $provider)),
                'vehicle_brand' => $vehicle['brand'] ?? 'Audit',
                'vehicle_model' => $vehicle['model'] ?? 'Vehicle',
                'pickup_date' => $case['pickup_date'],
                'pickup_time' => $case['pickup_time'],
                'dropoff_date' => $case['dropoff_date'],
                'dropoff_time' => $case['dropoff_time'],
                'pickup_location' => $location['name'] ?? 'Audit Pickup',
                'dropoff_location' => $location['name'] ?? 'Audit Dropoff',
                'number_of_days' => 3,
                'currency' => $vehicle['currency'] ?? 'EUR',
                'provider_currency' => $vehicle['currency'] ?? 'EUR',
                'total_amount' => 120,
                'total_amount_net' => 100,
                'payable_amount' => 20,
                'provider_grand_total' => 100,
                'provider_vehicle_total' => 90,
                'provider_extras_total' => 10,
                'customer_name' => 'Audit User',
                'customer_email' => $email,
                'customer_phone' => '+10000000000',
                'customer_driver_age' => 35,
                'payment_method' => 'card',
                'package' => 'BAS',
                'provider_booking_ref' => $providerRef,
            ]);

            $session = (object) [
                'id' => $sessionId,
                'payment_intent' => $paymentIntent,
                'metadata' => $metadata,
            ];

            $booking = $bookingService->createBookingFromSession($session);
            $booking->refresh();

            $providerMetadata = $booking->provider_metadata ?? [];
            $pickupDetails = $providerMetadata['pickup_location_details'] ?? $providerMetadata['location'] ?? null;
            $dropoffDetails = $providerMetadata['dropoff_location_details'] ?? null;

            $row['status'] = 'ok';
            $row['booking_id'] = $booking->id;
            $row['metadata_keys_count'] = is_array($providerMetadata) ? count($providerMetadata) : 0;
            $row['pickup_details_present'] = is_array($pickupDetails) && !empty($pickupDetails);
            $row['dropoff_details_present'] = is_array($dropoffDetails) && !empty($dropoffDetails);
            $row['pickup_details_has_coordinates'] = is_array($pickupDetails)
                && !empty($pickupDetails['latitude'])
                && !empty($pickupDetails['longitude']);
            $row['pickup_details_preview'] = is_array($pickupDetails)
                ? array_intersect_key($pickupDetails, array_flip(['name', 'address_1', 'address_city', 'address_postcode', 'latitude', 'longitude']))
                : null;
            $row['booking_pickup_location'] = $booking->pickup_location;
            $row['booking_return_location'] = $booking->return_location;

            DB::rollBack();
        } catch (Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            throw $e;
        }
    } catch (Throwable $e) {
        $row['status'] = 'error';
        $row['reason'] = $e->getMessage();
        $row['trace_file'] = $e->getFile();
        $row['trace_line'] = $e->getLine();
    }

    $results['cases'][] = $row;
}

$outputDir = base_path('docs/reports');
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

$jsonPath = $outputDir . '/provider-booking-persistence-audit-' . now()->format('Y-m-d-His') . '.json';
file_put_contents($jsonPath, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo $jsonPath . PHP_EOL;

function findLocation(array $locations, string $provider, string $pickupId): ?array
{
    foreach ($locations as $location) {
        foreach (($location['providers'] ?? []) as $providerEntry) {
            if ((string) ($providerEntry['provider'] ?? '') === $provider && (string) ($providerEntry['pickup_id'] ?? '') === $pickupId) {
                return $location;
            }
        }
    }

    return null;
}

function dispatchFrontendSearch(array $query): array
{
    $baseUrl = rtrim((string) env('APP_URL', 'http://127.0.0.1:8000'), '/');
    $response = Http::timeout(120)->get($baseUrl . '/en/s', $query);
    if (!$response->successful()) {
        throw new RuntimeException('Laravel search HTTP request failed with status ' . $response->status() . '.');
    }

    $content = $response->body();
    if (!preg_match('/data-page=\"([^\"]+)\"/', $content, $matches)) {
        throw new RuntimeException('Laravel search response did not include Inertia data-page payload.');
    }

    $decoded = json_decode(htmlspecialchars_decode($matches[1], ENT_QUOTES), true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Laravel search did not return valid decoded Inertia payload.');
    }

    return $decoded;
}

function providerVehiclesFromFrontend(array $frontend, string $provider): array
{
    $vehicles = $frontend['props']['vehicles']['data'] ?? [];
    if (!is_array($vehicles)) {
        return [];
    }

    return array_values(array_filter($vehicles, static function ($vehicle) use ($provider): bool {
        if (!is_array($vehicle)) {
            return false;
        }

        return strtolower((string) ($vehicle['source'] ?? '')) === strtolower($provider);
    }));
}
