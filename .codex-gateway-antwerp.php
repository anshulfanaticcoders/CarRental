<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = $app->make(App\Services\VrooemGatewayService::class);

$result = $service->searchVehicles([
    'unified_location_id' => 3038777513,
    'pickup_date' => '2026-05-05',
    'dropoff_date' => '2026-05-12',
    'pickup_time' => '09:00',
    'dropoff_time' => '09:00',
    'driver_age' => 35,
    'dropoff_unified_location_id' => 3038777513,
    'provider_locations' => [
        [
            'provider' => 'internal',
            'pickup_id' => '2',
            'original_name' => '70 nijverheidsstraat, Antwerp, Antwerpen, Belgium',
            'dropoffs' => [],
            'latitude' => 51.199993,
            'longitude' => 4.431101,
            'supports_one_way' => false,
            'extended_location_code' => null,
            'extended_dropoff_code' => null,
            'country_code' => 'BE',
            'iata' => null,
            'provider_code' => null,
        ],
        [
            'provider' => 'internal',
            'pickup_id' => '4',
            'original_name' => 'Bosschaert de Bouwellei, Antwerp, Antwerpen, Belgium',
            'dropoffs' => [],
            'latitude' => 51.222003,
            'longitude' => 4.470379,
            'supports_one_way' => false,
            'extended_location_code' => null,
            'extended_dropoff_code' => null,
            'country_code' => 'BE',
            'iata' => null,
            'provider_code' => null,
        ],
        [
            'provider' => 'renteon',
            'pickup_id' => 'BE-ANT-DT',
            'original_name' => 'Antwerpen downtown',
            'dropoffs' => [],
            'latitude' => null,
            'longitude' => null,
            'supports_one_way' => false,
            'extended_location_code' => null,
            'extended_dropoff_code' => null,
            'country_code' => 'BE',
            'iata' => null,
            'provider_code' => null,
        ],
    ],
    'country_code' => 'BE',
]);

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
