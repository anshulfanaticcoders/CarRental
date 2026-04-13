<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Models\VendorLocation;
use Database\Seeders\VendorLocationBackfillSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorLocationBackfillSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_backfills_canonical_vendor_locations_from_existing_vehicle_rows(): void
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

        $sharedLocation = [
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'brand' => 'Toyota',
            'color' => 'white',
            'mileage' => 20,
            'transmission' => 'automatic',
            'fuel' => 'petrol',
            'seating_capacity' => 5,
            'number_of_doors' => 4,
            'luggage_capacity' => 2,
            'horsepower' => 110,
            'co2' => '120',
            'location' => 'Marrakech Airport (RAK)',
            'location_type' => 'Airport',
            'city' => 'Marrakech',
            'state' => null,
            'country' => 'Maroc',
            'full_vehicle_address' => 'Menara Airport, Terminal 2, Marrakech, Morocco',
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
        ];

        $firstVehicle = Vehicle::create(array_merge($sharedLocation, [
            'model' => 'Yaris',
        ]));

        $secondVehicle = Vehicle::create(array_merge($sharedLocation, [
            'model' => 'Corolla',
        ]));

        $thirdVehicle = Vehicle::create(array_merge($sharedLocation, [
            'model' => 'C-HR',
            'location' => 'Marrakech Downtown',
            'location_type' => 'Downtown',
            'full_vehicle_address' => 'Avenue Mohammed V, Marrakech, Morocco',
            'latitude' => 31.6295,
            'longitude' => -7.9811,
        ]));

        $this->seed(VendorLocationBackfillSeeder::class);

        $this->assertCount(2, VendorLocation::all());

        $firstVehicle->refresh();
        $secondVehicle->refresh();
        $thirdVehicle->refresh();

        $this->assertNotNull($firstVehicle->vendor_location_id);
        $this->assertSame($firstVehicle->vendor_location_id, $secondVehicle->vendor_location_id);
        $this->assertNotSame($firstVehicle->vendor_location_id, $thirdVehicle->vendor_location_id);

        $airportLocation = VendorLocation::findOrFail($firstVehicle->vendor_location_id);

        $this->assertSame('airport', $airportLocation->location_type);
        $this->assertSame('MA', $airportLocation->country_code);
        $this->assertSame('RAK', $airportLocation->iata_code);
        $this->assertSame('Menara Airport', $airportLocation->address_line_1);
    }

    public function test_it_can_derive_city_from_full_vehicle_address_when_city_is_missing(): void
    {
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

        $vehicle = Vehicle::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'brand' => 'Citroen Jumpy',
            'model' => '2022',
            'color' => 'white',
            'mileage' => 25,
            'transmission' => 'automatic',
            'fuel' => 'diesel',
            'seating_capacity' => 8,
            'number_of_doors' => 5,
            'luggage_capacity' => 4,
            'horsepower' => 140,
            'co2' => '150',
            'location' => 'Antalya Havalimani',
            'location_type' => 'Airport',
            'city' => null,
            'state' => null,
            'country' => 'Turkey',
            'full_vehicle_address' => 'Antalya Havalimani, Antalya, Turkey',
            'latitude' => 36.8987,
            'longitude' => 30.8010,
            'status' => 'available',
            'features' => json_encode([]),
            'featured' => false,
            'security_deposit' => 500,
            'payment_method' => json_encode(['credit_card']),
            'price_per_day' => 75,
            'pickup_times' => ['09:00'],
            'return_times' => ['09:00'],
        ]);

        $this->seed(VendorLocationBackfillSeeder::class);

        $vehicle->refresh();

        $this->assertNotNull($vehicle->vendor_location_id);

        $location = VendorLocation::findOrFail($vehicle->vendor_location_id);

        $this->assertSame('Antalya', $location->city);
        $this->assertSame('TR', $location->country_code);
        $this->assertSame('airport', $location->location_type);
    }
}
