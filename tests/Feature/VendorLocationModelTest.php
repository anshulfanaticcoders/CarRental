<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Models\VendorLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorLocationModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_vehicle_can_belong_to_a_vendor_location(): void
    {
        $vendor = User::factory()->create([
            'role' => 'vendor',
            'status' => 'active',
        ]);

        $category = VehicleCategory::create([
            'name' => 'Economy',
            'slug' => 'economy',
            'description' => 'Economy vehicles',
            'status' => true,
        ]);

        $location = VendorLocation::create([
            'vendor_id' => $vendor->id,
            'name' => 'Marrakech Airport',
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

        $vehicle = Vehicle::create([
            'vendor_id' => $vendor->id,
            'vendor_location_id' => $location->id,
            'category_id' => $category->id,
            'brand' => 'Toyota',
            'model' => 'Yaris',
            'color' => 'white',
            'mileage' => 20,
            'transmission' => 'automatic',
            'fuel' => 'petrol',
            'seating_capacity' => 5,
            'number_of_doors' => 4,
            'luggage_capacity' => 2,
            'horsepower' => 110,
            'co2' => '120',
            'location' => 'Marrakech Airport',
            'location_type' => 'airport',
            'city' => 'Marrakech',
            'state' => null,
            'country' => 'Morocco',
            'full_vehicle_address' => 'Menara Airport, Marrakech, Morocco',
            'latitude' => 31.6069,
            'longitude' => -8.0363,
            'status' => 'available',
            'features' => json_encode([]),
            'featured' => false,
            'security_deposit' => 500,
            'payment_method' => json_encode(['credit_card']),
            'price_per_day' => 55,
            'pickup_times' => ['09:00'],
            'return_times' => ['09:00'],
        ]);

        $this->assertSame($location->id, $vehicle->vendorLocation->id);
        $this->assertSame($vendor->id, $location->vendor->id);
        $this->assertCount(1, $location->vehicles);
    }
}
