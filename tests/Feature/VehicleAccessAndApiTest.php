<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VehicleAccessAndApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_create_vehicle_via_store_route(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this
            ->actingAs($customer)
            ->post(route('vehicles.store', ['locale' => 'en']), []);

        $response->assertRedirect('/');
        $response->assertSessionHasNoErrors();
    }

    public function test_approved_vendor_can_fetch_vehicle_categories(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $this->createApprovedVendorProfile($vendor);

        VehicleCategory::create([
            'name' => 'SUV',
            'slug' => 'suv',
            'description' => 'Sports Utility Vehicle',
            'status' => true,
        ]);

        $response = $this
            ->actingAs($vendor)
            ->get(route('vehicle.categories', ['locale' => 'en']));

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'SUV']);
    }

    public function test_public_vehicles_api_returns_vendor_vehicles(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $this->createApprovedVendorProfile($vendor);

        $category = VehicleCategory::create([
            'name' => 'Sedan',
            'slug' => 'sedan',
            'description' => 'Sedan Cars',
            'status' => true,
        ]);

        Vehicle::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'seating_capacity' => 5,
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'number_of_doors' => 4,
            'transmission' => 'automatic',
            'luggage_capacity' => 2,
            'horsepower' => 132,
            'fuel' => 'petrol',
            'co2' => '120',
            'color' => 'blue',
            'mileage' => 45,
            'location' => 'Rabat Airport',
            'location_type' => 'airport',
            'latitude' => 34.0510,
            'longitude' => -6.7515,
            'status' => 'available',
            'features' => json_encode(['A/C']),
            'featured' => false,
            'security_deposit' => 200.00,
            'payment_method' => json_encode(['credit_card']),
            'guidelines' => 'Bring your license.',
            'terms_policy' => 'No smoking. Return with same fuel level.',
            'price_per_day' => 60.00,
            'preferred_price_type' => 'day',
            'pickup_times' => ['09:00'],
            'return_times' => ['09:00'],
        ]);

        $response = $this->getJson('/api/vehicles');

        $response->assertOk();
        $response->assertJsonFragment(['model' => 'Corolla']);
        $response->assertJsonFragment(['vendor_id' => $vendor->id]);
        $response->assertJsonFragment(['terms_policy' => 'No smoking. Return with same fuel level.']);
    }

    private function createApprovedVendorProfile(User $user): VendorProfile
    {
        return VendorProfile::create([
            'user_id' => $user->id,
            'company_name' => 'Vendor ' . $user->id,
            'company_phone_number' => '1234567890',
            'company_email' => 'vendor-' . $user->id . '@example.com',
            'company_address' => '123 Market St',
            'company_gst_number' => 'GST-' . Str::upper(Str::random(8)),
            'status' => 'approved',
        ]);
    }
}
