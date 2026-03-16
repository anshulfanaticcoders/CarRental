<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Http;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$targetProviders = [
    ['provider' => 'greenmotion', 'pickup_id' => '61334'],
    ['provider' => 'surprice', 'pickup_id' => 'ATH'],
    ['provider' => 'okmobility', 'pickup_id' => '01'],
    ['provider' => 'locauto_rent', 'pickup_id' => 'FCO'],
    ['provider' => 'wheelsys', 'pickup_id' => 'MAIN'],
];

$candidateWindows = [
    ['from' => '2026-06-15', 'to' => '2026-06-20', 'start' => '10:00', 'end' => '10:00', 'age' => 35],
    ['from' => '2026-07-10', 'to' => '2026-07-15', 'start' => '10:00', 'end' => '10:00', 'age' => 35],
    ['from' => '2026-08-05', 'to' => '2026-08-10', 'start' => '10:00', 'end' => '10:00', 'age' => 35],
    ['from' => '2026-09-10', 'to' => '2026-09-15', 'start' => '10:00', 'end' => '10:00', 'age' => 35],
    ['from' => '2026-10-10', 'to' => '2026-10-15', 'start' => '10:00', 'end' => '10:00', 'age' => 35],
    ['from' => '2026-11-10', 'to' => '2026-11-15', 'start' => '10:00', 'end' => '10:00', 'age' => 35],
];

$locations = json_decode(file_get_contents(public_path('unified_locations.json')), true, 512, JSON_THROW_ON_ERROR);
$baseUrl = rtrim((string) env('APP_URL', 'http://127.0.0.1:8000'), '/');

$results = [
    'generated_at' => now()->toIso8601String(),
    'providers' => [],
];

foreach ($targetProviders as $target) {
    $provider = (string) $target['provider'];
    $pickupId = (string) $target['pickup_id'];
    $location = findLocation($locations, $provider, $pickupId);

    $row = [
        'provider' => $provider,
        'pickup_id' => $pickupId,
        'found_window' => null,
        'attempts' => [],
    ];

    if ($location === null) {
        $row['error'] = 'No matching unified location row in public/unified_locations.json';
        $results['providers'][] = $row;
        continue;
    }

    foreach ($candidateWindows as $window) {
        $query = [
            'provider' => $provider,
            'provider_pickup_id' => $pickupId,
            'unified_location_id' => $location['unified_location_id'],
            'date_from' => $window['from'],
            'date_to' => $window['to'],
            'start_time' => $window['start'],
            'end_time' => $window['end'],
            'age' => $window['age'],
            'where' => $location['name'] ?? null,
        ];

        $count = 0;
        $status = 'ok';
        $error = null;

        try {
            $frontend = dispatchFrontendSearch($baseUrl, $query);
            $vehicles = providerVehiclesFromFrontend($frontend, $provider);
            $count = count($vehicles);
        } catch (Throwable $e) {
            $status = 'error';
            $error = $e->getMessage();
        }

        $row['attempts'][] = [
            'window' => $window,
            'status' => $status,
            'frontend_provider_count' => $count,
            'error' => $error,
        ];

        if ($status === 'ok' && $count > 0 && $row['found_window'] === null) {
            $row['found_window'] = $window;
            // Keep collecting attempts for evidence coverage.
        }
    }

    $results['providers'][] = $row;
}

$outputDir = base_path('docs/reports');
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

$jsonPath = $outputDir . '/provider-future-inventory-probe-' . now()->format('Y-m-d-His') . '.json';
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

function dispatchFrontendSearch(string $baseUrl, array $query): array
{
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
