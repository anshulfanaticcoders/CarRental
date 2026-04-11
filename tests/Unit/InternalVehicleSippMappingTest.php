<?php

namespace Tests\Unit;

use App\Services\Search\InternalSearchVehicleFactory;
use Tests\TestCase;

class InternalVehicleSippMappingTest extends TestCase
{
    public function test_internal_vehicle_payload_preserves_explicit_sipp_code(): void
    {
        $factory = app(InternalSearchVehicleFactory::class);

        $payload = $factory->make([
            'id' => 100,
            'brand' => 'Toyota',
            'model' => 'Yaris',
            'transmission' => 'automatic',
            'fuel' => 'petrol',
            'seating_capacity' => 5,
            'number_of_doors' => 4,
            'doors' => 4,
            'small_bags' => 1,
            'medium_bags' => 1,
            'large_bags' => 1,
            'air_conditioning' => true,
            'sipp_code' => 'ECMR',
            'price_per_day' => 55,
            'currency' => 'EUR',
            'latitude' => 31.6069,
            'longitude' => -8.0363,
            'full_vehicle_address' => 'Menara Airport, Marrakech, Morocco',
            'location' => 'Marrakech Airport',
            'city' => 'Marrakech',
            'country' => 'Morocco',
            'images' => [],
            'benefits' => [],
        ], 3, [
            'pickup_location_id' => '101',
            'dropoff_location_id' => '101',
        ]);

        $this->assertSame('ECMR', data_get($payload, 'specs.sipp_code'));
    }
}
