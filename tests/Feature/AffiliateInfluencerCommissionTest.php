<?php

namespace Tests\Feature;

use App\Models\Affiliate\AffiliateBusiness;
use App\Models\Affiliate\AffiliateCommission;
use App\Models\Affiliate\AffiliateCustomerScan;
use App\Models\Affiliate\AffiliateQrCode;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Services\Affiliate\ScoutCommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AffiliateInfluencerCommissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_influencer_locationless_qr_scan_can_create_booking_commission(): void
    {
        $customerUser = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'first_name' => 'Smoke',
            'last_name' => 'Customer',
            'email' => $customerUser->email,
            'phone' => '+971509900001',
            'driver_age' => 30,
        ]);

        $vehicle = $this->createVehicle();
        $booking = Booking::create([
            'booking_number' => 'BK-INF-'.Str::upper(Str::random(6)),
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'pickup_date' => '2026-07-01 09:00:00',
            'return_date' => '2026-07-03 09:00:00',
            'pickup_time' => '09:00',
            'return_time' => '09:00',
            'pickup_location' => 'Dubai Airport',
            'return_location' => 'Dubai Airport',
            'total_days' => 2,
            'base_price' => 200,
            'tax_amount' => 0,
            'total_amount' => 200,
            'booking_status' => 'confirmed',
        ]);

        $business = $this->createInfluencerBusiness();
        $qrCode = AffiliateQrCode::create([
            'uuid' => (string) Str::uuid(),
            'business_id' => $business->id,
            'location_id' => null,
            'qr_code_value' => 'INFLUENCER-COMMISSION',
            'qr_hash' => hash('sha256', 'INFLUENCER-COMMISSION'),
            'short_code' => 'INFLUCOM',
            'qr_url' => url('/en/affiliate/qr/INFLUCOM'),
            'qr_image_path' => 'affiliate/qr/INFLUCOM.png',
            'discount_type' => 'percentage',
            'discount_value' => 0,
            'status' => 'active',
            'total_scans' => 1,
            'unique_scans' => 1,
        ]);

        $scan = AffiliateCustomerScan::create([
            'uuid' => (string) Str::uuid(),
            'qr_code_id' => $qrCode->id,
            'customer_id' => $customerUser->id,
            'session_id' => 'influencer-session',
            'scan_token' => 'influencer-scan-token',
            'tracking_url' => $qrCode->qr_url,
            'ip_address' => '127.0.0.1',
            'device_type' => 'mobile',
            'scan_date' => now()->toDateString(),
            'scan_hour' => (int) now()->format('G'),
            'scan_result' => 'success',
            'booking_completed' => false,
        ]);

        app(ScoutCommissionService::class)->createCommission(
            $booking->id,
            $customerUser->id,
            200,
            'platform',
            ['customer_scan_id' => $scan->id],
            'EUR'
        );

        $commission = AffiliateCommission::where('booking_id', $booking->id)->firstOrFail();

        $this->assertSame($business->id, $commission->business_id);
        $this->assertNull($commission->location_id);
        $this->assertSame($scan->id, $commission->qr_scan_id);
        $this->assertEqualsWithDelta(6.00, (float) $commission->commission_amount, 0.01);

        $scan->refresh();
        $this->assertTrue($scan->booking_completed);
        $this->assertSame($booking->id, $scan->booking_id);
    }

    private function createInfluencerBusiness(): AffiliateBusiness
    {
        $owner = User::factory()->create([
            'role' => 'affiliate',
            'status' => 'active',
        ]);

        return AffiliateBusiness::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $owner->id,
            'name' => 'Creator Road Trips',
            'business_type' => 'influencer',
            'contact_email' => $owner->email,
            'contact_phone' => $owner->phone,
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
            'currency' => 'EUR',
            'verification_status' => 'verified',
            'status' => 'active',
        ]);
    }

    private function createVehicle(): Vehicle
    {
        $category = VehicleCategory::create([
            'name' => 'SUV',
            'slug' => 'suv',
            'description' => 'SUV vehicles',
            'status' => true,
        ]);

        $vendor = User::factory()->create([
            'role' => 'vendor',
            'status' => 'active',
        ]);

        return Vehicle::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'brand' => 'GMC',
            'model' => 'Yukon',
            'seating_capacity' => 7,
            'number_of_doors' => 4,
            'transmission' => 'automatic',
            'luggage_capacity' => 4,
            'horsepower' => 300,
            'fuel' => 'petrol',
            'co2' => '220',
            'color' => 'black',
            'mileage' => 10000,
            'location' => 'Dubai Airport',
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
            'latitude' => 25.251369,
            'longitude' => 55.347204,
            'status' => 'available',
            'features' => json_encode(['Air Conditioning']),
            'featured' => false,
            'security_deposit' => 500,
            'payment_method' => 'credit_card',
            'price_per_day' => 100,
            'pickup_times' => ['09:00'],
            'return_times' => ['09:00'],
        ]);
    }
}
