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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminVehicleDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_single_delete_removes_vehicle_and_related_storage_artifacts(): void
    {
        Storage::fake('upcloud');

        $admin = User::factory()->create(['role' => 'admin']);
        $vehicle = $this->createVehicle();
        $this->attachVehicleArtifacts($vehicle);

        $this->actingAs($admin)
            ->delete(route('admin.vehicles.destroy', ['vendor_vehicle' => $vehicle->id]));

        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
        $this->assertDatabaseMissing('api_bookings', ['vehicle_id' => $vehicle->id]);
        Storage::disk('upcloud')->assertMissing('vehicle_images/test-primary.jpg');
        Storage::disk('upcloud')->assertMissing('damage_protections/before/before-proof.jpg');
        Storage::disk('upcloud')->assertMissing('damage_protections/after/after-proof.jpg');
    }

    public function test_admin_bulk_delete_removes_all_selected_vehicles_and_related_storage_artifacts(): void
    {
        Storage::fake('upcloud');

        $admin = User::factory()->create(['role' => 'admin']);
        $firstVehicle = $this->createVehicle('Mazda', '3');
        $secondVehicle = $this->createVehicle('Toyota', 'Yaris');

        $this->attachVehicleArtifacts($firstVehicle, 'vehicle_images/first.jpg', 'damage_protections/before/first-before.jpg', 'damage_protections/after/first-after.jpg');
        $this->attachVehicleArtifacts($secondVehicle, 'vehicle_images/second.jpg', 'damage_protections/before/second-before.jpg', 'damage_protections/after/second-after.jpg');

        $this->actingAs($admin)
            ->delete(route('admin.vehicles.bulk-delete'), [
                'ids' => [$firstVehicle->id, $secondVehicle->id],
            ]);

        $this->assertDatabaseMissing('vehicles', ['id' => $firstVehicle->id]);
        $this->assertDatabaseMissing('vehicles', ['id' => $secondVehicle->id]);
        $this->assertDatabaseMissing('api_bookings', ['vehicle_id' => $firstVehicle->id]);
        $this->assertDatabaseMissing('api_bookings', ['vehicle_id' => $secondVehicle->id]);
        Storage::disk('upcloud')->assertMissing('vehicle_images/first.jpg');
        Storage::disk('upcloud')->assertMissing('vehicle_images/second.jpg');
        Storage::disk('upcloud')->assertMissing('damage_protections/before/first-before.jpg');
        Storage::disk('upcloud')->assertMissing('damage_protections/after/first-after.jpg');
        Storage::disk('upcloud')->assertMissing('damage_protections/before/second-before.jpg');
        Storage::disk('upcloud')->assertMissing('damage_protections/after/second-after.jpg');
    }

    private function createVehicle(string $brand = 'Kia', string $model = 'Pegas'): Vehicle
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $category = VehicleCategory::query()->create([
            'name' => "{$brand} Category",
            'description' => 'Test category',
            'slug' => strtolower($brand) . '-category-' . fake()->unique()->numerify('###'),
        ]);

        return Vehicle::query()->create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'seating_capacity' => 5,
            'brand' => $brand,
            'model' => $model,
            'number_of_doors' => 4,
            'transmission' => 'automatic',
            'luggage_capacity' => 2,
            'horsepower' => 110,
            'fuel' => 'petrol',
            'co2' => '100',
            'color' => 'White',
            'mileage' => 1000,
            'location' => 'Dubai Airport',
            'city' => 'Dubai',
            'state' => 'Dubai',
            'country' => 'United Arab Emirates',
            'latitude' => 25.250291,
            'longitude' => 55.345171,
            'status' => 'available',
            'features' => json_encode([]),
            'security_deposit' => 1000,
            'payment_method' => 'card',
            'price_per_day' => 100,
        ]);
    }

    private function attachVehicleArtifacts(
        Vehicle $vehicle,
        string $imagePath = 'vehicle_images/test-primary.jpg',
        string $beforeImagePath = 'damage_protections/before/before-proof.jpg',
        string $afterImagePath = 'damage_protections/after/after-proof.jpg'
    ): void {
        $customerUser = User::factory()->create(['role' => 'customer']);
        $customer = Customer::query()->create([
            'user_id' => $customerUser->id,
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+123456789',
            'driver_age' => 35,
        ]);

        Storage::disk('upcloud')->put($imagePath, UploadedFile::fake()->image('vehicle.jpg')->getContent());
        Storage::disk('upcloud')->put($beforeImagePath, UploadedFile::fake()->image('before.jpg')->getContent());
        Storage::disk('upcloud')->put($afterImagePath, UploadedFile::fake()->image('after.jpg')->getContent());

        VehicleImage::query()->create([
            'vehicle_id' => $vehicle->id,
            'image_path' => $imagePath,
            'image_url' => null,
            'image_type' => 'primary',
        ]);

        $booking = Booking::query()->create([
            'booking_number' => 'BK' . fake()->unique()->numerify('######'),
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'vehicle_name' => "{$vehicle->brand} {$vehicle->model}",
            'pickup_date' => now(),
            'return_date' => now()->addDays(2),
            'pickup_time' => '09:00',
            'return_time' => '09:00',
            'total_days' => 2,
            'base_price' => 100,
            'extra_charges' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 200,
            'payment_status' => 'succeeded',
            'booking_status' => 'confirmed',
        ]);

        DamageProtection::query()->create([
            'booking_id' => $booking->id,
            'before_images' => [basename($beforeImagePath)],
            'after_images' => [basename($afterImagePath)],
        ]);

        $consumer = ApiConsumer::query()->create([
            'name' => 'Test Consumer',
            'contact_name' => 'Consumer Contact',
            'contact_email' => fake()->unique()->safeEmail(),
            'contact_phone' => '+123456789',
            'status' => 'active',
            'mode' => 'sandbox',
        ]);

        ApiBooking::query()->create([
            'booking_number' => ApiBooking::generateBookingNumber(),
            'api_consumer_id' => $consumer->id,
            'vehicle_id' => $vehicle->id,
            'vehicle_name' => "{$vehicle->brand} {$vehicle->model}",
            'driver_first_name' => 'Test',
            'driver_last_name' => 'Driver',
            'driver_email' => fake()->unique()->safeEmail(),
            'driver_phone' => '+123456789',
            'driver_age' => 35,
            'driver_license_number' => 'DL123456',
            'driver_license_country' => 'AE',
            'pickup_date' => now(),
            'pickup_time' => '09:00',
            'return_date' => now()->addDays(2),
            'return_time' => '09:00',
            'pickup_location' => 'Dubai Airport',
            'return_location' => 'Dubai Airport',
            'total_days' => 2,
            'daily_rate' => 100,
            'base_price' => 200,
            'extras_total' => 0,
            'total_amount' => 200,
            'currency' => 'EUR',
            'status' => 'confirmed',
        ]);
    }
}
