<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\VendorProfile;
use App\Models\Vehicle;
use App\Models\VehicleBenefit;
use App\Models\VehicleCategory;
use App\Models\VehicleImage;
use App\Models\VehicleOperatingHour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalVehicleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_gateway_can_fetch_internal_vehicles_for_a_grouped_internal_location(): void
    {
        config(['vrooem.internal_api_token' => 'gateway-test-token']);

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
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
        ]);

        VendorProfile::create([
            'user_id' => $vendor->id,
            'company_name' => 'Airport Fleet Co',
            'company_email' => 'fleet@example.com',
            'company_phone_number' => '+971500000000',
            'company_address' => 'Terminal 1',
            'status' => 'approved',
        ]);

        $referenceVehicle = $this->createVehicleAtAirport($vendor->id, $category->id, [
            'brand' => 'Toyota',
            'model' => 'Yaris',
        ]);

        $secondVehicle = $this->createVehicleAtAirport($vendor->id, $category->id, [
            'brand' => 'Nissan',
            'model' => 'Sunny',
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer gateway-test-token')
            ->getJson('/api/internal/vehicles?location_id=' . $referenceVehicle->id . '&pickup_date=2026-06-15&dropoff_date=2026-06-18&pickup_time=09:00&dropoff_time=09:00');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment([
            'id' => $referenceVehicle->id,
            'brand' => 'Toyota',
            'model' => 'Yaris',
            'location' => 'Dubai Airport Terminal 1, Dubai, United Arab Emirates',
        ]);
        $response->assertJsonFragment([
            'id' => $secondVehicle->id,
            'brand' => 'Nissan',
            'model' => 'Sunny',
            'location' => 'Dubai Airport Terminal 1, Dubai, United Arab Emirates',
        ]);

        $payload = $response->json('data.0');
        $this->assertSame('Dubai', $payload['vendor']['profile']['city']);
        $this->assertSame('AE', $payload['vendor']['profile']['country_code']);
        $this->assertSame('Airport Fleet Co', $payload['vendorProfileData']['company_name']);
        $this->assertSame('Airport Fleet Co', $payload['vendor_profile_data']['company_name']);
        $this->assertSame('https://example.com/internal/' . $referenceVehicle->id . '.jpg', $payload['images'][0]['image_url']);
        $this->assertSame(250, $payload['benefits']['km_per_day']);
        $this->assertSame(25, $payload['benefits']['min_driver_age']);
        $this->assertStringContainsString('2', $payload['benefits']['cancellation']);
    }

    public function test_gateway_can_authenticate_with_x_gateway_token_header(): void
    {
        config(['vrooem.internal_api_token' => 'gateway-test-token']);

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
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
        ]);

        VendorProfile::create([
            'user_id' => $vendor->id,
            'company_name' => 'Airport Fleet Co',
            'status' => 'approved',
        ]);

        $referenceVehicle = $this->createVehicleAtAirport($vendor->id, $category->id, [
            'brand' => 'Toyota',
            'model' => 'Yaris',
        ]);

        $response = $this
            ->withHeader('X-Gateway-Token', 'gateway-test-token')
            ->getJson('/api/internal/vehicles?location_id=' . $referenceVehicle->id . '&pickup_date=2026-06-15&dropoff_date=2026-06-18&pickup_time=09:00&dropoff_time=09:00');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'id' => $referenceVehicle->id,
            'brand' => 'Toyota',
            'model' => 'Yaris',
        ]);
    }

    private function createVehicleAtAirport(int $vendorId, int $categoryId, array $overrides = []): Vehicle
    {
        $vehicle = Vehicle::create(array_merge([
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
            'price_per_week' => 300,
            'price_per_month' => 1000,
            'preferred_price_type' => 'day',
            'pickup_times' => ['09:00'],
            'return_times' => ['09:00'],
        ], $overrides));

        VehicleImage::create([
            'vehicle_id' => $vehicle->id,
            'image_path' => 'vehicle_images/' . $vehicle->id . '.jpg',
            'image_url' => 'https://example.com/internal/' . $vehicle->id . '.jpg',
            'image_type' => 'primary',
        ]);

        VehicleBenefit::create([
            'vehicle_id' => $vehicle->id,
            'limited_km_per_day' => 1,
            'limited_km_per_day_range' => 250,
            'cancellation_available_per_day' => 1,
            'cancellation_available_per_day_date' => 2,
            'price_per_km_per_day' => 1.5,
            'minimum_driver_age' => 25,
        ]);

        foreach ([0, 3] as $dayOfWeek) {
            VehicleOperatingHour::create([
                'vehicle_id' => $vehicle->id,
                'day_of_week' => $dayOfWeek,
                'is_open' => true,
                'open_time' => '08:00',
                'close_time' => '20:00',
            ]);
        }

        return $vehicle;
    }
}
