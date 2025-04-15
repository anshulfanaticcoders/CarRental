<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogHelper;
use App\Models\{
    BookingAddon,
    User,
    Vehicle,
    VehicleBenefit,
    VehicleFeature,
    VehicleImage,
    VehicleSpecification,
    VendorVehicleAddon,
    VendorVehiclePlan
};
use App\Notifications\VehicleCreatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class BulkVehicleController extends Controller
{
    public function create()
    {
        $categories = DB::table('vehicle_categories')->select('id', 'name')->get();

        return Inertia::render('Bulk/CreateListing', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // Max 10MB
        ]);

        $file = $request->file('csv_file');
        $records = array_map('str_getcsv', file($file->getRealPath()));
        $headers = array_map('trim', array_shift($records)); // Trim headers

        $csvData = [];
        foreach ($records as $record) {
            if (count($record) === count($headers)) {
                $csvData[] = array_combine($headers, $record);
            }
        }

        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($csvData as $index => $data) {
            // Safe parse JSON fields
            $data['features'] = $this->parseJson($data['features'] ?? '[]');
            $data['payment_method'] = $this->parseJson($data['payment_method'] ?? '["credit_card"]');
            $data['pickup_times'] = $this->parseJson($data['pickup_times'] ?? '["09:00", "17:00"]');
            $data['return_times'] = $this->parseJson($data['return_times'] ?? '["09:00", "17:00"]');

            // Cast booleans
            $booleanFields = ['featured', 'limited_km', 'cancellation_available'];
            foreach ($booleanFields as $field) {
                $data[$field] = filter_var($data[$field] ?? false, FILTER_VALIDATE_BOOLEAN);
            }

            $validator = Validator::make($data, [
                'category_id' => 'required|exists:vehicle_categories,id',
                'brand' => 'required|string|max:50',
                'model' => 'required|string|max:50',
                'color' => 'required|string|max:30',
                'mileage' => 'required|integer|min:0',
                'transmission' => 'required|string',
                'fuel' => 'required|string',
                'seating_capacity' => 'required|integer|min:1',
                'number_of_doors' => 'required|integer|min:2',
                'luggage_capacity' => 'required|integer|min:0',
                'horsepower' => 'required|integer|min:0',
                'co2' => 'required|string',
                'location' => 'required|string',
                'status' => 'required|in:available,rented,maintenance',
                'security_deposit' => 'required|numeric',
                'preferred_price_type' => 'required|in:day,week,month',
                'price_per_day' => 'nullable|numeric',
                'price_per_week' => 'nullable|numeric',
                'price_per_month' => 'nullable|numeric',
            ]);

            if ($validator->fails()) {
                $failedCount++;
                $errors[] = [
                    'row' => $index + 2,
                    'errors' => $validator->errors()->toArray()
                ];
                continue;
            }

            try {
                DB::beginTransaction();

                $vehicle = Vehicle::create([
                    'vendor_id' => $request->user()->id,
                    'category_id' => $data['category_id'],
                    'brand' => $data['brand'],
                    'model' => $data['model'],
                    'color' => $data['color'],
                    'mileage' => $data['mileage'],
                    'transmission' => $data['transmission'],
                    'fuel' => $data['fuel'],
                    'seating_capacity' => $data['seating_capacity'],
                    'number_of_doors' => $data['number_of_doors'],
                    'luggage_capacity' => $data['luggage_capacity'],
                    'horsepower' => $data['horsepower'],
                    'co2' => $data['co2'],
                    'location' => $data['location'],
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'city' => $data['city'] ?? null,
                    'state' => $data['state'] ?? null,
                    'country' => $data['country'] ?? null,
                    'status' => $data['status'],
                    'features' => json_encode($data['features']),
                    'featured' => $data['featured'],
                    'security_deposit' => $data['security_deposit'],
                    'payment_method' => json_encode($data['payment_method']),
                    'guidelines' => $data['guidelines'] ?? null,
                    'price_per_day' => $data['price_per_day'] ?? null,
                    'price_per_week' => $data['price_per_week'] ?? null,
                    'weekly_discount' => $data['weekly_discount'] ?? null,
                    'price_per_month' => $data['price_per_month'] ?? null,
                    'monthly_discount' => $data['monthly_discount'] ?? null,
                    'preferred_price_type' => $data['preferred_price_type'],
                    'limited_km' => $data['limited_km'],
                    'cancellation_available' => $data['cancellation_available'],
                    'price_per_km' => $data['price_per_km'] ?? null,
                    'pickup_times' => $data['pickup_times'],
                    'return_times' => $data['return_times'],
                ]);

                VehicleBenefit::create([
                    'vehicle_id' => $vehicle->id,
                    'limited_km_per_day' => $data['limited_km'],
                    'limited_km_per_week' => $data['limited_km'],
                    'limited_km_per_month' => $data['limited_km'],
                    'limited_km_per_day_range' => $data['limited_km_per_day_range'] ?? 100,
                    'limited_km_per_week_range' => $data['limited_km_per_week_range'] ?? 700,
                    'limited_km_per_month_range' => $data['limited_km_per_month_range'] ?? 3000,
                    'cancellation_available_per_day' => $data['cancellation_available'],
                    'cancellation_available_per_week' => $data['cancellation_available'],
                    'cancellation_available_per_month' => $data['cancellation_available'],
                    'cancellation_available_per_day_date' => $data['cancellation_available_per_day_date'] ?? 1,
                    'cancellation_available_per_week_date' => $data['cancellation_available_per_week_date'] ?? 3,
                    'cancellation_available_per_month_date' => $data['cancellation_available_per_month_date'] ?? 7,
                    'price_per_km_per_day' => $data['price_per_km'] ?? 0,
                    'price_per_km_per_week' => $data['price_per_km'] ?? 0,
                    'price_per_km_per_month' => $data['price_per_km'] ?? 0,
                    'minimum_driver_age' => $data['minimum_driver_age'] ?? 18,
                ]);

                VehicleSpecification::create([
                    'vehicle_id' => $vehicle->id,
                    'registration_number' => $data['registration_number'] ?? 'REG-' . rand(1000, 9999),
                    'registration_country' => $data['registration_country'] ?? $data['country'] ?? 'Unknown',
                    'registration_date' => $data['registration_date'] ?? now(),
                    'gross_vehicle_mass' => $data['gross_vehicle_mass'] ?? 0,
                    'vehicle_height' => $data['vehicle_height'] ?? 0,
                    'dealer_cost' => $data['dealer_cost'] ?? 0,
                    'phone_number' => $data['phone_number'] ?? $request->user()->phone ?? '1234567890',
                ]);

                VehicleImage::create([
                    'vehicle_id' => $vehicle->id,
                    'image_path' => 'vehicle_images/placeholder_' . $vehicle->id . '.jpg',
                    'image_url' => '/api/placeholder/400/320',
                    'image_type' => 'primary',
                ]);

                ActivityLogHelper::logActivity('create', 'Created a new vehicle via bulk upload', $vehicle, $request);

                // Notify admin
                if ($admin = User::where('email', 'anshul@fanaticcoders.com')->first()) {
                    $admin->notify(new VehicleCreatedNotification($vehicle));
                }

                DB::commit();
                $successCount++;
            } catch (\Throwable $e) {
                DB::rollBack();
                $failedCount++;
                $errors[] = [
                    'row' => $index + 2,
                    'message' => $e->getMessage(),
                ];
            }
        }
        // dd($data);

        return Redirect::back();

    }

    public function downloadTemplate()
    {
        $headers = [
            'vendor_id', 'category_id', 'seating_capacity', 'brand', 'model', 
            'number_of_doors', 'transmission', 'luggage_capacity', 'horsepower', 
            'fuel', 'co2', 'color', 'mileage', 'location', 'city', 'state', 
            'country', 'latitude', 'longitude', 'status', 'features', 'featured', 
            'security_deposit', 'payment_method', 'guidelines', 'price_per_day', 
            'price_per_week', 'weekly_discount', 'price_per_month', 'monthly_discount', 
            'preferred_price_type', 'limited_km', 'cancellation_available', 'price_per_km',
            'registration_number', 'registration_country', 'registration_date', 
            'phone_number', 'pickup_times', 'return_times'
        ];

        $data = [[
            'auto', '2', '5', 'Toyota', 'Corolla', 
            '4', 'automatic', '3', '132', 'petrol', 
            '115', 'Silver', '10000', 'New York, NY, USA', 'New York', 'NY', 
            'USA', '40.7128', '-74.0060', 'available', 
            '["Music System", "Bluetooth", "Navigation"]', '0', 
            '300', '["credit_card","cash"]', 'Please return with full tank.', '50', 
            '300', '10', '1200', '15', 'day', '0', '1', '0.5',
            'ABC123', 'USA', '2022-01-01', 
            '1234567890', '["09:00", "17:00"]', '["09:00", "17:00"]'
        ]];

        $callback = function () use ($headers, $data) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        $filename = 'vehicle_bulk_upload_template_' . date('Y-m-d') . '.csv';

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    private function parseJson(string $value): array
    {
        $parsed = json_decode($value, true);
        return is_array($parsed) ? $parsed : [];
    }
}
