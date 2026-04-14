<?php

namespace Tests\Feature;

use App\Models\ApiBooking;
use App\Models\ApiConsumer;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\DamageProtection;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Models\VehicleImage;
use App\Models\VendorLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorLocationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_location_update_redirects_back_to_index(): void
    {
        $vendor = User::factory()->create([
            'role' => 'vendor',
            'status' => 'active',
        ]);

        $location = VendorLocation::create([
            'vendor_id' => $vendor->id,
            'name' => 'Dubai Airport',
            'code' => null,
            'address_line_1' => 'Terminal 1 Arrivals',
            'address_line_2' => null,
            'city' => 'Dubai',
            'state' => 'Dubai',
            'country' => 'United Arab Emirates',
            'country_code' => 'AE',
            'latitude' => 25.2532,
            'longitude' => 55.3657,
            'location_type' => 'airport',
            'iata_code' => 'DXB',
            'phone' => '+971400000000',
            'pickup_instructions' => 'Meet the agent at arrivals.',
            'dropoff_instructions' => 'Return to the airport desk.',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($vendor)
            ->put(route('vendor.locations.update', ['locale' => 'en', 'vendor_location' => $location->id]), [
                'name' => 'Dubai Airport T1',
                'code' => null,
                'address_line_1' => 'Terminal 1 Arrivals',
                'address_line_2' => null,
                'city' => 'Dubai',
                'state' => 'Dubai',
                'country' => 'United Arab Emirates',
                'country_code' => 'AE',
                'latitude' => 25.2532,
                'longitude' => 55.3657,
                'location_type' => 'airport',
                'iata_code' => 'DXB',
                'phone' => '+971400000000',
                'pickup_instructions' => 'Meet the agent at arrivals.',
                'dropoff_instructions' => 'Return to the airport desk.',
            ]);

        $response->assertRedirect(route('vendor.locations.index', ['locale' => 'en']));

        $this->assertDatabaseHas('vendor_locations', [
            'id' => $location->id,
            'name' => 'Dubai Airport T1',
        ]);
    }

    public function test_vendor_location_delete_redirects_back_to_index(): void
    {
        $vendor = User::factory()->create([
            'role' => 'vendor',
            'status' => 'active',
        ]);

        $location = VendorLocation::create([
            'vendor_id' => $vendor->id,
            'name' => 'Dubai Downtown',
            'code' => null,
            'address_line_1' => 'Sheikh Zayed Road',
            'address_line_2' => null,
            'city' => 'Dubai',
            'state' => 'Dubai',
            'country' => 'United Arab Emirates',
            'country_code' => 'AE',
            'latitude' => 25.2048,
            'longitude' => 55.2708,
            'location_type' => 'downtown',
            'iata_code' => null,
            'phone' => '+971400000000',
            'pickup_instructions' => 'Meet at the office.',
            'dropoff_instructions' => 'Return to the office.',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($vendor)
            ->delete(route('vendor.locations.destroy', ['locale' => 'en', 'vendor_location' => $location->id]));

        $response->assertRedirect(route('vendor.locations.index', ['locale' => 'en']));

        $this->assertDatabaseMissing('vendor_locations', [
            'id' => $location->id,
        ]);
    }

    public function test_vendor_location_delete_with_vehicles_removes_location_and_linked_inventory(): void
    {
        Storage::fake('upcloud');

        $vendor = User::factory()->create([
            'role' => 'vendor',
            'status' => 'active',
        ]);

        $category = VehicleCategory::create([
            'name' => 'SUV',
            'slug' => 'suv',
            'description' => 'SUV vehicles',
            'status' => true,
        ]);

        $location = VendorLocation::create([
            'vendor_id' => $vendor->id,
            'name' => 'Dubai Airport',
            'code' => null,
            'address_line_1' => 'Terminal 1 Arrivals',
            'address_line_2' => null,
            'city' => 'Dubai',
            'state' => 'Dubai',
            'country' => 'United Arab Emirates',
            'country_code' => 'AE',
            'latitude' => 25.2532,
            'longitude' => 55.3657,
            'location_type' => 'airport',
            'iata_code' => 'DXB',
            'phone' => '+971400000000',
            'pickup_instructions' => 'Meet the agent at arrivals.',
            'dropoff_instructions' => 'Return to the airport desk.',
            'is_active' => true,
        ]);

        $vehicle = Vehicle::create([
            'vendor_id' => $vendor->id,
            'vendor_location_id' => $location->id,
            'category_id' => $category->id,
            'brand' => 'Toyota',
            'model' => 'RAV4',
            'color' => 'white',
            'mileage' => 20,
            'transmission' => 'automatic',
            'fuel' => 'petrol',
            'seating_capacity' => 5,
            'number_of_doors' => 5,
            'luggage_capacity' => 3,
            'horsepower' => 180,
            'co2' => '120',
            'location' => 'Dubai Airport',
            'location_type' => 'airport',
            'latitude' => 25.2532,
            'longitude' => 55.3657,
            'city' => 'Dubai',
            'state' => 'Dubai',
            'country' => 'United Arab Emirates',
            'full_vehicle_address' => 'Terminal 1 Arrivals, Dubai Airport, Dubai',
            'status' => 'available',
            'features' => json_encode(['Air Conditioning']),
            'featured' => false,
            'security_deposit' => 300,
            'payment_method' => json_encode(['credit_card']),
            'guidelines' => 'Bring passport and license.',
            'terms_policy' => 'Return with same fuel level.',
            'fuel_policy' => 'full_to_full',
            'price_per_day' => 80,
            'price_per_week' => 500,
            'price_per_month' => 1800,
            'preferred_price_type' => 'day',
            'pickup_times' => ['09:00'],
            'return_times' => ['09:00'],
        ]);

        $imagePath = 'vehicle_images/test-rav4.jpg';
        Storage::disk('upcloud')->put($imagePath, UploadedFile::fake()->image('rav4.jpg')->getContent());

        VehicleImage::create([
            'vehicle_id' => $vehicle->id,
            'image_path' => $imagePath,
            'image_url' => 'https://example.com/rav4.jpg',
            'image_type' => 'primary',
        ]);

        $consumer = ApiConsumer::create([
            'name' => 'Test Consumer',
            'contact_name' => 'API User',
            'contact_email' => 'api@example.com',
            'contact_phone' => '+971500000000',
            'company_url' => 'https://example.com',
            'status' => 'active',
            'mode' => 'live',
            'plan' => 'basic',
            'rate_limit' => 60,
        ]);

        ApiBooking::create([
            'booking_number' => 'VRO-LOC1',
            'api_consumer_id' => $consumer->id,
            'vehicle_id' => $vehicle->id,
            'pickup_vendor_location_id' => $location->id,
            'dropoff_vendor_location_id' => $location->id,
            'vehicle_name' => 'Toyota RAV4',
            'vehicle_image' => 'https://example.com/rav4.jpg',
            'driver_first_name' => 'API',
            'driver_last_name' => 'User',
            'driver_email' => 'driver@example.com',
            'driver_phone' => '+971500000000',
            'driver_age' => 30,
            'driver_license_number' => 'LIC-123',
            'driver_license_country' => 'AE',
            'pickup_date' => '2026-05-10 00:00:00',
            'pickup_time' => '09:00',
            'return_date' => '2026-05-12 00:00:00',
            'return_time' => '09:00',
            'pickup_location' => 'Dubai Airport',
            'return_location' => 'Dubai Airport',
            'total_days' => 2,
            'daily_rate' => 80,
            'base_price' => 160,
            'extras_total' => 0,
            'total_amount' => 160,
            'currency' => 'EUR',
            'status' => 'confirmed',
        ]);

        $customerUser = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => 'customer@example.com',
            'phone' => '+971500000001',
        ]);

        $booking = Booking::create([
            'booking_number' => 'BK2026050001',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'pickup_date' => '2026-05-10 09:00:00',
            'return_date' => '2026-05-12 09:00:00',
            'pickup_location' => 'Dubai Airport',
            'return_location' => 'Dubai Airport',
            'pickup_time' => '09:00',
            'return_time' => '09:00',
            'total_days' => 2,
            'base_price' => 160,
            'extra_charges' => 0,
            'tax_amount' => 0,
            'total_amount' => 160,
            'amount_paid' => 160,
            'payment_status' => 'succeeded',
            'booking_status' => 'confirmed',
        ]);

        $beforeImagePath = 'damage_protections/before/test-before.jpg';
        $afterImagePath = 'damage_protections/after/test-after.jpg';
        Storage::disk('upcloud')->put($beforeImagePath, 'before-image');
        Storage::disk('upcloud')->put($afterImagePath, 'after-image');

        DamageProtection::create([
            'booking_id' => $booking->id,
            'before_images' => ['test-before.jpg'],
            'after_images' => ['test-after.jpg'],
        ]);

        $response = $this
            ->actingAs($vendor)
            ->delete(route('vendor.locations.destroy-with-vehicles', ['locale' => 'en', 'vendor_location' => $location->id]));

        $response->assertRedirect(route('vendor.locations.index', ['locale' => 'en']));

        $this->assertDatabaseMissing('vendor_locations', ['id' => $location->id]);
        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
        $this->assertDatabaseMissing('api_bookings', ['vehicle_id' => $vehicle->id]);
        $this->assertDatabaseMissing('damage_protections', ['booking_id' => $booking->id]);
        Storage::disk('upcloud')->assertMissing($imagePath);
        Storage::disk('upcloud')->assertMissing($beforeImagePath);
        Storage::disk('upcloud')->assertMissing($afterImagePath);
    }

    public function test_vendor_location_standard_delete_still_blocks_when_vehicles_are_linked(): void
    {
        $vendor = User::factory()->create([
            'role' => 'vendor',
            'status' => 'active',
        ]);

        $category = VehicleCategory::create([
            'name' => 'Sedan',
            'slug' => 'sedan',
            'description' => 'Sedan vehicles',
            'status' => true,
        ]);

        $location = VendorLocation::create([
            'vendor_id' => $vendor->id,
            'name' => 'Dubai Airport',
            'code' => null,
            'address_line_1' => 'Terminal 1 Arrivals',
            'address_line_2' => null,
            'city' => 'Dubai',
            'state' => 'Dubai',
            'country' => 'United Arab Emirates',
            'country_code' => 'AE',
            'latitude' => 25.2532,
            'longitude' => 55.3657,
            'location_type' => 'airport',
            'iata_code' => 'DXB',
            'phone' => '+971400000000',
            'pickup_instructions' => 'Meet the agent at arrivals.',
            'dropoff_instructions' => 'Return to the airport desk.',
            'is_active' => true,
        ]);

        Vehicle::create([
            'vendor_id' => $vendor->id,
            'vendor_location_id' => $location->id,
            'category_id' => $category->id,
            'brand' => 'Toyota',
            'model' => 'Camry',
            'color' => 'white',
            'mileage' => 20,
            'transmission' => 'automatic',
            'fuel' => 'petrol',
            'seating_capacity' => 5,
            'number_of_doors' => 4,
            'luggage_capacity' => 2,
            'horsepower' => 150,
            'co2' => '120',
            'location' => 'Dubai Airport',
            'location_type' => 'airport',
            'latitude' => 25.2532,
            'longitude' => 55.3657,
            'city' => 'Dubai',
            'state' => 'Dubai',
            'country' => 'United Arab Emirates',
            'full_vehicle_address' => 'Terminal 1 Arrivals, Dubai Airport, Dubai',
            'status' => 'available',
            'features' => json_encode(['Air Conditioning']),
            'featured' => false,
            'security_deposit' => 300,
            'payment_method' => json_encode(['credit_card']),
            'guidelines' => 'Bring passport and license.',
            'terms_policy' => 'Return with same fuel level.',
            'fuel_policy' => 'full_to_full',
            'price_per_day' => 60,
            'price_per_week' => 400,
            'price_per_month' => 1400,
            'preferred_price_type' => 'day',
            'pickup_times' => ['09:00'],
            'return_times' => ['09:00'],
        ]);

        $response = $this
            ->actingAs($vendor)
            ->delete(route('vendor.locations.destroy', ['locale' => 'en', 'vendor_location' => $location->id]));

        $response->assertSessionHasErrors('delete');
        $this->assertDatabaseHas('vendor_locations', ['id' => $location->id]);
    }
}
