<?php

declare(strict_types=1);

use App\Services\GatewaySearchParamsBuilder;
use App\Services\VrooemGatewayService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Http;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$cases = [
    [
        'provider' => 'greenmotion',
        'pickup_id' => '61334',
        'pickup_date' => '2026-06-15',
        'dropoff_date' => '2026-06-20',
        'pickup_time' => '10:00',
        'dropoff_time' => '10:00',
        'driver_age' => 35,
    ],
    [
        'provider' => 'renteon',
        'pickup_id' => 'GR-ATH-ATH',
        'pickup_date' => '2026-06-15',
        'dropoff_date' => '2026-06-20',
        'pickup_time' => '10:00',
        'dropoff_time' => '10:00',
        'driver_age' => 30,
    ],
    [
        'provider' => 'surprice',
        'pickup_id' => 'ATH',
        'pickup_date' => '2026-06-15',
        'dropoff_date' => '2026-06-20',
        'pickup_time' => '10:00',
        'dropoff_time' => '10:00',
        'driver_age' => 35,
    ],
    [
        'provider' => 'recordgo',
        'pickup_id' => '35001',
        'pickup_date' => '2026-05-06',
        'dropoff_date' => '2026-05-14',
        'pickup_time' => '09:00',
        'dropoff_time' => '09:00',
        'driver_age' => 35,
    ],
    [
        'provider' => 'xdrive',
        'pickup_id' => '52',
        'pickup_date' => '2026-06-15',
        'dropoff_date' => '2026-06-20',
        'pickup_time' => '09:00',
        'dropoff_time' => '09:00',
        'driver_age' => 30,
    ],
    [
        'provider' => 'favrica',
        'pickup_id' => '8',
        'pickup_date' => '2026-06-15',
        'dropoff_date' => '2026-06-20',
        'pickup_time' => '09:00',
        'dropoff_time' => '09:00',
        'driver_age' => 30,
    ],
    [
        'provider' => 'okmobility',
        'pickup_id' => '01',
        'pickup_date' => '2026-06-15',
        'dropoff_date' => '2026-06-20',
        'pickup_time' => '10:00',
        'dropoff_time' => '10:00',
        'driver_age' => 35,
    ],
    [
        'provider' => 'locauto_rent',
        'pickup_id' => 'FCO',
        'pickup_date' => '2026-06-15',
        'dropoff_date' => '2026-06-20',
        'pickup_time' => '10:00',
        'dropoff_time' => '10:00',
        'driver_age' => 35,
    ],
    [
        'provider' => 'sicily_by_car',
        'pickup_id' => 'IT011',
        'pickup_date' => '2026-06-15',
        'dropoff_date' => '2026-06-20',
        'pickup_time' => '09:00',
        'dropoff_time' => '09:00',
        'driver_age' => 30,
    ],
    [
        'provider' => 'adobe',
        'pickup_id' => 'SJO',
        'pickup_date' => '2026-06-15',
        'dropoff_date' => '2026-06-20',
        'pickup_time' => '09:00',
        'dropoff_time' => '09:00',
        'driver_age' => 30,
    ],
    [
        'provider' => 'wheelsys',
        'pickup_id' => 'MAIN',
        'pickup_date' => '2026-06-15',
        'dropoff_date' => '2026-06-20',
        'pickup_time' => '10:00',
        'dropoff_time' => '10:00',
        'driver_age' => 35,
    ],
];

$locations = json_decode(file_get_contents(public_path('unified_locations.json')), true, 512, JSON_THROW_ON_ERROR);
$paramsBuilder = app(GatewaySearchParamsBuilder::class);
$gatewayService = app(VrooemGatewayService::class);

$results = [
    'generated_at' => now()->toIso8601String(),
    'gateway_url' => config('vrooem.url'),
    'cases' => [],
];

foreach ($cases as $case) {
    $provider = (string) $case['provider'];
    $pickupId = (string) $case['pickup_id'];
    $location = findLocation($locations, $provider, $pickupId);

    $row = [
        'provider' => $provider,
        'pickup_id' => $pickupId,
        'search' => [
            'pickup_date' => $case['pickup_date'],
            'dropoff_date' => $case['dropoff_date'],
            'pickup_time' => $case['pickup_time'],
            'dropoff_time' => $case['dropoff_time'],
            'driver_age' => $case['driver_age'],
        ],
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
        $gatewayParams = $paramsBuilder->build($query);
        $gatewayResult = $gatewayService->searchVehicles($gatewayParams);
        $frontend = dispatchFrontendSearch($query);

        $frontendVehicles = providerVehiclesFromFrontend($frontend, $provider);
        $locationAudit = auditVehicleLocations($frontendVehicles);
        $checkoutAudit = auditCheckoutAndBookingMetadataReadiness($provider, $frontendVehicles[0] ?? null);

        $row['gateway_count'] = count($gatewayResult['vehicles'] ?? []);
        $row['frontend_provider_count'] = count($frontendVehicles);
        $row['location_audit'] = $locationAudit;
        $row['booking_metadata_readiness'] = $checkoutAudit;
        $row['sample_vehicle'] = sampleVehicle($frontendVehicles[0] ?? null);
        $row['status'] = count($frontendVehicles) > 0 ? 'ok' : 'no-inventory';
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

$jsonPath = $outputDir . '/provider-location-booking-audit-' . now()->format('Y-m-d-His') . '.json';
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

function auditVehicleLocations(array $vehicles): array
{
    $counts = [
        'total' => count($vehicles),
        'valid_coordinates' => 0,
        'full_vehicle_address' => 0,
        'location_details' => 0,
        'renteon_pickup_office' => 0,
        'okmobility_pickup_station_or_address' => 0,
        'adobe_office_address' => 0,
    ];

    foreach ($vehicles as $vehicle) {
        if (hasValidCoordinates($vehicle['latitude'] ?? null, $vehicle['longitude'] ?? null)) {
            $counts['valid_coordinates']++;
        }

        if (isNonEmptyString($vehicle['full_vehicle_address'] ?? null)) {
            $counts['full_vehicle_address']++;
        }

        if (!empty($vehicle['location_details']) && is_array($vehicle['location_details'])) {
            $counts['location_details']++;
        }

        if (!empty($vehicle['pickup_office']) && is_array($vehicle['pickup_office'])) {
            $counts['renteon_pickup_office']++;
        }

        if (isNonEmptyString($vehicle['pickup_station_name'] ?? null) || isNonEmptyString($vehicle['pickup_address'] ?? null)) {
            $counts['okmobility_pickup_station_or_address']++;
        }

        if (isNonEmptyString($vehicle['office_address'] ?? null)) {
            $counts['adobe_office_address']++;
        }
    }

    $total = max($counts['total'], 1);
    return [
        'counts' => $counts,
        'ratios' => [
            'valid_coordinates_ratio' => round($counts['valid_coordinates'] / $total, 4),
            'full_vehicle_address_ratio' => round($counts['full_vehicle_address'] / $total, 4),
            'location_details_ratio' => round($counts['location_details'] / $total, 4),
        ],
    ];
}

function auditCheckoutAndBookingMetadataReadiness(string $provider, ?array $vehicle): array
{
    if (!is_array($vehicle)) {
        return [
            'search_results_location_source' => 'n/a',
            'stripe_checkout_pickup_location_source' => 'n/a',
            'stripe_checkout_dropoff_location_source' => 'n/a',
            'booking_details_ready' => false,
            'notes' => ['No provider vehicle row available for this case/date.'],
        ];
    }

    $provider = strtolower($provider);
    $notes = [];

    $searchLocationSource = 'none';
    $pickupSource = 'none';
    $dropoffSource = 'none';
    $pickupDetails = null;
    $dropoffDetails = null;

    if (in_array($provider, ['greenmotion', 'usave'], true)) {
        $searchLocationSource = 'greenmotion-location-api';
        $pickupSource = 'request.location_details';
        $dropoffSource = 'request.dropoff_location_details_or_pickup';
        $pickupDetails = ['provider' => 'greenmotion-location-api'];
    } elseif (!empty($vehicle['location_details']) && is_array($vehicle['location_details'])) {
        $searchLocationSource = 'vehicle.location_details';
        $pickupSource = 'request.location_details';
        $pickupDetails = $vehicle['location_details'];

        if (!empty($vehicle['dropoff_location_details']) && is_array($vehicle['dropoff_location_details'])) {
            $dropoffSource = 'request.dropoff_location_details';
            $dropoffDetails = $vehicle['dropoff_location_details'];
        }
    }

    if ($provider === 'renteon') {
        if ($pickupDetails === null && !empty($vehicle['pickup_office']) && is_array($vehicle['pickup_office'])) {
            $pickupSource = 'vehicle.pickup_office_fallback';
            $pickupDetails = $vehicle['pickup_office'];
        }
        if ($dropoffDetails === null && !empty($vehicle['dropoff_office']) && is_array($vehicle['dropoff_office'])) {
            $dropoffSource = 'vehicle.dropoff_office_fallback';
            $dropoffDetails = $vehicle['dropoff_office'];
        }
    }

    if ($pickupDetails === null) {
        $pickupDetails = buildFallbackLocationFromVehicle($vehicle, 'pickup');
        if ($pickupDetails !== null) {
            $pickupSource = 'vehicle.generic_fallback';
        }
    }

    if ($dropoffDetails === null) {
        $dropoffDetails = buildFallbackLocationFromVehicle($vehicle, 'dropoff');
        if ($dropoffDetails !== null) {
            $dropoffSource = 'vehicle.generic_fallback';
        }
    }

    if ($dropoffDetails === null && $pickupDetails !== null) {
        $dropoffDetails = $pickupDetails;
        if ($dropoffSource === 'none') {
            $dropoffSource = 'pickup_fallback_copy';
        }
    }

    $bookingDetailsReady = $pickupDetails !== null;

    if (!$bookingDetailsReady) {
        $notes[] = 'Booking details page will fall back to booking.pickup_location string and may not have map coordinates.';
    }

    return [
        'search_results_location_source' => $searchLocationSource,
        'stripe_checkout_pickup_location_source' => $pickupSource,
        'stripe_checkout_dropoff_location_source' => $dropoffSource,
        'booking_details_ready' => $bookingDetailsReady,
        'notes' => $notes,
    ];
}

function sampleVehicle(?array $vehicle): ?array
{
    if (!is_array($vehicle)) {
        return null;
    }

    return [
        'id' => $vehicle['id'] ?? null,
        'source' => $vehicle['source'] ?? null,
        'provider_pickup_id' => $vehicle['provider_pickup_id'] ?? null,
        'latitude' => $vehicle['latitude'] ?? null,
        'longitude' => $vehicle['longitude'] ?? null,
        'full_vehicle_address' => $vehicle['full_vehicle_address'] ?? null,
        'has_location_details' => !empty($vehicle['location_details']) && is_array($vehicle['location_details']),
        'has_pickup_office' => !empty($vehicle['pickup_office']) && is_array($vehicle['pickup_office']),
        'pickup_station_name' => $vehicle['pickup_station_name'] ?? null,
        'pickup_address' => $vehicle['pickup_address'] ?? null,
        'office_address' => $vehicle['office_address'] ?? null,
    ];
}

function hasValidCoordinates($lat, $lng): bool
{
    if (!is_numeric($lat) || !is_numeric($lng)) {
        return false;
    }

    $lat = (float) $lat;
    $lng = (float) $lng;

    if ($lat < -90.0 || $lat > 90.0 || $lng < -180.0 || $lng > 180.0) {
        return false;
    }

    return !(abs($lat) < 0.000001 && abs($lng) < 0.000001);
}

function isNonEmptyString($value): bool
{
    return is_string($value) && trim($value) !== '';
}

function buildFallbackLocationFromVehicle(array $vehicle, string $leg = 'pickup'): ?array
{
    $isDropoff = $leg === 'dropoff';
    $officeKey = $isDropoff ? 'dropoff_office' : 'pickup_office';
    $office = (!empty($vehicle[$officeKey]) && is_array($vehicle[$officeKey])) ? $vehicle[$officeKey] : [];

    $stationName = $isDropoff
        ? ($vehicle['dropoff_station_name'] ?? null)
        : ($vehicle['pickup_station_name'] ?? null);
    $directAddress = $isDropoff
        ? ($vehicle['dropoff_address'] ?? null)
        : ($vehicle['pickup_address'] ?? null);

    $addressLine = $directAddress
        ?: ($office['address'] ?? null)
        ?: ($vehicle['office_address'] ?? null)
        ?: ($vehicle['full_vehicle_address'] ?? null);

    $name = $stationName
        ?: ($office['name'] ?? null)
        ?: ($vehicle['full_vehicle_address'] ?? null)
        ?: ($vehicle['pickup_location'] ?? null)
        ?: ($vehicle['location'] ?? null);

    $details = array_filter([
        'name' => $name,
        'address_1' => $addressLine,
        'address_city' => $office['town'] ?? null,
        'address_postcode' => $office['postal_code'] ?? null,
        'telephone' => $office['phone'] ?? ($vehicle['office_phone'] ?? null),
        'email' => $office['email'] ?? null,
        'collection_details' => $isDropoff
            ? ($office['dropoff_instructions'] ?? null)
            : ($office['pickup_instructions'] ?? null),
        'latitude' => $vehicle[$isDropoff ? 'dropoff_latitude' : 'latitude'] ?? ($vehicle['latitude'] ?? null),
        'longitude' => $vehicle[$isDropoff ? 'dropoff_longitude' : 'longitude'] ?? ($vehicle['longitude'] ?? null),
    ], static fn ($value) => $value !== null && $value !== '');

    return !empty($details) ? $details : null;
}
