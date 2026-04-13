<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Models\VendorLocation;
use App\Models\VendorProfile;
use Database\Seeders\VendorDemoInventorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VendorDemoInventorySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_complete_vendor_inventory_with_canonical_locations(): void
    {
        $vendor = User::factory()->create([
            'role' => 'vendor',
            'status' => 'active',
        ]);

        VendorProfile::create([
            'user_id' => $vendor->id,
            'company_name' => 'Demo Fleet',
            'company_phone_number' => '1234567890',
            'company_email' => 'demo-fleet@example.com',
            'company_address' => 'Marrakech',
            'company_gst_number' => 'GST-' . Str::upper(Str::random(8)),
            'status' => 'approved',
        ]);

        VehicleCategory::create([
            'name' => 'SUV Cars',
            'slug' => 'suv-cars',
            'description' => 'SUV Cars',
            'status' => true,
        ]);
        VehicleCategory::create([
            'name' => 'Luxury Cars',
            'slug' => 'luxury-cars',
            'description' => 'Luxury Cars',
            'status' => true,
        ]);
        VehicleCategory::create([
            'name' => 'City Cars',
            'slug' => 'city-cars',
            'description' => 'City Cars',
            'status' => true,
        ]);

        $this->seed(VendorDemoInventorySeeder::class);

        $this->assertGreaterThan(0, VendorLocation::query()->count());
        $this->assertGreaterThan(0, Vehicle::query()->count());

        $vehicle = Vehicle::query()
            ->with(['vendorLocation', 'specifications', 'benefits', 'images', 'operatingHours'])
            ->firstOrFail();

        $this->assertNotNull($vehicle->vendor_location_id);
        $this->assertNotNull($vehicle->sipp_code);
        $this->assertNotNull($vehicle->vendorLocation);
        $this->assertSame($vehicle->vendorLocation->name, $vehicle->location);
        $this->assertNotNull($vehicle->specifications);
        $this->assertNotNull($vehicle->benefits);
        $this->assertGreaterThanOrEqual(2, $vehicle->images->count());
        $this->assertCount(7, $vehicle->operatingHours);
    }
}
