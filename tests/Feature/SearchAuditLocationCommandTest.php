<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Models\VendorLocation;
use App\Services\VrooemGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SearchAuditLocationCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_reports_when_laravel_adds_verified_internal_mapping_missing_from_gateway(): void
    {
        $location = $this->createInternalAirportLocation();

        $gateway = Mockery::mock(VrooemGatewayService::class);
        $gateway->shouldReceive('getLocation')
            ->twice()
            ->with(3385755165)
            ->andReturn([
                'unified_location_id' => 3385755165,
                'name' => 'Dubai Airport (DXB)',
                'city' => 'Dubai',
                'country' => 'United Arab Emirates',
                'country_code' => 'AE',
                'location_type' => 'airport',
                'iata' => 'DXB',
                'providers' => [
                    ['provider' => 'surprice', 'pickup_id' => 'DXB:DXBA01'],
                ],
            ]);
        $gateway->shouldReceive('searchLocations')
            ->zeroOrMoreTimes()
            ->andReturn(['results' => []]);
        $this->app->instance(VrooemGatewayService::class, $gateway);

        $this->artisan('search:audit-location', ['unified_location_id' => 3385755165])
            ->expectsOutputToContain('Gateway is missing internal')
            ->expectsOutputToContain((string) $location->id)
            ->assertSuccessful();
    }

    private function createInternalAirportLocation(): VendorLocation
    {
        $category = VehicleCategory::create([
            'name' => 'Economy',
            'slug' => 'economy',
            'description' => 'Economy vehicles',
            'status' => true,
        ]);

        $vendor = User::factory()->create([
            'role' => 'vendor',
            'status' => 'active',
        ]);

        $location = VendorLocation::create([
            'vendor_id' => $vendor->id,
            'name' => 'Dubai Airport (DXB)',
            'address_line_1' => 'Dubai Airport Terminal 1',
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
            'country_code' => 'AE',
            'latitude' => 25.251369,
            'longitude' => 55.347204,
            'location_type' => 'airport',
            'iata_code' => 'DXB',
            'is_active' => true,
        ]);

        Vehicle::create([
            'vendor_id' => $vendor->id,
            'vendor_location_id' => $location->id,
            'category_id' => $category->id,
            'brand' => 'Toyota',
            'model' => 'Yaris',
            'transmission' => 'automatic',
            'fuel' => 'petrol',
            'seating_capacity' => 5,
            'number_of_doors' => 4,
            'luggage_capacity' => 2,
            'horsepower' => 110,
            'co2' => '120',
            'color' => 'white',
            'mileage' => 20,
            'location' => 'Dubai Airport (DXB)',
            'location_type' => 'airport',
            'latitude' => 25.251369,
            'longitude' => 55.347204,
            'city' => 'Dubai',
            'state' => null,
            'country' => 'United Arab Emirates',
            'full_vehicle_address' => 'Dubai Airport Terminal 1, Dubai, United Arab Emirates',
            'status' => 'available',
            'features' => json_encode(['Air Conditioning']),
            'featured' => false,
            'security_deposit' => 200,
            'payment_method' => json_encode(['credit_card']),
            'guidelines' => 'Bring passport and license.',
            'terms_policy' => 'Return with same fuel level.',
            'price_per_day' => 55,
            'preferred_price_type' => 'day',
        ]);

        return $location;
    }
}
