<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Vehicle;
use App\Models\VehicleBenefit;
use App\Models\VehicleCategory;
use App\Models\VehicleImage;
use App\Models\VehicleOperatingHour;
use App\Models\VendorLocation;
use App\Models\VendorProfile;
use App\Services\Vehicles\InternalVehicleAvailabilityService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InternalVehicleCompletedAvailabilityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_ignores_completed_bookings_when_checking_availability(): void
    {
        $service = app(InternalVehicleAvailabilityService::class);
        [$vehicle, , , $customer] = $this->createVehicleContext();

        Booking::create([
            'booking_number' => 'BK-COMP-1',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'provider_source' => 'internal',
            'pickup_date' => '2026-04-01 00:00:00',
            'return_date' => '2026-04-07 00:00:00',
            'pickup_time' => '09:00',
            'return_time' => '10:00',
            'pickup_location' => 'Airport',
            'return_location' => 'Airport',
            'total_days' => 6,
            'base_price' => 200,
            'tax_amount' => 0,
            'total_amount' => 200,
            'booking_status' => 'completed',
        ]);

        $available = $service->apply(Vehicle::query(), [
            'pickup_date' => '2026-04-06',
            'pickup_time' => '09:00',
            'dropoff_date' => '2026-04-08',
            'dropoff_time' => '09:00',
        ])->pluck('id');

        $this->assertTrue($available->contains($vehicle->id));
    }

    private function createVehicleContext(array $vehicleOverrides = []): array
    {
        $category = VehicleCategory::firstOrCreate([
            'slug' => 'economy',
        ], [
            'name' => 'Economy',
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

        $customerUser = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => 'customer-' . $customerUser->id . '@example.com',
            'phone' => '+971500000099',
            'driver_age' => 30,
        ]);

        VendorProfile::create([
            'user_id' => $vendor->id,
            'company_name' => 'Airport Fleet Co',
            'company_email' => 'fleet-' . $vendor->id . '@example.com',
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

        $vehicle = Vehicle::create(array_merge([
            'vendor_id' => $vendor->id,
            'vendor_location_id' => $location->id,
            'category_id' => $category->id,
            'brand' => 'Toyota',
            'model' => 'Yaris',
            'transmission' => 'automatic',
            'fuel' => 'petrol',
            'body_style' => 'hatchback',
            'air_conditioning' => true,
            'sipp_code' => 'ECAR',
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
        ], $vehicleOverrides));

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

        foreach (range(0, 6) as $dayOfWeek) {
            VehicleOperatingHour::create([
                'vehicle_id' => $vehicle->id,
                'day_of_week' => $dayOfWeek,
                'is_open' => true,
                'open_time' => '08:00',
                'close_time' => '20:00',
            ]);
        }

        return [$vehicle, $location, $vendor, $customer];
    }
}
