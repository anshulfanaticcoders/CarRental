<?php

namespace App\Services\Vehicles\BulkImport;

use App\Models\BookingAddon;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBenefit;
use App\Models\VehicleImage;
use App\Models\VehicleOperatingHour;
use App\Models\VehicleSpecification;
use App\Models\VendorVehicleAddon;
use App\Models\VendorVehiclePlan;
use App\Services\Vehicles\SippCodeSuggestionService;
use App\Services\Vehicles\VendorLocationSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class BulkVehicleImportExecutionService
{
    public function __construct(
        private readonly VendorLocationSyncService $vendorLocationSyncService,
        private readonly SippCodeSuggestionService $sippCodeSuggestionService,
    ) {
    }

    public function import(array $analysis, User $user): array
    {
        if (($analysis['missing_required_columns'] ?? []) !== []) {
            throw new RuntimeException('The CSV is still missing required columns.');
        }

        if (($analysis['invalid_rows'] ?? 0) > 0) {
            throw new RuntimeException('The CSV still has invalid rows and cannot be imported yet.');
        }

        $rows = $analysis['rows'] ?? [];
        if ($rows === []) {
            throw new RuntimeException('There are no vehicle rows available to import.');
        }

        $createdVehicleIds = [];

        DB::transaction(function () use ($rows, $user, &$createdVehicleIds): void {
            foreach ($rows as $row) {
                $createdVehicle = $this->createVehicleFromRow($row, $user);
                $createdVehicleIds[] = $createdVehicle->id;
            }
        });

        return [
            'created_count' => count($createdVehicleIds),
            'vehicle_ids' => $createdVehicleIds,
        ];
    }

    private function createVehicleFromRow(array $row, User $user): Vehicle
    {
        $normalized = $row['canonical'] ?? [];
        $resolved = $row['resolved'] ?? [];

        $vendorLocationId = $resolved['vendor_location_id'] ?? null;
        $vendorLocation = $this->vendorLocationSyncService->resolveSelectedLocation($user->id, $vendorLocationId);
        if (!$vendorLocation) {
            throw new RuntimeException('Vendor location could not be resolved during import.');
        }

        $locationAttributes = $this->vendorLocationSyncService->vehicleLocationAttributes($vendorLocation);
        $resolvedSippCode = $this->sippCodeSuggestionService->suggest([
            'category_name' => $resolved['category_name'] ?? null,
            'body_style' => $normalized['body_style'] ?? null,
            'seating_capacity' => $normalized['seating_capacity'] ?? null,
            'number_of_doors' => $normalized['number_of_doors'] ?? null,
            'horsepower' => $normalized['horsepower'] ?? null,
            'transmission' => $normalized['transmission'] ?? null,
            'fuel' => $normalized['fuel'] ?? null,
            'air_conditioning' => $resolved['air_conditioning'] ?? null,
            'brand' => $normalized['brand'] ?? null,
            'model' => $normalized['model'] ?? null,
        ]);

        $vehicle = Vehicle::create([
            'vendor_id' => $user->id,
            'vendor_location_id' => $vendorLocation->id,
            'category_id' => $resolved['category_id'],
            'brand' => $normalized['brand'],
            'model' => $normalized['model'],
            'color' => $normalized['color'],
            'mileage' => (int) $normalized['mileage'],
            'transmission' => $normalized['transmission'],
            'fuel' => $normalized['fuel'],
            'body_style' => $normalized['body_style'],
            'air_conditioning' => (bool) $resolved['air_conditioning'],
            'sipp_code' => $resolvedSippCode,
            'seating_capacity' => (int) $normalized['seating_capacity'],
            'number_of_doors' => (int) $normalized['number_of_doors'],
            'luggage_capacity' => $normalized['luggage_capacity'] === '' ? 0 : (int) $normalized['luggage_capacity'],
            'horsepower' => (int) $normalized['horsepower'],
            'co2' => $normalized['co2'],
            'location' => $locationAttributes['location'],
            'location_type' => $locationAttributes['location_type'],
            'latitude' => $locationAttributes['latitude'],
            'longitude' => $locationAttributes['longitude'],
            'city' => $locationAttributes['city'],
            'state' => $locationAttributes['state'],
            'country' => $locationAttributes['country'],
            'full_vehicle_address' => $locationAttributes['full_vehicle_address'],
            'status' => $normalized['status'],
            'features' => $resolved['features'] !== [] ? json_encode($resolved['features']) : null,
            'featured' => false,
            'security_deposit' => $normalized['security_deposit'],
            'payment_method' => json_encode($resolved['payment_methods']),
            'guidelines' => $normalized['guidelines'] ?: null,
            'terms_policy' => $normalized['terms_policy'] ?: null,
            'rental_policy' => $normalized['rental_policy'] ?: null,
            'fuel_policy' => $normalized['fuel_policy'] ?: 'full_to_full',
            'pickup_instructions' => $locationAttributes['pickup_instructions'],
            'dropoff_instructions' => $locationAttributes['dropoff_instructions'],
            'location_phone' => $locationAttributes['location_phone'],
            'price_per_day' => $normalized['price_per_day'],
            'price_per_week' => $normalized['price_per_week'] ?: null,
            'weekly_discount' => $normalized['weekly_discount'] ?: null,
            'price_per_month' => $normalized['price_per_month'] ?: null,
            'monthly_discount' => $normalized['monthly_discount'] ?: null,
            'preferred_price_type' => 'day',
            'limited_km' => false,
            'cancellation_available' => false,
            'price_per_km' => $normalized['price_per_km'] ?: null,
            'pickup_times' => $resolved['pickup_times'],
            'return_times' => $resolved['return_times'],
        ]);

        VehicleBenefit::create([
            'vehicle_id' => $vehicle->id,
            'limited_km_per_day' => false,
            'limited_km_per_week' => false,
            'limited_km_per_month' => false,
            'limited_km_per_day_range' => null,
            'limited_km_per_week_range' => null,
            'limited_km_per_month_range' => null,
            'cancellation_available_per_day' => false,
            'cancellation_available_per_week' => false,
            'cancellation_available_per_month' => false,
            'cancellation_available_per_day_date' => null,
            'cancellation_fee_per_day' => null,
            'cancellation_available_per_week_date' => null,
            'cancellation_available_per_month_date' => null,
            'price_per_km_per_day' => null,
            'price_per_km_per_week' => null,
            'price_per_km_per_month' => null,
            'minimum_driver_age' => (int) $normalized['minimum_driver_age'],
        ]);

        VehicleSpecification::create([
            'vehicle_id' => $vehicle->id,
            'registration_number' => $normalized['registration_number'],
            'registration_country' => $normalized['registration_country'],
            'registration_date' => $normalized['registration_date'],
            'gross_vehicle_mass' => $normalized['gross_vehicle_mass'] !== '' ? $normalized['gross_vehicle_mass'] : null,
            'vehicle_height' => $normalized['vehicle_height'] !== '' ? $normalized['vehicle_height'] : null,
            'dealer_cost' => $normalized['dealer_cost'] !== '' ? $normalized['dealer_cost'] : null,
            'phone_number' => $normalized['phone_number'],
        ]);

        $this->createDefaultOperatingHours($vehicle);
        $this->attachPlans($vehicle, $user, $resolved['selected_plans'] ?? [], (float) $normalized['price_per_day']);
        $this->attachCustomAddons($vehicle, $user, $resolved['custom_addons'] ?? []);
        $this->attachImages($vehicle, $resolved);

        return $vehicle;
    }

    private function createDefaultOperatingHours(Vehicle $vehicle): void
    {
        $rows = [];
        $timestamp = now();

        for ($day = 0; $day <= 6; $day++) {
            $rows[] = [
                'vehicle_id' => $vehicle->id,
                'day_of_week' => $day,
                'is_open' => true,
                'open_time' => '00:00',
                'close_time' => '23:59',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        VehicleOperatingHour::query()->insert($rows);
    }

    private function attachImages(Vehicle $vehicle, array $resolved): void
    {
        $primary = $resolved['primary_image'] ?? null;
        $gallery = $resolved['gallery_images'] ?? [];

        if ($primary) {
            $this->createImage($vehicle, $primary, 'primary');
        }

        foreach ($gallery as $galleryImage) {
            $this->createImage($vehicle, $galleryImage, 'gallery');
        }
    }

    private function createImage(Vehicle $vehicle, array $image, string $imageType): void
    {
        if (($image['type'] ?? null) === 'bulk_image') {
            VehicleImage::create([
                'vehicle_id' => $vehicle->id,
                'image_path' => $image['path'],
                'image_url' => Storage::disk('upcloud')->url($image['path']),
                'thumbnail_path' => $image['thumbnail_path'] ?? null,
                'thumbnail_url' => !empty($image['thumbnail_path']) ? Storage::disk('upcloud')->url($image['thumbnail_path']) : Storage::disk('upcloud')->url($image['path']),
                'image_type' => $imageType,
            ]);

            return;
        }

        if (($image['type'] ?? null) === 'url') {
            VehicleImage::create([
                'vehicle_id' => $vehicle->id,
                'image_path' => $image['value'],
                'image_url' => $image['value'],
                'thumbnail_path' => null,
                'thumbnail_url' => $image['value'],
                'image_type' => $imageType,
            ]);
        }
    }

    private function attachPlans(Vehicle $vehicle, User $user, array $selectedPlans, float $dailyPrice): void
    {
        if ($selectedPlans === []) {
            return;
        }

        $planId = 1;

        foreach ($selectedPlans as $selectedPlan) {
            $planType = $selectedPlan['plan_type'] ?? 'Basic';
            $planValue = $planType === 'Basic'
                ? $dailyPrice
                : ($selectedPlan['plan_value'] ?? 0);

            $features = isset($selectedPlan['features']) && is_array($selectedPlan['features'])
                ? array_values(array_filter($selectedPlan['features'], fn ($feature) => trim((string) $feature) !== ''))
                : [];

            VendorVehiclePlan::create([
                'vendor_id' => $user->id,
                'vehicle_id' => $vehicle->id,
                'plan_id' => $planId,
                'plan_type' => $planType,
                'price' => $planValue,
                'features' => $features !== [] ? json_encode(array_slice($features, 0, 5)) : null,
                'plan_description' => $selectedPlan['plan_description'] ?? null,
            ]);

            $planId++;
        }
    }

    private function attachCustomAddons(Vehicle $vehicle, User $user, array $customAddons): void
    {
        if ($customAddons === []) {
            return;
        }

        foreach ($customAddons as $customAddon) {
            $extraName = trim((string) ($customAddon['extra_name'] ?? ''));
            if ($extraName === '') {
                continue;
            }

            $extraType = trim((string) ($customAddon['extra_type'] ?? '')) ?: 'custom';
            $description = trim((string) ($customAddon['description'] ?? ''));
            $price = $customAddon['price'] ?? 0;
            $quantity = $customAddon['quantity'] ?? 1;

            $addon = BookingAddon::updateOrCreate(
                [
                    'vendor_id' => $user->id,
                    'extra_name' => $extraName,
                    'extra_type' => $extraType,
                ],
                [
                    'description' => $description,
                    'price' => $price,
                    'quantity' => $quantity,
                ]
            );

            VendorVehicleAddon::create([
                'vendor_id' => $user->id,
                'vehicle_id' => $vehicle->id,
                'addon_id' => $addon->id,
                'extra_type' => $addon->extra_type,
                'extra_name' => $addon->extra_name,
                'price' => $price,
                'quantity' => $quantity,
                'description' => $addon->description,
            ]);
        }
    }
}
