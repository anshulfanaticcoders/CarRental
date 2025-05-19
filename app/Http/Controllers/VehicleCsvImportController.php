<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogHelper;
use App\Models\BookingAddon;
use App\Models\Plan;
use App\Models\Vehicle;
use App\Models\VehicleBenefit;
use App\Models\VehicleImage;
use App\Models\VehicleSpecification;
use App\Models\VendorVehicleAddon;
use App\Models\VendorVehiclePlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class VehicleCsvImportController extends Controller
{
    public function index()
    {
        // Get available plans and addons for the template
        $plans = Plan::select('id', 'plan_type')->get();
        $addons = BookingAddon::select('id', 'extra_name')->get();
        
        return Inertia::render('Auth/VehicleCsvImport', [
            'plans' => $plans,
            'addons' => $addons
        ]);
    }

    public function downloadSample()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="vehicle_sample.csv"',
        ];

        $columns = [
            'brand', 'model', 'category_id', 'color', 'mileage', 'transmission', 'fuel', 
            'seating_capacity', 'number_of_doors', 'luggage_capacity', 'horsepower', 'co2',
            'location', 'city', 'state', 'country', 'status', 'features', 'featured',
            'security_deposit', 'payment_method', 'guidelines', 'price_per_day', 
            'price_per_week', 'weekly_discount', 'price_per_month', 'monthly_discount',
            'preferred_price_type', 'limited_km', 'cancellation_available', 'price_per_km',
            'registration_number', 'registration_country', 'registration_date', 'phone_number',
            'pickup_times', 'return_times', 'minimum_driver_age',
            // Plan columns
            'plan_ids', 'plan_types', 'plan_prices', 'plan_descriptions', 'plan_features',
            // Addon columns
            'addon_ids', 'addon_prices', 'addon_quantities'
        ];

        $output = fopen('php://output', 'w');
        fputcsv($output, $columns);

        // Add sample data row
        $sampleData = [
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'category_id' => '1',
            'color' => 'Black',
            'mileage' => '15000',
            'transmission' => 'automatic',
            'fuel' => 'petrol',
            'seating_capacity' => '5',
            'number_of_doors' => '4',
            'luggage_capacity' => '3',
            'horsepower' => '132',
            'co2' => '25',
            'location' => 'New York, NY, USA',
            'city' => 'New York',
            'state' => 'NY',
            'country' => 'USA',
            'status' => 'available',
            'features' => '["Music System", "Bluetooth", "Navigation"]',
            'featured' => '0',
            'security_deposit' => '300',
            'payment_method' => '["credit_card", "bank_wire"]',
            'guidelines' => 'No smoking, no pets',
            'price_per_day' => '40',
            'price_per_week' => '250',
            'weekly_discount' => '10',
            'price_per_month' => '900',
            'monthly_discount' => '25',
            'preferred_price_type' => 'day',
            'limited_km' => '1',
            'cancellation_available' => '1',
            'price_per_km' => '0.25',
            'registration_number' => 'ABC123',
            'registration_country' => 'USA',
            'registration_date' => '2023-01-15',
            'phone_number' => '1234567890',
            'pickup_times' => '["09:00", "14:00"]',
            'return_times' => '["10:00", "15:00"]',
            'minimum_driver_age' => '21',
            // Plan sample data
            'plan_ids' => '1,2',  // Comma-separated IDs
            'plan_types' => 'Standard Plan,Premium Plan',  // Comma-separated types
            'plan_prices' => '100,200',  // Comma-separated prices
            'plan_descriptions' => 'Basic features,Advanced features',  // Comma-separated descriptions
            'plan_features' => '["Basic Features","24/7 Support"],["Premium Features","Personal Onboardings","Dedicated Support"]',  // JSON arrays as string, separated by comma
            // Addon sample data
            'addon_ids' => '1,3',  // Comma-separated IDs
            'addon_prices' => '25,50',  // Comma-separated prices
            'addon_quantities' => '1,2'  // Comma-separated quantities
        ];

        fputcsv($output, $sampleData);
        fclose($output);

        return response()->make('', 200, $headers);
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();
        $data = array_map('str_getcsv', file($path));
        
        // Get headers and remove them from data
        $headers = array_shift($data);
        
        $importedCount = 0;
        $errorCount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($data as $rowIndex => $row) {
                $rowData = array_combine($headers, $row);
                
                // Validate required fields and formats
                $rules = [
                    'brand' => 'required|string|max:50',
                    'model' => 'required|string|max:50',
                    'category_id' => 'required|exists:vehicle_categories,id',
                    'color' => 'nullable|string|max:30',
                    'mileage' => 'nullable|numeric|min:0',
                    'transmission' => 'required|in:automatic,manual,semi-automatic', // Example enum
                    'fuel' => 'required|in:petrol,diesel,electric,hybrid', // Example enum
                    'seating_capacity' => 'required|integer|min:1',
                    'number_of_doors' => 'nullable|integer|min:1',
                    'luggage_capacity' => 'nullable|integer|min:0',
                    'horsepower' => 'nullable|integer|min:0',
                    'co2' => 'nullable|numeric|min:0',
                    'location' => 'required|string',
                    'status' => 'required|in:available,unavailable,booked,maintenance', // Example enum
                    'featured' => 'required|boolean', // Handles 0, 1, true, false
                    'security_deposit' => 'nullable|numeric|min:0',
                    'price_per_day' => 'nullable|numeric|min:0',
                    'price_per_week' => 'nullable|numeric|min:0',
                    'weekly_discount' => 'nullable|numeric|min:0|max:100',
                    'price_per_month' => 'nullable|numeric|min:0',
                    'monthly_discount' => 'nullable|numeric|min:0|max:100',
                    'preferred_price_type' => 'required|in:day,week,month',
                    'limited_km' => 'required|boolean',
                    'cancellation_available' => 'required|boolean',
                    'price_per_km' => 'nullable|numeric|min:0',
                    'registration_number' => 'required|string|max:20',
                    'registration_country' => 'required|string|max:50',
                    'registration_date' => 'required|date',
                    'phone_number' => 'required|string|max:20', // Consider a more specific phone validation rule
                    'minimum_driver_age' => 'nullable|integer|min:18',
                    // Plan/Addon counts could be validated here if needed, or by checking array lengths after explode
                ];

                // Prepare data for validation (e.g., convert boolean-like strings)
                $validationData = $rowData;
                $booleanFields = ['featured', 'limited_km', 'cancellation_available'];
                foreach ($booleanFields as $field) {
                    if (isset($validationData[$field])) {
                        $validationData[$field] = filter_var($validationData[$field], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    }
                }
                
                $validator = Validator::make($validationData, $rules);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $field => $message) {
                        $errors[] = [
                            'row' => $rowIndex + 2, // User-friendly row number (1-based for data rows)
                            'column' => $field,
                            'message' => implode(', ', $message)
                        ];
                    }
                    $errorCount++;
                    continue;
                }

                // Process features and payment_method as JSON
                $features = is_string($rowData['features']) ? json_decode($rowData['features'], true) : [];
                $paymentMethod = is_string($rowData['payment_method']) ? json_decode($rowData['payment_method'], true) : [];
                $pickupTimes = is_string($rowData['pickup_times']) ? json_decode($rowData['pickup_times'], true) : [];
                $returnTimes = is_string($rowData['return_times']) ? json_decode($rowData['return_times'], true) : [];

                // Create the vehicle
                $vehicle = Vehicle::create([
                    'vendor_id' => $request->user()->id,
                    'category_id' => $rowData['category_id'],
                    'brand' => $rowData['brand'],
                    'model' => $rowData['model'],
                    'color' => $rowData['color'],
                    'mileage' => $rowData['mileage'],
                    'transmission' => $rowData['transmission'],
                    'fuel' => $rowData['fuel'],
                    'seating_capacity' => $rowData['seating_capacity'],
                    'number_of_doors' => $rowData['number_of_doors'],
                    'luggage_capacity' => $rowData['luggage_capacity'],
                    'horsepower' => $rowData['horsepower'],
                    'co2' => $rowData['co2'],
                    'location' => $rowData['location'],
                    'city' => $rowData['city'] ?? null,
                    'state' => $rowData['state'] ?? null,
                    'country' => $rowData['country'] ?? null,
                    'status' => $rowData['status'],
                    'features' => json_encode($features),
                    'featured' => filter_var($rowData['featured'], FILTER_VALIDATE_BOOLEAN),
                    'security_deposit' => $rowData['security_deposit'],
                    'payment_method' => json_encode($paymentMethod),
                    'guidelines' => $rowData['guidelines'] ?? null,
                    'price_per_day' => $rowData['price_per_day'] ?? null,
                    'price_per_week' => $rowData['price_per_week'] ?? null,
                    'weekly_discount' => $rowData['weekly_discount'] ?? null,
                    'price_per_month' => $rowData['price_per_month'] ?? null,
                    'monthly_discount' => $rowData['monthly_discount'] ?? null,
                    'preferred_price_type' => $rowData['preferred_price_type'],
                    'limited_km' => filter_var($rowData['limited_km'], FILTER_VALIDATE_BOOLEAN),
                    'cancellation_available' => filter_var($rowData['cancellation_available'], FILTER_VALIDATE_BOOLEAN),
                    'price_per_km' => $rowData['price_per_km'] ?? null,
                    'pickup_times' => $pickupTimes,
                    'return_times' => $returnTimes,
                ]);

                // Create vehicle specifications
                VehicleSpecification::create([
                    'vehicle_id' => $vehicle->id,
                    'registration_number' => $rowData['registration_number'],
                    'registration_country' => $rowData['registration_country'],
                    'registration_date' => $rowData['registration_date'],
                    'gross_vehicle_mass' => $rowData['gross_vehicle_mass'] ?? null,
                    'vehicle_height' => $rowData['vehicle_height'] ?? null,
                    'dealer_cost' => $rowData['dealer_cost'] ?? null,
                    'phone_number' => $rowData['phone_number'],
                ]);

                // Create vehicle benefits
                VehicleBenefit::create([
                    'vehicle_id' => $vehicle->id,
                    'limited_km_per_day' => isset($rowData['limited_km_per_day']) ? filter_var($rowData['limited_km_per_day'], FILTER_VALIDATE_BOOLEAN) : false,
                    'limited_km_per_week' => isset($rowData['limited_km_per_week']) ? filter_var($rowData['limited_km_per_week'], FILTER_VALIDATE_BOOLEAN) : false,
                    'limited_km_per_month' => isset($rowData['limited_km_per_month']) ? filter_var($rowData['limited_km_per_month'], FILTER_VALIDATE_BOOLEAN) : false,
                    'limited_km_per_day_range' => $rowData['limited_km_per_day_range'] ?? null,
                    'limited_km_per_week_range' => $rowData['limited_km_per_week_range'] ?? null,
                    'limited_km_per_month_range' => $rowData['limited_km_per_month_range'] ?? null,
                    'cancellation_available_per_day' => isset($rowData['cancellation_available_per_day']) ? filter_var($rowData['cancellation_available_per_day'], FILTER_VALIDATE_BOOLEAN) : false,
                    'cancellation_available_per_week' => isset($rowData['cancellation_available_per_week']) ? filter_var($rowData['cancellation_available_per_week'], FILTER_VALIDATE_BOOLEAN) : false,
                    'cancellation_available_per_month' => isset($rowData['cancellation_available_per_month']) ? filter_var($rowData['cancellation_available_per_month'], FILTER_VALIDATE_BOOLEAN) : false,
                    'cancellation_available_per_day_date' => $rowData['cancellation_available_per_day_date'] ?? null,
                    'cancellation_available_per_week_date' => $rowData['cancellation_available_per_week_date'] ?? null,
                    'cancellation_available_per_month_date' => $rowData['cancellation_available_per_month_date'] ?? null,
                    'price_per_km_per_day' => $rowData['price_per_km_per_day'] ?? null,
                    'price_per_km_per_week' => $rowData['price_per_km_per_week'] ?? null,
                    'price_per_km_per_month' => $rowData['price_per_km_per_month'] ?? null,
                    'minimum_driver_age' => $rowData['minimum_driver_age'] ?? null,
                ]);

                // Import plans if available
                if (isset($rowData['plan_ids']) && !empty($rowData['plan_ids'])) {
                    $planIds = explode(',', $rowData['plan_ids']);
                    $planTypes = explode(',', $rowData['plan_types'] ?? '');
                    $planPrices = explode(',', $rowData['plan_prices'] ?? '');
                    $planDescriptions = explode(',', $rowData['plan_descriptions'] ?? '');
                    
                    // Parse plan features from the string
                    $planFeaturesArray = [];
                    if (isset($rowData['plan_features']) && !empty($rowData['plan_features'])) {
                        // This tries to split the features by looking for JSON arrays in a string
                        preg_match_all('/\[\s*"[^"]*"(?:\s*,\s*"[^"]*")*\s*\]/', $rowData['plan_features'], $matches);
                        if (!empty($matches[0])) {
                            $planFeaturesArray = array_map(function($item) {
                                return json_decode($item, true);
                            }, $matches[0]);
                        }
                    }

                    // Create plans
                    foreach ($planIds as $index => $planId) {
                        if (empty($planId)) continue;
                        
                        VendorVehiclePlan::create([
                            'vendor_id' => $request->user()->id,
                            'vehicle_id' => $vehicle->id,
                            'plan_id' => $planId,
                            'plan_type' => $planTypes[$index] ?? 'Standard',
                            'price' => $planPrices[$index] ?? 0,
                            'plan_description' => $planDescriptions[$index] ?? null,
                            'features' => isset($planFeaturesArray[$index]) ? json_encode($planFeaturesArray[$index]) : null,
                        ]);
                    }
                }

                // Import addons if available
                if (isset($rowData['addon_ids']) && !empty($rowData['addon_ids'])) {
                    $addonIds = explode(',', $rowData['addon_ids']);
                    $addonPrices = explode(',', $rowData['addon_prices'] ?? '');
                    $addonQuantities = explode(',', $rowData['addon_quantities'] ?? '');

                    // Create addons
                    foreach ($addonIds as $index => $addonId) {
                        if (empty($addonId)) continue;
                        
                        $addon = BookingAddon::find($addonId);
                        if (!$addon) continue;

                        VendorVehicleAddon::create([
                            'vendor_id' => $request->user()->id,
                            'vehicle_id' => $vehicle->id,
                            'addon_id' => $addonId,
                            'extra_type' => $addon->extra_type,
                            'extra_name' => $addon->extra_name,
                            'price' => $addonPrices[$index] ?? 0,
                            'quantity' => $addonQuantities[$index] ?? 1,
                            'description' => $addon->description,
                        ]);
                    }
                }

                // Log activity
                ActivityLogHelper::logActivity('create', 'Created a new vehicle via CSV import', $vehicle, $request);
                
                $importedCount++;
            }

            DB::commit();
            
            $flashData = [
                'type' => $errorCount > 0 ? ($importedCount > 0 ? 'warning' : 'error') : 'success',
                'message' => "$importedCount vehicle(s) imported. $errorCount error(s) found.",
            ];
            if ($errorCount > 0) {
                $flashData['csv_errors'] = $errors; // Use a specific key for CSV errors
                if ($importedCount == 0) {
                     $flashData['message'] = "Import failed. Please fix the " . $errorCount . " error(s) in your CSV file.";
                } else {
                     $flashData['message'] = "Import partially completed. $importedCount vehicle(s) imported, but $errorCount error(s) were found.";
                }
            } else {
                $flashData['message'] = "$importedCount vehicle(s) imported successfully.";
            }
            return redirect()->back()->with('flash', $flashData);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()->with('flash', [
                'type' => 'error',
                'message' => 'A critical error occurred during import: ' . $e->getMessage()
            ]);
        }
    }
}
