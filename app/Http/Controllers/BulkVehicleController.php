<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogHelper;
use App\Models\{
    BookingAddon,
    Plan,
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class BulkVehicleController extends Controller
{
    public function create()
    {
        $categories = DB::table('vehicle_categories')->select('id', 'name')->get();
        $plans = Plan::select('id', 'plan_type')->get();
        $addons = BookingAddon::select('id', 'extra_name', 'extra_type')->get();

        return Inertia::render('Bulk/CreateListing', [
            'categories' => $categories,
            'plans' => $plans,
            'addons' => $addons,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('csv_file');
        $records = array_map('str_getcsv', file($file->getRealPath()));
        $headers = array_map('trim', array_shift($records));

        $csvData = [];
        foreach ($records as $record) {
            if (count($record) === count($headers)) {
                $csvData[] = array_combine($headers, array_map('trim', $record));
            }
        }

        // Debug Point 1: Inspect raw CSV data
        // dd('Raw CSV Data', $csvData);

        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($csvData as $index => $data) {
            // Parse JSON fields
            $data['features'] = $this->parseJson($data['features'] ?? '[]');
            $data['payment_method'] = $this->parseJson($data['payment_method'] ?? '["credit_card"]');
            $data['pickup_times'] = $this->parseJson($data['pickup_times'] ?? '["09:00", "17:00"]');
            $data['return_times'] = $this->parseJson($data['return_times'] ?? '["09:00", "17:00"]');
            $data['selected_plans'] = $this->parseJson($data['selected_plans'] ?? '[]');
            $data['selected_addons'] = $this->parseJson($data['selected_addons'] ?? '[]');

            // Debug Point 2: Inspect data after JSON parsing
            // dd('After JSON Parsing for Row ' . ($index + 2), $data);

            // Cast booleans
            $booleanFields = [
                'featured', 'limited_km', 'cancellation_available',
                'limited_km_per_day', 'limited_km_per_week', 'limited_km_per_month',
                'cancellation_available_per_day', 'cancellation_available_per_week',
                'cancellation_available_per_month'
            ];
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
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'country' => 'nullable|string|max:100',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'status' => 'required|in:available,rented,maintenance',
                'features' => 'array',
                'featured' => 'boolean',
                'security_deposit' => 'required|numeric|min:0',
                'payment_method' => 'required|array',
                'payment_method.*' => 'string|in:credit_card,cheque,bank_wire,cryptocurrency,cash',
                'guidelines' => 'nullable|string|max:50000',
                'price_per_day' => 'nullable|numeric|min:0',
                'price_per_week' => 'nullable|numeric|min:0',
                'weekly_discount' => 'nullable|numeric|min:0',
                'price_per_month' => 'nullable|numeric|min:0',
                'monthly_discount' => 'nullable|numeric|min:0',
                'preferred_price_type' => 'required|in:day,week,month',
                'limited_km' => 'boolean',
                'cancellation_available' => 'boolean',
                'price_per_km' => 'nullable|numeric|min:0',
                'registration_number' => 'required|string|max:50',
                'registration_country' => 'required|string|max:50',
                'registration_date' => 'required|date',
                'gross_vehicle_mass' => 'nullable|integer|min:0',
                'vehicle_height' => 'nullable|numeric|min:0',
                'dealer_cost' => 'nullable|numeric|min:0',
                'phone_number' => 'required|string|max:15',
                'pickup_times' => 'required|array',
                'return_times' => 'required|array',
                'limited_km_per_day' => 'boolean',
                'limited_km_per_week' => 'boolean',
                'limited_km_per_month' => 'boolean',
                'limited_km_per_day_range' => 'nullable|numeric|min:0',
                'limited_km_per_week_range' => 'nullable|numeric|min:0',
                'limited_km_per_month_range' => 'nullable|numeric|min:0',
                'cancellation_available_per_day' => 'boolean',
                'cancellation_available_per_week' => 'boolean',
                'cancellation_available_per_month' => 'boolean',
                'cancellation_available_per_day_date' => 'nullable|integer|min:0',
                'cancellation_available_per_week_date' => 'nullable|integer|min:0',
                'cancellation_available_per_month_date' => 'nullable|integer|min:0',
                'price_per_km_per_day' => 'nullable|numeric|min:0',
                'price_per_km_per_week' => 'nullable|numeric|min:0',
                'price_per_km_per_month' => 'nullable|numeric|min:0',
                'minimum_driver_age' => 'nullable|integer|min:18',
                'selected_plans' => 'array',
                'selected_plans.*.id' => 'required|exists:plans,id',
                'selected_plans.*.plan_type' => 'required|string',
                'selected_plans.*.plan_value' => 'required|numeric|min:0',
                'selected_plans.*.features' => 'nullable|array',
                'selected_plans.*.plan_description' => 'nullable|string',
                'selected_addons' => 'array',
                'selected_addons.*.id' => 'required|exists:booking_addons,id',
                'selected_addons.*.price' => 'required|numeric|min:0',
                'selected_addons.*.quantity' => 'required|integer|min:1',
                'selected_addons.*.extra_type' => 'required|string',
                'selected_addons.*.extra_name' => 'required|string',
                'selected_addons.*.description' => 'required|string',
            ]);

            if ($validator->fails()) {
                $failedCount++;
                $errors[] = [
                    'row' => $index + 2,
                    'errors' => $validator->errors()->toArray()
                ];
                dd('Validation Errors for Row ' . ($index + 2), $validator->errors()->toArray());
                continue;
            }

            // Debug Point 3: Inspect validated data
            // dd('Validated Data for Row ' . ($index + 2), $data);

            try {
                DB::beginTransaction();

                // Debug Point 4: Inspect data before creating records
                // dd('Data Before Creating Records for Row ' . ($index + 2), [
                //     'vehicle_data' => $data,
                //     'plans' => $data['selected_plans'],
                //     'addons' => $data['selected_addons']
                // ]);

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
                    'pickup_times' => json_encode($data['pickup_times']),
                    'return_times' => json_encode($data['return_times']),
                ]);

                VehicleBenefit::create([
                    'vehicle_id' => $vehicle->id,
                    'limited_km_per_day' => $data['limited_km_per_day'],
                    'limited_km_per_week' => $data['limited_km_per_week'],
                    'limited_km_per_month' => $data['limited_km_per_month'],
                    'limited_km_per_day_range' => $data['limited_km_per_day_range'] ?? null,
                    'limited_km_per_week_range' => $data['limited_km_per_week_range'] ?? null,
                    'limited_km_per_month_range' => $data['limited_km_per_month_range'] ?? null,
                    'cancellation_available_per_day' => $data['cancellation_available_per_day'],
                    'cancellation_available_per_week' => $data['cancellation_available_per_week'],
                    'cancellation_available_per_month' => $data['cancellation_available_per_month'],
                    'cancellation_available_per_day_date' => $data['cancellation_available_per_day_date'] ?? null,
                    'cancellation_available_per_week_date' => $data['cancellation_available_per_week_date'] ?? null,
                    'cancellation_available_per_month_date' => $data['cancellation_available_per_month_date'] ?? null,
                    'price_per_km_per_day' => $data['price_per_km_per_day'] ?? null,
                    'price_per_km_per_week' => $data['price_per_km_per_week'] ?? null,
                    'price_per_km_per_month' => $data['price_per_km_per_month'] ?? null,
                    'minimum_driver_age' => $data['minimum_driver_age'] ?? null,
                ]);

                VehicleSpecification::create([
                    'vehicle_id' => $vehicle->id,
                    'registration_number' => $data['registration_number'],
                    'registration_country' => $data['registration_country'],
                    'registration_date' => $data['registration_date'],
                    'gross_vehicle_mass' => $data['gross_vehicle_mass'] ?? 0,
                    'vehicle_height' => $data['vehicle_height'] ?? 0,
                    'dealer_cost' => $data['dealer_cost'] ?? 0,
                    'phone_number' => $data['phone_number'],
                ]);

                // Handle selected plans
                foreach ($data['selected_plans'] as $plan) {
                    VendorVehiclePlan::create([
                        'vendor_id' => $request->user()->id,
                        'vehicle_id' => $vehicle->id,
                        'plan_id' => $plan['id'],
                        'plan_type' => $plan['plan_type'],
                        'price' => $plan['plan_value'],
                        'features' => isset($plan['features']) ? json_encode($plan['features']) : null,
                        'plan_description' => $plan['plan_description'] ?? null,
                    ]);
                }

                // Handle selected addons
                foreach ($data['selected_addons'] as $addon) {
                    VendorVehicleAddon::create([
                        'vendor_id' => $request->user()->id,
                        'vehicle_id' => $vehicle->id,
                        'addon_id' => $addon['id'],
                        'extra_type' => $addon['extra_type'],
                        'extra_name' => $addon['extra_name'],
                        'price' => $addon['price'],
                        'quantity' => $addon['quantity'],
                        'description' => $addon['description'],
                    ]);
                }

                // Create placeholder image
                VehicleImage::create([
                    'vehicle_id' => $vehicle->id,
                    'image_path' => 'vehicle_images/placeholder_' . $vehicle->id . '.jpg',
                    'image_url' => '/api/placeholder/400/320',
                    'image_type' => 'primary',
                ]);

                ActivityLogHelper::logActivity('create', 'Created a new vehicle via bulk upload', $vehicle, $request);

                DB::commit();
                $successCount++;
            } catch (\Throwable $e) {
                DB::rollBack();
                $failedCount++;
                $errors[] = [
                    'row' => $index + 2,
                    'message' => $e->getMessage(),
                    'errors' => ['exception' => $e->getMessage()]
                ];
            }
        }

        $message = "Bulk upload completed: {$successCount} vehicles uploaded successfully";
        if ($failedCount > 0) {
            $message .= ", {$failedCount} vehicles failed to upload";
        }

        return redirect()->back()->with([
            'message' => $message,
            'type' => $successCount > 0 ? 'success' : 'error',
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'upload_errors' => $errors
        ]);
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
            'gross_vehicle_mass', 'vehicle_height', 'dealer_cost', 'phone_number',
            'pickup_times', 'return_times', 'limited_km_per_day', 'limited_km_per_week',
            'limited_km_per_month', 'limited_km_per_day_range', 'limited_km_per_week_range',
            'limited_km_per_month_range', 'cancellation_available_per_day',
            'cancellation_available_per_week', 'cancellation_available_per_month',
            'cancellation_available_per_day_date', 'cancellation_available_per_week_date',
            'cancellation_available_per_month_date', 'price_per_km_per_day',
            'price_per_km_per_week', 'price_per_km_per_month', 'minimum_driver_age',
            'selected_plans', 'selected_addons'
        ];

        $data = [[
            '1', '2', '5', 'Toyota', 'Corolla',
            '4', 'automatic', '3', '132', 'petrol',
            '115', 'Silver', '10000', 'New York, NY, USA', 'New York', 'NY',
            'USA', '40.7128', '-74.0060', 'available',
            '["Music System","Bluetooth","Navigation"]', '0',
            '300', '["credit_card","cash"]', 'Please return with full tank.', '50',
            '300', '10', '1200', '15', 'day', '0', '1', '0.5',
            'ABC123', 'USA', '2022-01-01',
            '2000', '1.5', '25000', '1234567890',
            '["09:00","17:00"]', '["09:00","17:00"]', '1', '1', '1',
            '100', '700', '3000', '1', '1', '1',
            '1', '3', '7', '0.5', '0.4', '0.3', '21',
            '[{"id":1,"plan_type":"basic","plan_value":50,"features":["Insurance","Roadside Assistance"],"plan_description":"Basic coverage"}]',
            '[{"id":1,"price":10,"quantity":2,"extra_type":"equipment","extra_name":"GPS","description":"Navigation system"}]'
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
        if (empty(trim($value))) {
            return [];
        }

        $parsed = json_decode($value, true);
        if (is_array($parsed)) {
            return $parsed;
        }

        $value = trim($value, '"\'');
        $value = str_replace("'", '"', $value);
        $parsed = json_decode($value, true);
        return is_array($parsed) ? $parsed : [];
    }
}