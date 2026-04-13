<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\Vehicle;
use App\Models\VehicleBenefit;
use App\Models\VehicleCategory;
use App\Models\VehicleImage;
use App\Models\VehicleOperatingHour;
use App\Models\VendorLocation;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkyscannerSearchApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_skyscanner_search_api_returns_quotes_for_authenticated_requests(): void
    {
        config([
            'skyscanner.api_key' => 'secret-key',
            'skyscanner.testing_access.auth_header' => 'x-api-key',
            'skyscanner.case_id' => 'PSM-46100',
            'skyscanner.quote_ttl_minutes' => 30,
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
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
        ]);

        VendorProfile::create([
            'user_id' => $vendor->id,
            'company_name' => 'Airport Fleet Co',
            'company_email' => 'fleet@example.com',
            'company_phone_number' => '+971500000000',
            'company_address' => 'Terminal 1',
            'company_gst_number' => 'GST-DXB-' . $vendor->id,
            'status' => 'approved',
        ]);

        $referenceVehicle = $this->createVehicleAtAirport($vendor->id, $category->id, [
            'brand' => 'Toyota',
            'model' => 'Yaris',
        ]);

        $response = $this
            ->withHeader('x-api-key', 'secret-key')
            ->postJson('/api/skyscanner/car-hire/search', [
                'pickup_location_id' => $referenceVehicle->id,
                'dropoff_location_id' => $referenceVehicle->id,
                'pickup_date' => '2026-06-15',
                'pickup_time' => '09:00',
                'dropoff_date' => '2026-06-18',
                'dropoff_time' => '09:00',
                'driver_age' => 35,
                'currency' => 'EUR',
            ]);

        $response->assertOk();
        $response->assertJsonCount(1, 'quotes');
        $response->assertJsonPath('quotes.0.vehicle.provider_vehicle_id', (string) $referenceVehicle->id);
        $response->assertJsonPath('quotes.0.vehicle.display_name', 'Toyota Yaris');
        $response->assertJsonPath('excluded_vehicle_ids', []);
    }

    public function test_skyscanner_search_api_accepts_canonical_vendor_location_ids(): void
    {
        config([
            'skyscanner.api_key' => 'secret-key',
            'skyscanner.testing_access.auth_header' => 'x-api-key',
            'skyscanner.case_id' => 'PSM-46100',
            'skyscanner.quote_ttl_minutes' => 30,
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
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
        ]);

        VendorProfile::create([
            'user_id' => $vendor->id,
            'company_name' => 'Airport Fleet Co',
            'company_email' => 'fleet@example.com',
            'company_phone_number' => '+971500000000',
            'company_address' => 'Terminal 1',
            'company_gst_number' => 'GST-DXB-' . $vendor->id,
            'status' => 'approved',
        ]);

        $location = VendorLocation::create([
            'vendor_id' => $vendor->id,
            'name' => 'Dubai Airport (DXB)',
            'code' => 'vl-' . $vendor->id . '-dxb',
            'address_line_1' => 'Dubai Airport Terminal 1',
            'city' => 'Dubai',
            'state' => null,
            'country' => 'United Arab Emirates',
            'country_code' => 'AE',
            'latitude' => 25.251369,
            'longitude' => 55.347204,
            'location_type' => 'airport',
            'iata_code' => 'DXB',
            'is_active' => true,
        ]);

        $referenceVehicle = $this->createVehicleAtAirport($vendor->id, $category->id, [
            'brand' => 'Toyota',
            'model' => 'Yaris',
            'vendor_location_id' => $location->id,
        ]);

        $response = $this
            ->withHeader('x-api-key', 'secret-key')
            ->postJson('/api/skyscanner/car-hire/search', [
                'pickup_location_id' => $location->id,
                'dropoff_location_id' => $location->id,
                'pickup_date' => '2026-06-15',
                'pickup_time' => '09:00',
                'dropoff_date' => '2026-06-18',
                'dropoff_time' => '09:00',
                'driver_age' => 35,
                'currency' => 'EUR',
            ]);

        $response->assertOk();
        $response->assertJsonCount(1, 'quotes');
        $response->assertJsonPath('quotes.0.vehicle.provider_vehicle_id', (string) $referenceVehicle->id);
        $response->assertJsonPath('excluded_vehicle_ids', []);
    }

    public function test_skyscanner_search_api_supports_rest_style_uri_parameters(): void
    {
        config([
            'skyscanner.api_key' => 'secret-key',
            'skyscanner.testing_access.auth_header' => 'x-api-key',
            'skyscanner.case_id' => 'PSM-46100',
            'skyscanner.quote_ttl_minutes' => 30,
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
            'company_phone_number' => '+212500000000',
            'company_address' => 'Terminal 2',
            'company_gst_number' => 'GST-RAK-' . $vendor->id,
            'status' => 'approved',
        ]);

        $location = VendorLocation::create([
            'vendor_id' => $vendor->id,
            'name' => 'Menara Airport',
            'code' => 'vl-' . $vendor->id . '-rak',
            'address_line_1' => 'Menara Airport Terminal 2',
            'city' => 'Marrakech',
            'state' => null,
            'country' => 'Morocco',
            'country_code' => 'MA',
            'latitude' => 31.6069,
            'longitude' => -8.0363,
            'location_type' => 'airport',
            'iata_code' => 'RAK',
            'is_active' => true,
        ]);

        $referenceVehicle = $this->createVehicleAtAirport($vendor->id, $category->id, [
            'brand' => 'Toyota',
            'model' => 'Yaris',
            'location' => 'Menara Airport',
            'location_type' => 'airport',
            'city' => 'Marrakech',
            'state' => null,
            'country' => 'Morocco',
            'full_vehicle_address' => 'Menara Airport Terminal 2, Marrakech, Morocco',
            'latitude' => 31.6069,
            'longitude' => -8.0363,
            'vendor_location_id' => $location->id,
        ]);

        $response = $this
            ->withHeader('x-api-key', 'secret-key')
            ->getJson('/api/quotes/EUR/' . $location->id . '/' . $location->id . '/2026-06-15T09:00/2026-06-18T09:00/35');

        $response->assertOk();
        $response->assertJsonCount(1, 'quotes');
        $response->assertJsonPath('quotes.0.vehicle.provider_vehicle_id', (string) $referenceVehicle->id);
        $response->assertJsonPath('quotes.0.search.pickup_location_id', $location->id);
        $response->assertJsonPath('quotes.0.search.currency', 'EUR');
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
            'fuel_policy' => 'full_to_full',
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
            'limited_km_per_day' => 0,
            'limited_km_per_day_range' => null,
            'cancellation_available_per_day' => 1,
            'cancellation_available_per_day_date' => 2,
            'price_per_km_per_day' => 1.5,
            'minimum_driver_age' => 25,
            'fuel_policy' => 'full_to_full',
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
