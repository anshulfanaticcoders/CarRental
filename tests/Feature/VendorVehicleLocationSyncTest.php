<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBenefit;
use App\Models\VehicleCategory;
use App\Models\VehicleOperatingHour;
use App\Models\VehicleSpecification;
use App\Models\VendorLocation;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class VendorVehicleLocationSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_store_creates_a_canonical_vendor_location_and_saves_sipp_code(): void
    {
        Storage::fake('upcloud');
        Notification::fake();

        $vendor = User::factory()->create([
            'role' => 'vendor',
            'status' => 'active',
        ]);
        $this->createApprovedVendorProfile($vendor);

        $category = VehicleCategory::create([
            'name' => 'Economy',
            'slug' => 'economy',
            'description' => 'Economy vehicles',
            'status' => true,
        ]);
        $vendorLocation = $this->createVendorLocation($vendor);

        $response = $this
            ->actingAs($vendor)
            ->post(route('vehicles.store', ['locale' => 'en']), $this->storePayload($category->id, $vendorLocation->id));

        $response->assertRedirect(route('current-vendor-vehicles.index', ['locale' => 'en']));
        $response->assertSessionHasNoErrors();

        $vehicle = Vehicle::query()->with('vendorLocation')->firstOrFail();

        $this->assertSame('EDAR', $vehicle->sipp_code);
        $this->assertNotNull($vehicle->vendor_location_id);
        $this->assertSame('Marrakech Airport (RAK)', $vehicle->location);
        $this->assertSame('Airport', $vehicle->location_type);
        $this->assertSame('Marrakech', $vehicle->city);
        $this->assertSame('Morocco', $vehicle->country);
        $this->assertSame('Menara Airport, Marrakech, Morocco', $vehicle->full_vehicle_address);

        $this->assertSame('Marrakech Airport (RAK)', $vehicle->vendorLocation->name);
        $this->assertSame('airport', $vehicle->vendorLocation->location_type);
        $this->assertSame('RAK', $vehicle->vendorLocation->iata_code);
        $this->assertSame('MA', $vehicle->vendorLocation->country_code);
        $this->assertSame('Meet at desk 3 in terminal 2.', $vehicle->vendorLocation->pickup_instructions);
        $this->assertSame('+212600000000', $vehicle->vendorLocation->phone);
    }

    public function test_vendor_store_accepts_formatted_international_vehicle_contact_number(): void
    {
        Storage::fake('upcloud');
        Notification::fake();

        $vendor = User::factory()->create([
            'role' => 'vendor',
            'status' => 'active',
        ]);
        $this->createApprovedVendorProfile($vendor);

        $category = VehicleCategory::create([
            'name' => 'Economy',
            'slug' => 'economy',
            'description' => 'Economy vehicles',
            'status' => true,
        ]);
        $vendorLocation = $this->createVendorLocation($vendor);

        $payload = $this->storePayload($category->id, $vendorLocation->id);
        $payload['phone_number'] = '+212 661 584 986';

        $response = $this
            ->actingAs($vendor)
            ->post(route('vehicles.store', ['locale' => 'en']), $payload);

        $response->assertRedirect(route('current-vendor-vehicles.index', ['locale' => 'en']));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('vehicle_specifications', [
            'phone_number' => '+212 661 584 986',
        ]);
    }

    public function test_vendor_store_rejects_values_outside_vehicle_field_constraints(): void
    {
        Storage::fake('upcloud');
        Notification::fake();

        $vendor = User::factory()->create([
            'role' => 'vendor',
            'status' => 'active',
        ]);
        $this->createApprovedVendorProfile($vendor);

        $category = VehicleCategory::create([
            'name' => 'Economy',
            'slug' => 'economy',
            'description' => 'Economy vehicles',
            'status' => true,
        ]);
        $vendorLocation = $this->createVendorLocation($vendor);

        $payload = $this->storePayload($category->id, $vendorLocation->id);
        $payload['mileage'] = 121;
        $payload['horsepower'] = '110.5';
        $payload['co2'] = 'not-a-number';
        $payload['security_deposit'] = '-1.00';
        $payload['price_per_day'] = '0.00';
        $payload['gross_vehicle_mass'] = '1500.5';
        $payload['vehicle_height'] = '5.01';
        $payload['dealer_cost'] = '100000000.00';
        $payload['minimum_driver_age'] = 17;
        $payload['selected_plans'] = [[
            'plan_type' => 'Premium',
            'plan_value' => '55.555',
            'features' => [],
        ]];
        $payload['custom_addons'] = [[
            'extra_name' => 'GPS',
            'extra_type' => 'equipment',
            'description' => 'Portable navigation unit.',
            'price' => '1.234',
            'quantity' => 0,
        ]];

        $response = $this
            ->actingAs($vendor)
            ->post(route('vehicles.store', ['locale' => 'en']), $payload);

        $response->assertSessionHasErrors([
            'mileage',
            'horsepower',
            'co2',
            'security_deposit',
            'price_per_day',
            'gross_vehicle_mass',
            'vehicle_height',
            'dealer_cost',
            'minimum_driver_age',
            'selected_plans.0.plan_value',
            'custom_addons.0.price',
            'custom_addons.0.quantity',
        ]);
    }

    public function test_vendor_update_keeps_vendor_location_and_legacy_fields_in_sync(): void
    {
        $vendor = User::factory()->create([
            'role' => 'vendor',
            'status' => 'active',
        ]);
        $this->createApprovedVendorProfile($vendor);

        $category = VehicleCategory::create([
            'name' => 'Economy',
            'slug' => 'economy',
            'description' => 'Economy vehicles',
            'status' => true,
        ]);

        $vendorLocation = VendorLocation::create([
            'vendor_id' => $vendor->id,
            'name' => 'Marrakech Airport (RAK)',
            'address_line_1' => 'Menara Airport, Marrakech, Morocco',
            'city' => 'Marrakech',
            'country' => 'Morocco',
            'country_code' => 'MA',
            'latitude' => 31.6069,
            'longitude' => -8.0363,
            'location_type' => 'airport',
            'iata_code' => 'RAK',
            'phone' => '+212611111111',
            'pickup_instructions' => 'Meet at desk 3 in terminal 2.',
            'dropoff_instructions' => 'Return at parking lane B.',
            'is_active' => true,
        ]);

        $vehicle = Vehicle::create([
            'vendor_id' => $vendor->id,
            'vendor_location_id' => $vendorLocation->id,
            'category_id' => $category->id,
            'brand' => 'Toyota',
            'model' => 'Yaris',
            'color' => 'white',
            'mileage' => 20,
            'transmission' => 'automatic',
            'fuel' => 'petrol',
            'body_style' => 'sedan',
            'air_conditioning' => true,
            'sipp_code' => 'ECMN',
            'seating_capacity' => 5,
            'number_of_doors' => 4,
            'luggage_capacity' => 2,
            'horsepower' => 110,
            'co2' => '120',
            'location' => 'Old Airport',
            'location_type' => 'airport',
            'latitude' => 31.6069,
            'longitude' => -8.0363,
            'city' => 'Marrakech',
            'state' => null,
            'country' => 'Morocco',
            'full_vehicle_address' => 'Old Terminal, Marrakech, Morocco',
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
        ]);

        VehicleBenefit::create([
            'vehicle_id' => $vehicle->id,
            'minimum_driver_age' => 25,
        ]);

        VehicleSpecification::create([
            'vehicle_id' => $vehicle->id,
            'registration_number' => 'ABC123',
            'registration_country' => 'MA',
            'registration_date' => '2024-01-01',
            'phone_number' => '+212600000000',
        ]);

        foreach (range(0, 6) as $day) {
            VehicleOperatingHour::create([
                'vehicle_id' => $vehicle->id,
                'day_of_week' => $day,
                'is_open' => true,
                'open_time' => '08:00',
                'close_time' => '20:00',
            ]);
        }

        $payload = $this->updatePayload($category->id, $vendorLocation->id);

        $response = $this
            ->actingAs($vendor)
            ->putJson(route('current-vendor-vehicles.update', ['locale' => 'en', 'current_vendor_vehicle' => $vehicle->id]), $payload);

        $response->assertOk();
        $response->assertJsonPath('vehicle.sipp_code', 'EDAR');

        $vehicle->refresh();
        $vendorLocation->refresh();

        $this->assertSame($vendorLocation->id, $vehicle->vendor_location_id);
        $this->assertSame('EDAR', $vehicle->sipp_code);
        $this->assertSame('Marrakech Airport (RAK)', $vehicle->location);
        $this->assertSame('Airport', $vehicle->location_type);
        $this->assertSame('Marrakech', $vehicle->city);
        $this->assertSame('Morocco', $vehicle->country);
        $this->assertSame('Menara Airport, Marrakech, Morocco', $vehicle->full_vehicle_address);

        $this->assertSame('Marrakech Airport (RAK)', $vendorLocation->name);
        $this->assertSame('Menara Airport, Marrakech, Morocco', $vendorLocation->address_line_1);
        $this->assertSame('airport', $vendorLocation->location_type);
        $this->assertSame('RAK', $vendorLocation->iata_code);
        $this->assertSame('+212611111111', $vendorLocation->phone);
    }

    private function storePayload(int $categoryId, int $vendorLocationId): array
    {
        return [
            'vendor_location_id' => $vendorLocationId,
            'category_id' => $categoryId,
            'brand' => 'Toyota',
            'model' => 'Yaris',
            'color' => 'white',
            'mileage' => 20,
            'transmission' => 'automatic',
            'fuel' => 'petrol',
            'body_style' => 'sedan',
            'air_conditioning' => true,
            'sipp_code' => 'ecmr',
            'seating_capacity' => 5,
            'number_of_doors' => 4,
            'luggage_capacity' => 2,
            'horsepower' => 110,
            'co2' => '120',
            'location' => 'Marrakech Airport (RAK)',
            'location_type' => 'airport',
            'iata_code' => 'RAK',
            'city' => 'Marrakech',
            'state' => null,
            'country' => 'Morocco',
            'full_vehicle_address' => 'Menara Airport, Marrakech, Morocco',
            'latitude' => 31.6069,
            'longitude' => -8.0363,
            'status' => 'available',
            'features' => ['Air Conditioning'],
            'featured' => false,
            'security_deposit' => '500.00',
            'payment_method' => ['credit_card'],
            'guidelines' => 'Bring passport and license.',
            'terms_policy' => 'Return with same fuel level.',
            'rental_policy' => 'Standard rental policy.',
            'fuel_policy' => 'full_to_full',
            'pickup_instructions' => 'Meet at desk 3 in terminal 2.',
            'dropoff_instructions' => 'Return at parking lane B.',
            'location_phone' => '+212600000000',
            'price_per_day' => '55.00',
            'price_per_week' => '300.00',
            'weekly_discount' => '0.00',
            'price_per_month' => '1000.00',
            'monthly_discount' => '0.00',
            'preferred_price_type' => 'day',
            'limited_km' => false,
            'cancellation_available' => true,
            'price_per_km' => '1.50',
            'registration_number' => 'ABC123',
            'registration_country' => 'MA',
            'registration_date' => '2024-01-01',
            'gross_vehicle_mass' => 1500,
            'vehicle_height' => '1.55',
            'dealer_cost' => '10000.00',
            'phone_number' => '+212600000000',
            'limited_km_per_day' => false,
            'limited_km_per_week' => false,
            'limited_km_per_month' => false,
            'cancellation_available_per_day' => true,
            'cancellation_available_per_week' => false,
            'cancellation_available_per_month' => false,
            'cancellation_available_per_day_date' => 2,
            'cancellation_fee_per_day' => '10.00',
            'price_per_km_per_day' => '1.50',
            'price_per_km_per_week' => null,
            'price_per_km_per_month' => null,
            'minimum_driver_age' => 25,
            'pickup_times' => ['09:00'],
            'return_times' => ['09:00'],
            'operating_hours' => $this->operatingHoursPayload(),
            'primary_image_index' => 0,
            'images' => [
                UploadedFile::fake()->image('vehicle-1.jpg'),
                UploadedFile::fake()->image('vehicle-2.jpg'),
                UploadedFile::fake()->image('vehicle-3.jpg'),
                UploadedFile::fake()->image('vehicle-4.jpg'),
                UploadedFile::fake()->image('vehicle-5.jpg'),
            ],
        ];
    }

    private function updatePayload(int $categoryId, int $vendorLocationId): array
    {
        return [
            'vendor_location_id' => $vendorLocationId,
            'category_id' => $categoryId,
            'brand' => 'Toyota',
            'model' => 'Yaris',
            'color' => 'white',
            'mileage' => 25,
            'transmission' => 'automatic',
            'fuel' => 'petrol',
            'body_style' => 'sedan',
            'air_conditioning' => true,
            'sipp_code' => 'ECMR',
            'seating_capacity' => 5,
            'number_of_doors' => 4,
            'luggage_capacity' => 2,
            'horsepower' => 110,
            'co2' => '120',
            'location' => 'Marrakech Airport (RAK)',
            'location_type' => 'airport',
            'iata_code' => 'RAK',
            'city' => 'Marrakech',
            'state' => null,
            'country' => 'Morocco',
            'full_vehicle_address' => 'Menara Airport, Marrakech, Morocco',
            'latitude' => 31.6069,
            'longitude' => -8.0363,
            'status' => 'available',
            'features' => ['Air Conditioning'],
            'featured' => false,
            'security_deposit' => 250,
            'payment_method' => ['credit_card'],
            'price_per_day' => 60,
            'price_per_week' => 320,
            'weekly_discount' => null,
            'price_per_month' => 1100,
            'monthly_discount' => null,
            'preferred_price_type' => 'day',
            'registration_number' => 'ABC123',
            'registration_country' => 'MA',
            'registration_date' => '2024-01-01',
            'gross_vehicle_mass' => 1600,
            'vehicle_height' => 1.55,
            'dealer_cost' => 10000,
            'phone_number' => '+212611111111',
            'guidelines' => 'Bring passport and license.',
            'terms_policy' => 'Return with same fuel level.',
            'rental_policy' => 'Updated rental policy.',
            'fuel_policy' => 'full_to_full',
            'pickup_instructions' => 'Meet at desk 3 in terminal 2.',
            'dropoff_instructions' => 'Return at parking lane B.',
            'location_phone' => '+212611111111',
            'pickup_times' => ['09:00'],
            'return_times' => ['09:00'],
            'benefits' => [
                'limited_km_per_day' => false,
                'limited_km_per_week' => false,
                'limited_km_per_month' => false,
                'limited_km_per_day_range' => null,
                'limited_km_per_week_range' => null,
                'limited_km_per_month_range' => null,
                'cancellation_available_per_day' => true,
                'cancellation_available_per_week' => false,
                'cancellation_available_per_month' => false,
                'cancellation_available_per_day_date' => 2,
                'cancellation_fee_per_day' => 10,
                'cancellation_available_per_week_date' => null,
                'cancellation_available_per_month_date' => null,
                'price_per_km_per_day' => 1.5,
                'price_per_km_per_week' => null,
                'price_per_km_per_month' => null,
                'minimum_driver_age' => 25,
            ],
            'operating_hours' => $this->operatingHoursPayload(),
            'selected_plans' => [],
            'selected_addons' => [],
            'addon_prices' => [],
            'addon_quantities' => [],
            'custom_addons' => [],
        ];
    }

    private function operatingHoursPayload(): array
    {
        return collect(range(0, 6))
            ->map(fn (int $day) => [
                'day' => $day,
                'is_open' => true,
                'open_time' => '08:00',
                'close_time' => '20:00',
            ])
            ->all();
    }

    private function createVendorLocation(User $vendor): VendorLocation
    {
        return VendorLocation::create([
            'vendor_id' => $vendor->id,
            'name' => 'Marrakech Airport (RAK)',
            'address_line_1' => 'Menara Airport, Marrakech, Morocco',
            'city' => 'Marrakech',
            'country' => 'Morocco',
            'country_code' => 'MA',
            'latitude' => 31.6069,
            'longitude' => -8.0363,
            'location_type' => 'airport',
            'iata_code' => 'RAK',
            'phone' => '+212600000000',
            'pickup_instructions' => 'Meet at desk 3 in terminal 2.',
            'dropoff_instructions' => 'Return at parking lane B.',
            'is_active' => true,
        ]);
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
