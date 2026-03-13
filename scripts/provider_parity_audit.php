<?php

declare(strict_types=1);

use App\Services\AdobeCarService;
use App\Services\FavricaService;
use App\Services\GatewaySearchParamsBuilder;
use App\Services\GreenMotionService;
use App\Services\LocautoRentService;
use App\Services\OkMobilityService;
use App\Services\RecordGoService;
use App\Services\RenteonService;
use App\Services\SicilyByCarService;
use App\Services\SurpriceService;
use App\Services\VrooemGatewayService;
use App\Services\WheelsysService;
use App\Services\XDriveService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

/**
 * Fixed provider cases pulled from repo docs/apidata and normalized to the current
 * Laravel search contract.
 */
$cases = [
    [
        'provider' => 'greenmotion',
        'pickup_id' => '61334',
        'pickup_date' => '2026-03-15',
        'dropoff_date' => '2026-03-20',
        'pickup_time' => '10:00',
        'dropoff_time' => '10:00',
        'driver_age' => 30,
        'direct' => [
            'type' => 'greenmotion',
            'expected_doc_count' => 7,
        ],
    ],
    [
        'provider' => 'renteon',
        'pickup_id' => 'GR-ATH-ATH',
        'pickup_date' => '2026-06-15',
        'dropoff_date' => '2026-06-20',
        'pickup_time' => '10:00',
        'dropoff_time' => '10:00',
        'driver_age' => 30,
        'direct' => [
            'type' => 'renteon',
            'provider_code' => 'LetsDrive',
            'expected_doc_count' => 14,
        ],
    ],
    [
        'provider' => 'surprice',
        'pickup_id' => 'ATH',
        'pickup_date' => '2026-03-15',
        'dropoff_date' => '2026-03-20',
        'pickup_time' => '10:00',
        'dropoff_time' => '10:00',
        'driver_age' => 30,
        'direct' => [
            'type' => 'surprice',
            'pickup_ext_code' => 'ATHA01',
        ],
    ],
    [
        'provider' => 'recordgo',
        'pickup_id' => '35001',
        'pickup_date' => '2026-05-06',
        'dropoff_date' => '2026-05-14',
        'pickup_time' => '09:00',
        'dropoff_time' => '09:00',
        'driver_age' => 35,
        'direct' => [
            'type' => 'recordgo',
            'country' => 'PT',
            'sell_code' => 110,
            'expected_doc_count' => 12,
        ],
    ],
    [
        'provider' => 'xdrive',
        'pickup_id' => '52',
        'pickup_date' => '2026-06-15',
        'dropoff_date' => '2026-06-20',
        'pickup_time' => '09:00',
        'dropoff_time' => '09:00',
        'driver_age' => 30,
        'direct' => [
            'type' => 'xdrive',
            'currency' => 'EURO',
            'expected_doc_count' => 37,
        ],
    ],
    [
        'provider' => 'favrica',
        'pickup_id' => '8',
        'pickup_date' => '2026-06-15',
        'dropoff_date' => '2026-06-20',
        'pickup_time' => '09:00',
        'dropoff_time' => '09:00',
        'driver_age' => 30,
        'direct' => [
            'type' => 'favrica',
            'currency' => 'EURO',
            'expected_doc_count' => 34,
        ],
    ],
    [
        'provider' => 'okmobility',
        'pickup_id' => '01',
        'pickup_date' => '2026-03-15',
        'dropoff_date' => '2026-03-20',
        'pickup_time' => '10:00',
        'dropoff_time' => '10:00',
        'driver_age' => 35,
        'direct' => [
            'type' => 'okmobility',
        ],
    ],
    [
        'provider' => 'locauto_rent',
        'pickup_id' => 'FCO',
        'pickup_date' => '2026-03-15',
        'dropoff_date' => '2026-03-20',
        'pickup_time' => '09:00',
        'dropoff_time' => '09:00',
        'driver_age' => 35,
        'direct' => [
            'type' => 'locauto_rent',
        ],
    ],
    [
        'provider' => 'sicily_by_car',
        'pickup_id' => 'IT011',
        'pickup_date' => '2026-06-15',
        'dropoff_date' => '2026-06-20',
        'pickup_time' => '09:00',
        'dropoff_time' => '09:00',
        'driver_age' => 30,
        'direct' => [
            'type' => 'sicily_by_car',
        ],
    ],
    [
        'provider' => 'adobe',
        'pickup_id' => 'SJO',
        'pickup_date' => '2026-06-15',
        'dropoff_date' => '2026-06-20',
        'pickup_time' => '09:00',
        'dropoff_time' => '09:00',
        'driver_age' => 30,
        'direct' => [
            'type' => 'adobe',
        ],
    ],
    [
        'provider' => 'wheelsys',
        'pickup_id' => 'MAIN',
        'pickup_date' => '2026-03-15',
        'dropoff_date' => '2026-03-20',
        'pickup_time' => '10:00',
        'dropoff_time' => '10:00',
        'driver_age' => 30,
        'direct' => [
            'type' => 'wheelsys',
        ],
    ],
];

$locations = json_decode(file_get_contents(public_path('unified_locations.json')), true, 512, JSON_THROW_ON_ERROR);
$paramsBuilder = app(GatewaySearchParamsBuilder::class);
$gatewayService = app(VrooemGatewayService::class);
$results = [
    'generated_at' => now()->toIso8601String(),
    'laravel_app_env' => app()->environment(),
    'gateway_url' => config('vrooem.url'),
    'cases' => [],
];

foreach ($cases as $case) {
    $row = [
        'provider' => $case['provider'],
        'pickup_id' => $case['pickup_id'],
        'search' => Arr::only($case, ['pickup_date', 'dropoff_date', 'pickup_time', 'dropoff_time', 'driver_age']),
    ];

    $location = findLocation($locations, (string) $case['provider'], (string) $case['pickup_id']);
    if ($location === null) {
        $row['status'] = 'blocked';
        $row['reason'] = 'No matching unified location row in public/unified_locations.json';
        $results['cases'][] = $row;
        continue;
    }

    $query = [
        'provider' => $case['provider'],
        'provider_pickup_id' => $case['pickup_id'],
        'unified_location_id' => $location['unified_location_id'],
        'date_from' => $case['pickup_date'],
        'date_to' => $case['dropoff_date'],
        'start_time' => $case['pickup_time'],
        'end_time' => $case['dropoff_time'],
        'age' => $case['driver_age'],
        'where' => $location['name'] ?? null,
    ];

    $row['location'] = Arr::only($location, ['unified_location_id', 'name', 'city', 'country', 'country_code']);

    try {
        $gatewayParams = $paramsBuilder->build($query);
        $gateway = $gatewayService->searchVehicles($gatewayParams);
        $frontend = dispatchFrontendSearch($query);
        $direct = runDirectProviderAudit($case, $location);

        $row['gateway'] = summarizeGatewayResult($gateway);
        $row['frontend'] = summarizeFrontendResult($frontend, (string) $case['provider']);
        $row['direct'] = $direct;
        $row['mismatches'] = classifyMismatches($row['direct'], $row['gateway'], $row['frontend']);
        $row['status'] = empty($row['mismatches']) ? 'parity-ok' : 'mismatch';
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

$jsonPath = $outputDir . '/provider-parity-audit-' . now()->format('Y-m-d-His') . '.json';
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

function summarizeGatewayResult(array $gateway): array
{
    $vehicles = array_values($gateway['vehicles'] ?? []);
    $supplierResults = array_values($gateway['supplier_results'] ?? []);

    return [
        'count' => count($vehicles),
        'response_time_ms' => $gateway['response_time_ms'] ?? null,
        'search_id' => $gateway['search_id'] ?? null,
        'supplier_status' => array_map(static function (array $result): array {
            return [
                'supplier_id' => $result['supplier_id'] ?? null,
                'vehicle_count' => $result['vehicle_count'] ?? 0,
                'error' => $result['error'] ?? null,
            ];
        }, $supplierResults),
        'samples' => array_map('normalizeGatewayVehicle', array_slice($vehicles, 0, 3)),
    ];
}

function summarizeFrontendResult(array $frontend, string $provider): array
{
    $props = $frontend['props'] ?? [];
    $vehicles = $props['vehicles']['data'] ?? [];
    $providerVehicles = array_values(array_filter($vehicles, static function (array $vehicle) use ($provider): bool {
        return (string) ($vehicle['source'] ?? '') === $provider;
    }));

    $providerStatus = array_values(array_filter($props['providerStatus'] ?? [], static function (array $status) use ($provider): bool {
        return (string) ($status['provider'] ?? '') === $provider;
    }));

    return [
        'total_count' => (int) ($props['vehicles']['total'] ?? 0),
        'provider_filtered_count' => count($providerVehicles),
        'via_gateway' => $props['via_gateway'] ?? null,
        'search_error' => $props['searchError'] ?? null,
        'provider_status' => $providerStatus,
        'samples' => array_map('normalizeFrontendVehicle', array_slice($providerVehicles, 0, 3)),
    ];
}

function normalizeGatewayVehicle(array $vehicle): array
{
    return [
        'supplier_id' => $vehicle['supplier_id'] ?? null,
        'supplier_vehicle_id' => $vehicle['supplier_vehicle_id'] ?? null,
        'make' => $vehicle['make'] ?? null,
        'model' => $vehicle['model'] ?? null,
        'category' => $vehicle['category'] ?? null,
        'currency' => $vehicle['pricing']['currency'] ?? null,
        'total_price' => $vehicle['pricing']['total_price'] ?? null,
        'daily_rate' => $vehicle['pricing']['daily_rate'] ?? null,
        'pickup_id' => $vehicle['pickup_location']['supplier_location_id'] ?? null,
    ];
}

function normalizeFrontendVehicle(array $vehicle): array
{
    return [
        'source' => $vehicle['source'] ?? null,
        'provider_code' => $vehicle['provider_code'] ?? null,
        'brand' => $vehicle['brand'] ?? null,
        'model' => $vehicle['model'] ?? null,
        'category' => $vehicle['category'] ?? null,
        'currency' => $vehicle['currency'] ?? null,
        'total_price' => $vehicle['total_price'] ?? null,
        'price_per_day' => $vehicle['price_per_day'] ?? null,
        'pickup_id' => $vehicle['provider_pickup_id'] ?? null,
    ];
}

function classifyMismatches(array $direct, array $gateway, array $frontend): array
{
    $issues = [];

    if ($direct['count'] !== null && $gateway['count'] !== $direct['count']) {
        $issues[] = 'direct-to-gateway-count-mismatch';
    }

    if (($frontend['provider_filtered_count'] ?? null) !== $gateway['count']) {
        $issues[] = 'gateway-to-frontend-count-mismatch';
    }

    if (!empty($frontend['search_error'])) {
        $issues[] = 'frontend-search-error';
    }

    if (($frontend['via_gateway'] ?? false) !== true) {
        $issues[] = 'frontend-not-marked-via-gateway';
    }

    $gatewayPrices = array_map(static fn (array $item) => round((float) ($item['total_price'] ?? 0), 2), $gateway['samples']);
    $frontendPrices = array_map(static fn (array $item) => round((float) ($item['total_price'] ?? 0), 2), $frontend['samples']);
    if ($gatewayPrices !== $frontendPrices) {
        $issues[] = 'gateway-to-frontend-sample-price-mismatch';
    }

    return $issues;
}

function runDirectProviderAudit(array $case, array $location): array
{
    return match ($case['direct']['type']) {
        'greenmotion' => directGreenMotion($case),
        'renteon' => directRenteon($case),
        'surprice' => directSurprice($case),
        'recordgo' => directRecordGo($case),
        'xdrive' => directXDrive($case),
        'favrica' => directFavrica($case),
        'okmobility' => directOkMobility($case),
        'locauto_rent' => directLocauto($case),
        'sicily_by_car' => directSicilyByCar($case),
        'adobe' => directAdobe($case),
        'wheelsys' => directWheelsys($case),
        default => ['status' => 'unsupported', 'count' => null, 'samples' => []],
    };
}

function directGreenMotion(array $case): array
{
    $service = app(GreenMotionService::class)->setProvider('greenmotion');
    $xml = $service->getVehicles($case['pickup_id'], $case['pickup_date'], $case['pickup_time'], $case['dropoff_date'], $case['dropoff_time'], $case['driver_age']);
    if (!is_string($xml) || $xml === '') {
        return ['status' => 'empty', 'count' => null, 'samples' => []];
    }

    $sx = @simplexml_load_string($xml);
    $vehicles = $sx ? ($sx->xpath('//vehicle') ?: []) : [];

    return [
        'status' => 'ok',
        'count' => count($vehicles),
        'samples' => [],
        'expected_doc_count' => $case['direct']['expected_doc_count'] ?? null,
    ];
}

function directRenteon(array $case): array
{
    $vehicles = app(RenteonService::class)->getTransformedVehicles(
        $case['pickup_id'],
        $case['pickup_id'],
        $case['pickup_date'],
        $case['pickup_time'],
        $case['dropoff_date'],
        $case['dropoff_time'],
        ['driver_age' => $case['driver_age'], 'currency' => 'EUR'],
    );

    return [
        'status' => 'ok',
        'count' => count($vehicles),
        'samples' => array_map('normalizeFrontendVehicle', array_slice($vehicles, 0, 3)),
        'expected_doc_count' => $case['direct']['expected_doc_count'] ?? null,
    ];
}

function directSurprice(array $case): array
{
    $vehicles = app(SurpriceService::class)->getTransformedVehicles(
        $case['pickup_id'],
        $case['direct']['pickup_ext_code'],
        $case['pickup_id'],
        $case['direct']['pickup_ext_code'],
        $case['pickup_date'] . 'T' . $case['pickup_time'] . ':00',
        $case['dropoff_date'] . 'T' . $case['dropoff_time'] . ':00',
        ['driver_age' => $case['driver_age']],
    );

    return [
        'status' => 'ok',
        'count' => count($vehicles),
        'samples' => array_map('normalizeFrontendVehicle', array_slice($vehicles, 0, 3)),
    ];
}

function directRecordGo(array $case): array
{
    $payload = [
        'partnerUser' => app(RecordGoService::class)->getPartnerUser(),
        'country' => $case['direct']['country'],
        'sellCode' => $case['direct']['sell_code'],
        'pickupBranch' => (int) $case['pickup_id'],
        'dropoffBranch' => (int) $case['pickup_id'],
        'pickupDateTime' => $case['pickup_date'] . 'T' . $case['pickup_time'] . ':00',
        'dropoffDateTime' => $case['dropoff_date'] . 'T' . $case['dropoff_time'] . ':00',
        'driverAge' => $case['driver_age'],
        'language' => 'en',
    ];

    $response = app(RecordGoService::class)->getAvailability($payload);
    $acriss = $response['data']['acriss'] ?? [];

    return [
        'status' => ($response['ok'] ?? false) ? 'ok' : 'error',
        'count' => is_array($acriss) ? count($acriss) : null,
        'samples' => array_map(static function (array $item): array {
            $product = $item['products'][0] ?? [];
            $image = $item['imagesArray'][0] ?? [];
            return [
                'brand' => $image['acrissDisplayName'] ?? null,
                'model' => $item['acrissCode'] ?? null,
                'category' => $item['acrissCode'] ?? null,
                'currency' => $product['currency'] ?? null,
                'total_price' => $product['priceTaxIncBooking'] ?? null,
                'price_per_day' => $product['priceTaxIncDay'] ?? null,
                'pickup_id' => null,
            ];
        }, array_slice(is_array($acriss) ? $acriss : [], 0, 3)),
        'errors' => $response['errors'] ?? null,
        'expected_doc_count' => $case['direct']['expected_doc_count'] ?? null,
    ];
}

function directXDrive(array $case): array
{
    $response = app(XDriveService::class)->searchRez(
        $case['pickup_id'],
        $case['pickup_id'],
        $case['pickup_date'],
        $case['pickup_time'],
        $case['dropoff_date'],
        $case['dropoff_time'],
        $case['direct']['currency'],
    );

    return [
        'status' => 'ok',
        'count' => count($response),
        'samples' => array_map(static function (array $item): array {
            return [
                'brand' => $item['brand'] ?? null,
                'model' => $item['type'] ?? $item['car_name'] ?? null,
                'category' => $item['group_str'] ?? $item['category'] ?? null,
                'currency' => $item['currency'] ?? null,
                'total_price' => $item['total_rental'] ?? null,
                'price_per_day' => $item['daily_rental'] ?? null,
                'pickup_id' => null,
            ];
        }, array_slice($response, 0, 3)),
        'expected_doc_count' => $case['direct']['expected_doc_count'] ?? null,
    ];
}

function directFavrica(array $case): array
{
    $response = app(FavricaService::class)->searchRez(
        $case['pickup_id'],
        $case['pickup_id'],
        $case['pickup_date'],
        $case['pickup_time'],
        $case['dropoff_date'],
        $case['dropoff_time'],
        $case['direct']['currency'],
    );

    return [
        'status' => 'ok',
        'count' => count($response),
        'samples' => array_map(static function (array $item): array {
            return [
                'brand' => $item['brand'] ?? null,
                'model' => $item['type'] ?? $item['car_name'] ?? null,
                'category' => $item['group_str'] ?? $item['category'] ?? null,
                'currency' => $item['currency'] ?? null,
                'total_price' => $item['total_rental'] ?? null,
                'price_per_day' => $item['daily_rental'] ?? null,
                'pickup_id' => null,
            ];
        }, array_slice($response, 0, 3)),
        'expected_doc_count' => $case['direct']['expected_doc_count'] ?? null,
    ];
}

function directOkMobility(array $case): array
{
    $xml = app(OkMobilityService::class)->getVehicles(
        $case['pickup_id'],
        $case['pickup_id'],
        $case['pickup_date'],
        $case['pickup_time'],
        $case['dropoff_date'],
        $case['dropoff_time'],
        ['age' => $case['driver_age']]
    );

    if (!is_string($xml) || $xml === '') {
        return ['status' => 'empty', 'count' => null, 'samples' => []];
    }

    $sx = @simplexml_load_string($xml);
    $nodes = $sx ? ($sx->xpath('//*[local-name()="getMultiplePrice"]') ?: []) : [];

    return [
        'status' => 'ok',
        'count' => count($nodes),
        'samples' => [],
    ];
}

function directLocauto(array $case): array
{
    $service = app(LocautoRentService::class);
    $xml = $service->getVehicles($case['pickup_id'], $case['pickup_date'], $case['pickup_time'], $case['dropoff_date'], $case['dropoff_time'], $case['driver_age']);
    $vehicles = is_string($xml) ? $service->parseVehicleResponse($xml) : [];

    return [
        'status' => 'ok',
        'count' => count($vehicles),
        'samples' => array_map('normalizeFrontendVehicle', array_slice($vehicles, 0, 3)),
    ];
}

function directSicilyByCar(array $case): array
{
    $payload = [
        'pickUpLocation' => $case['pickup_id'],
        'dropOffLocation' => $case['pickup_id'],
        'pickUpDateTime' => $case['pickup_date'] . 'T' . $case['pickup_time'] . ':00',
        'dropOffDateTime' => $case['dropoff_date'] . 'T' . $case['dropoff_time'] . ':00',
        'driverAge' => $case['driver_age'],
    ];
    $response = app(SicilyByCarService::class)->offersAvailability($payload);
    $offers = $response['data']['offers'] ?? [];

    return [
        'status' => ($response['ok'] ?? false) ? 'ok' : 'error',
        'count' => is_array($offers) ? count($offers) : null,
        'samples' => array_map(static function (array $offer): array {
            return [
                'brand' => $offer['vehicle']['description'] ?? null,
                'model' => $offer['vehicle']['sipp'] ?? null,
                'category' => $offer['rate']['description'] ?? null,
                'currency' => $offer['currency'] ?? null,
                'total_price' => $offer['totalPrices']['total'] ?? null,
                'price_per_day' => null,
                'pickup_id' => $offer['pickupLocation']['id'] ?? null,
            ];
        }, array_slice(is_array($offers) ? $offers : [], 0, 3)),
        'errors' => $response['errors'] ?? null,
    ];
}

function directAdobe(array $case): array
{
    $response = app(AdobeCarService::class)->getAvailableVehicles([
        'pickupoffice' => $case['pickup_id'],
        'returnoffice' => $case['pickup_id'],
        'startdate' => $case['pickup_date'] . ' ' . $case['pickup_time'],
        'enddate' => $case['dropoff_date'] . ' ' . $case['dropoff_time'],
    ]);
    $vehicles = $response['data'] ?? [];

    return [
        'status' => !empty($response['result']) ? 'ok' : 'error',
        'count' => is_array($vehicles) ? count($vehicles) : null,
        'samples' => array_map(static function (array $vehicle): array {
            return [
                'brand' => $vehicle['model'] ?? null,
                'model' => $vehicle['category'] ?? null,
                'category' => $vehicle['type'] ?? null,
                'currency' => 'USD',
                'total_price' => $vehicle['tdr'] ?? null,
                'price_per_day' => $vehicle['tdr'] ?? null,
                'pickup_id' => null,
            ];
        }, array_slice(is_array($vehicles) ? $vehicles : [], 0, 3)),
    ];
}

function directWheelsys(array $case): array
{
    $response = app(WheelsysService::class)->getVehicles(
        $case['pickup_id'],
        $case['pickup_id'],
        \Carbon\Carbon::parse($case['pickup_date'])->format('d/m/Y'),
        $case['pickup_time'],
        \Carbon\Carbon::parse($case['dropoff_date'])->format('d/m/Y'),
        $case['dropoff_time'],
    );
    $rates = $response['Rates'] ?? [];

    return [
        'status' => 'ok',
        'count' => is_array($rates) ? count($rates) : null,
        'samples' => array_map(static function (array $rate): array {
            return [
                'brand' => $rate['SampleModel'] ?? null,
                'model' => $rate['GroupCode'] ?? null,
                'category' => $rate['GroupCode'] ?? null,
                'currency' => 'USD',
                'total_price' => isset($rate['TotalRate']) ? ((float) $rate['TotalRate'] / 100) : null,
                'price_per_day' => isset($rate['TotalRate']) ? ((float) $rate['TotalRate'] / 100) : null,
                'pickup_id' => null,
            ];
        }, array_slice(is_array($rates) ? $rates : [], 0, 3)),
        'quote_id' => $response['Id'] ?? null,
    ];
}
