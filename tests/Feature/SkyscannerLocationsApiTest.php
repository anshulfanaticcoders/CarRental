<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Models\VendorLocation;
use App\Models\VendorProfile;
use App\Services\LocationSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SkyscannerLocationsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_skyscanner_locations_api_requires_a_valid_api_key(): void
    {
        config([
            'skyscanner.api_key' => 'secret-key',
            'skyscanner.testing_access.auth_header' => 'x-api-key',
        ]);

        $response = $this->getJson('/api/skyscanner/locations');

        $response->assertUnauthorized();
        $response->assertJson([
            'error' => 'invalid_api_key',
        ]);
    }

    public function test_skyscanner_locations_api_returns_airport_and_non_airport_offices(): void
    {
        config([
            'skyscanner.api_key' => 'secret-key',
            'skyscanner.testing_access.auth_header' => 'x-api-key',
        ]);

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

        UserProfile::create([
            'user_id' => $vendor->id,
            'city' => 'Marrakech',
            'country' => 'Morocco',
        ]);

        VendorProfile::create([
            'user_id' => $vendor->id,
            'company_name' => 'Airport Fleet Co',
            'company_email' => 'fleet@example.com',
            'company_phone_number' => '+212600000000',
            'company_address' => 'Marrakech',
            'company_gst_number' => 'GST-RAK-' . $vendor->id,
            'status' => 'approved',
        ]);

        $airportLocation = VendorLocation::create([
            'vendor_id' => $vendor->id,
            'name' => 'Marrakech Airport (RAK)',
            'address_line_1' => 'Menara Airport',
            'city' => 'Marrakech',
            'country' => 'Morocco',
            'country_code' => 'MA',
            'latitude' => 31.6069,
            'longitude' => -8.0363,
            'location_type' => 'airport',
            'iata_code' => 'RAK',
            'is_active' => true,
        ]);

        $downtownLocation = VendorLocation::create([
            'vendor_id' => $vendor->id,
            'name' => 'Marrakech Downtown',
            'address_line_1' => 'Avenue Mohammed V',
            'city' => 'Marrakech',
            'country' => 'Morocco',
            'country_code' => 'MA',
            'latitude' => 31.6295,
            'longitude' => -7.9811,
            'location_type' => 'downtown',
            'iata_code' => null,
            'is_active' => true,
        ]);

        $this->createVehicle($vendor->id, $category->id, [
            'vendor_location_id' => $airportLocation->id,
            'location' => 'Marrakech Airport (RAK)',
            'location_type' => 'airport',
            'full_vehicle_address' => 'Menara Airport, Marrakech, Morocco',
            'city' => 'Marrakech',
            'country' => 'Morocco',
            'latitude' => 31.6069,
            'longitude' => -8.0363,
        ]);

        $this->createVehicle($vendor->id, $category->id, [
            'vendor_location_id' => $downtownLocation->id,
            'location' => 'Marrakech Downtown',
            'location_type' => 'downtown',
            'full_vehicle_address' => 'Avenue Mohammed V, Marrakech, Morocco',
            'city' => 'Marrakech',
            'country' => 'Morocco',
            'latitude' => 31.6295,
            'longitude' => -7.9811,
        ]);

        $response = $this
            ->withHeader('x-api-key', 'secret-key')
            ->getJson('/api/skyscanner/locations');

        $response->assertOk();
        $response->assertJsonPath('data.0.office_id', (string) $airportLocation->id);
        $response->assertJsonPath('data.0.location_type', 'airport');
        $response->assertJsonPath('data.0.iata', 'RAK');
        $response->assertJsonPath('data.0.country_code', 'MA');
        $response->assertJsonPath('data.1.office_id', (string) $downtownLocation->id);
        $response->assertJsonPath('data.1.location_type', 'downtown');
        $response->assertJsonPath('data.1.iata', null);
        $response->assertJsonPath('data.1.country_code', 'MA');
    }

    public function test_skyscanner_locations_api_returns_unified_locations_for_mixed_inventory(): void
    {
        config([
            'skyscanner.api_key' => 'secret-key',
            'skyscanner.testing_access.auth_header' => 'x-api-key',
            'skyscanner.inventory_scope' => 'mixed',
            'skyscanner.provider_whitelist' => ['greenmotion'],
        ]);

        $locationSearchService = Mockery::mock(LocationSearchService::class);
        $locationSearchService->shouldReceive('getAllLocations')
            ->once()
            ->with(1000)
            ->andReturn([
                [
                    'unified_location_id' => 3272373056,
                    'name' => 'Dubai Airport',
                    'address' => 'Airport Road',
                    'city' => 'Dubai',
                    'state' => 'Dubai',
                    'country' => 'United Arab Emirates',
                    'country_code' => 'AE',
                    'location_type' => 'airport',
                    'iata' => 'DXB',
                    'latitude' => 25.251369,
                    'longitude' => 55.347204,
                    'providers' => [
                        ['provider' => 'internal', 'pickup_id' => '1'],
                        ['provider' => 'greenmotion', 'pickup_id' => '61412'],
                    ],
                ],
                [
                    'unified_location_id' => 3280737750,
                    'name' => 'San Jose Downtown',
                    'address' => 'Central Avenue',
                    'city' => 'San Jose',
                    'state' => null,
                    'country' => 'Costa Rica',
                    'country_code' => 'CR',
                    'location_type' => 'downtown',
                    'iata' => null,
                    'latitude' => 9.925916,
                    'longitude' => -84.095894,
                    'providers' => [
                        ['provider' => 'usave', 'pickup_id' => '888'],
                    ],
                ],
            ]);

        $this->app->instance(LocationSearchService::class, $locationSearchService);

        $response = $this
            ->withHeader('x-api-key', 'secret-key')
            ->getJson('/api/skyscanner/locations');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.office_id', '3272373056');
        $response->assertJsonPath('data.0.location_type', 'airport');
        $response->assertJsonPath('data.0.iata', 'DXB');
        $response->assertJsonPath('data.0.country_code', 'AE');
    }

    private function createVehicle(int $vendorId, int $categoryId, array $overrides = []): Vehicle
    {
        return Vehicle::create(array_merge([
            'vendor_id' => $vendorId,
            'category_id' => $categoryId,
            'brand' => 'Default',
            'model' => 'Default',
            'transmission' => 'automatic',
            'fuel' => 'petrol',
            'seating_capacity' => 5,
            'number_of_doors' => 4,
            'luggage_capacity' => 2,
            'horsepower' => 110,
            'co2' => '120',
            'color' => 'white',
            'mileage' => 20,
            'status' => 'available',
            'features' => json_encode(['Air Conditioning']),
            'featured' => false,
            'security_deposit' => 200,
            'payment_method' => json_encode(['credit_card']),
            'guidelines' => 'Bring passport and license.',
            'terms_policy' => 'Return with same fuel level.',
            'fuel_policy' => 'full_to_full',
            'price_per_day' => 55,
            'price_per_week' => 300,
            'price_per_month' => 1000,
            'preferred_price_type' => 'day',
            'pickup_times' => ['09:00'],
            'return_times' => ['09:00'],
        ], $overrides));
    }
}
