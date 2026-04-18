<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Models\VendorLocation;
use App\Models\VendorProfile;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AutoCompleteInternalBookingsCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_completes_due_internal_confirmed_bookings(): void
    {
        Carbon::setTestNow('2026-04-07 10:00:00');

        $booking = $this->createInternalBooking([
            'booking_number' => 'BK-DUE-1',
            'return_date' => '2026-04-07 00:00:00',
            'return_time' => '10:00',
            'booking_status' => 'confirmed',
        ]);

        $futureBooking = $this->createInternalBooking([
            'booking_number' => 'BK-FUTURE-1',
            'return_date' => '2026-04-07 00:00:00',
            'return_time' => '10:15',
            'booking_status' => 'confirmed',
        ]);

        $this->artisan('app:auto-complete-internal-bookings')
            ->expectsOutputToContain('Completed 1 internal bookings.')
            ->assertExitCode(0);

        $this->assertSame('completed', $booking->fresh()->booking_status);
        $this->assertSame('confirmed', $futureBooking->fresh()->booking_status);
    }

    public function test_it_does_not_complete_external_bookings(): void
    {
        Carbon::setTestNow('2026-04-07 10:00:00');

        $booking = $this->createExternalBooking([
            'booking_number' => 'BK-EXT-1',
            'return_date' => '2026-04-07 00:00:00',
            'return_time' => '10:00',
            'booking_status' => 'confirmed',
        ]);

        $this->artisan('app:auto-complete-internal-bookings')
            ->expectsOutputToContain('Completed 0 internal bookings.')
            ->assertExitCode(0);

        $this->assertSame('confirmed', $booking->fresh()->booking_status);
    }

    private function createInternalBooking(array $overrides = []): Booking
    {
        $vehicle = $this->createVehicle();

        return Booking::create(array_merge([
            'booking_number' => 'BK-INT-1',
            'customer_id' => $this->createCustomer()->id,
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
            'payment_status' => 'partial',
            'booking_status' => 'confirmed',
        ], $overrides));
    }

    private function createExternalBooking(array $overrides = []): Booking
    {
        return Booking::create(array_merge([
            'booking_number' => 'BK-EXT-BASE',
            'customer_id' => $this->createCustomer()->id,
            'provider_source' => 'greenmotion',
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
            'payment_status' => 'partial',
            'booking_status' => 'confirmed',
        ], $overrides));
    }

    private function createCustomer(): Customer
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        return Customer::create([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => 'customer-' . $user->id . '@example.com',
            'phone' => '+971500000099',
            'driver_age' => 30,
        ]);
    }

    private function createVehicle(): Vehicle
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
            'country' => 'United Arab Emirates',
            'country_code' => 'AE',
            'latitude' => 25.251369,
            'longitude' => 55.347204,
            'location_type' => 'airport',
            'iata_code' => 'DXB',
            'is_active' => true,
        ]);

        return Vehicle::create([
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
        ]);
    }
}
