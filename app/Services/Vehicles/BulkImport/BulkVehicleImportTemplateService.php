<?php

namespace App\Services\Vehicles\BulkImport;

use App\Models\User;
use App\Models\VehicleCategory;
use App\Models\VendorLocation;

class BulkVehicleImportTemplateService
{
    public function columns(): array
    {
        return config('vehicle_bulk_import.columns', []);
    }

    public function requiredColumns(): array
    {
        return array_values(array_filter(
            $this->columns(),
            static fn (array $column) => (bool) ($column['required'] ?? false)
        ));
    }

    public function optionalColumns(): array
    {
        return array_values(array_filter(
            $this->columns(),
            static fn (array $column) => !($column['required'] ?? false)
        ));
    }

    public function enumMap(): array
    {
        return config('vehicle_bulk_import.enums', []);
    }

    public function aliases(): array
    {
        return config('vehicle_bulk_import.aliases', []);
    }

    public function templateHeaders(): array
    {
        return array_map(static fn (array $column) => $column['key'], $this->columns());
    }

    public function columnDefinition(string $key): ?array
    {
        foreach ($this->columns() as $column) {
            if (($column['key'] ?? null) === $key) {
                return $column;
            }
        }

        return null;
    }

    public function sampleRow(): array
    {
        return array_map(static fn (array $column) => $column['example'] ?? '', $this->columns());
    }

    public function sampleRowsForUser(User $user): array
    {
        $categories = VehicleCategory::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->values();

        $vendorLocations = VendorLocation::query()
            ->where('vendor_id', $user->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'country', 'country_code'])
            ->values();

        $defaultRows = [
            [
                'brand' => 'Toyota',
                'model' => 'Corolla',
                'body_style' => 'sedan',
                'transmission' => 'automatic',
                'fuel' => 'petrol',
                'air_conditioning' => '1',
                'seating_capacity' => '5',
                'number_of_doors' => '4',
                'color' => 'Silver',
                'status' => 'available',
                'registration_number' => 'TEST-001',
                'registration_date' => '2024-01-15',
                'phone_number' => '+31612345678',
                'price_per_day' => '89.00',
                'price_per_week' => '540.00',
                'weekly_discount' => '35.00',
                'price_per_month' => '1890.00',
                'monthly_discount' => '120.00',
                'price_per_km' => '0.35',
                'security_deposit' => '350.00',
                'minimum_driver_age' => '23',
                'mileage' => '16',
                'luggage_capacity' => '3',
                'horsepower' => '138',
                'co2' => '130',
                'gross_vehicle_mass' => '1850',
                'vehicle_height' => '1.46',
                'dealer_cost' => '55.00',
                'fuel_policy' => 'full_to_full',
                'guidelines' => 'Bring passport and driving license.',
                'terms_policy' => 'No smoking. Return the car in clean condition.',
                'rental_policy' => 'Late return and damage charges apply according to the signed rental agreement.',
                'payment_methods' => 'cash|credit_card',
                'features' => '',
                'essential_plan_price' => '109.00',
                'essential_plan_features' => 'Roadside assistance|Theft protection',
                'essential_plan_description' => 'Core protection for standard rentals.',
                'premium_plan_price' => '',
                'premium_plan_features' => '',
                'premium_plan_description' => '',
                'premium_plus_plan_price' => '',
                'premium_plus_plan_features' => '',
                'premium_plus_plan_description' => '',
                'custom_addons' => 'Baby Seat~12.50~1~custom~Child seat',
                'pickup_times' => '09:00|12:00|16:00',
                'return_times' => '10:00|13:00|17:00',
                'primary_image_ref' => '',
                'gallery_image_refs' => '',
            ],
            [
                'brand' => 'Volkswagen',
                'model' => 'Tiguan',
                'body_style' => 'suv',
                'transmission' => 'manual',
                'fuel' => 'diesel',
                'air_conditioning' => '1',
                'seating_capacity' => '5',
                'number_of_doors' => '5',
                'color' => 'Black',
                'status' => 'available',
                'registration_number' => 'TEST-002',
                'registration_date' => '2024-03-10',
                'phone_number' => '+31612345678',
                'price_per_day' => '109.00',
                'price_per_week' => '660.00',
                'weekly_discount' => '40.00',
                'price_per_month' => '2280.00',
                'monthly_discount' => '150.00',
                'price_per_km' => '0.40',
                'security_deposit' => '450.00',
                'minimum_driver_age' => '25',
                'mileage' => '18',
                'luggage_capacity' => '4',
                'horsepower' => '150',
                'co2' => '145',
                'gross_vehicle_mass' => '2100',
                'vehicle_height' => '1.67',
                'dealer_cost' => '65.00',
                'fuel_policy' => 'same_to_same',
                'guidelines' => 'Customer must present valid driving licence and ID.',
                'terms_policy' => 'Pets only with prior approval. Return with the same fuel level.',
                'rental_policy' => 'Cross-border use, late fees, and insurance conditions follow the signed rental agreement.',
                'payment_methods' => 'credit_card|bank_wire',
                'features' => '',
                'essential_plan_price' => '',
                'essential_plan_features' => '',
                'essential_plan_description' => '',
                'premium_plan_price' => '145.00',
                'premium_plan_features' => 'Zero excess|Glass coverage',
                'premium_plan_description' => 'Extra coverage with reduced excess.',
                'premium_plus_plan_price' => '179.00',
                'premium_plus_plan_features' => 'Full damage waiver|Priority support',
                'premium_plus_plan_description' => 'Highest protection tier for premium bookings.',
                'custom_addons' => 'GPS~8.00~1~custom~Navigation unit;WiFi~10.00~1~custom~Portable hotspot',
                'pickup_times' => '08:00|11:00|15:00',
                'return_times' => '09:00|12:00|16:00',
                'primary_image_ref' => '',
                'gallery_image_refs' => '',
            ],
        ];

        return array_map(function (array $defaults, int $index) use ($categories, $vendorLocations) {
            $category = $categories->get($index) ?? $categories->first();
            $location = $vendorLocations->get($index) ?? $vendorLocations->first();

            return array_map(function (array $column) use ($defaults, $category, $location) {
                $key = $column['key'];

                return match ($key) {
                    'category' => $category?->name ?? '',
                    'vendor_location_code' => $location?->name ?: $location?->code ?: (string) ($location?->id ?? ''),
                    'registration_country' => $location?->country_code ?: $location?->country ?: 'NL',
                    default => $defaults[$key] ?? ($column['example'] ?? ''),
                };
            }, $this->columns());
        }, $defaultRows, array_keys($defaultRows));
    }
}
