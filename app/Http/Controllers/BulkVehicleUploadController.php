<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogHelper;
use App\Models\BookingAddon;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBenefit;
use App\Models\VehicleFeature;
use App\Models\VehicleImage;
use App\Models\VehicleSpecification;
use App\Models\VendorProfile;
use App\Models\VendorVehicleAddon;
use App\Models\VendorVehiclePlan;
use App\Notifications\VehicleCreatedNotification;
use App\Notifications\Vendor\VendorVehicleCreateCompanyNotification;
use App\Notifications\Vendor\VendorVehicleCreateNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class BulkVehicleUploadController extends Controller
{
    public function create()
    {
        return Inertia::render('Auth/BulkVehicleUpload');
    }

    public function store(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        $path = $request->file('csv_file')->getRealPath();
        $file = fopen($path, 'r');
        $header = fgetcsv($file); // Get header row

        // Define expected header columns (adjust as per your CSV structure)
        $expectedHeader = [
            'category_id', 'brand', 'model', 'color', 'mileage', 'transmission', 'fuel',
            'seating_capacity', 'number_of_doors', 'luggage_capacity', 'horsepower', 'co2',
            'location', 'latitude', 'longitude', 'city', 'state', 'country', 'status',
            'features', // Assuming features is a comma-separated string e.g. "Feature1,Feature2"
            'featured', 'security_deposit', 'payment_method', // Assuming payment_method is a comma-separated string e.g. "cash,credit_card"
            'guidelines', 'price_per_day', 'price_per_week', 'weekly_discount',
            'price_per_month', 'monthly_discount', 'preferred_price_type',
            'limited_km', 'cancellation_available', 'price_per_km',
            'registration_number', 'registration_country', 'registration_date',
            'gross_vehicle_mass', 'vehicle_height', 'dealer_cost', 'phone_number',
            'limited_km_per_day', 'limited_km_per_week', 'limited_km_per_month',
            'limited_km_per_day_range', 'limited_km_per_week_range', 'limited_km_per_month_range',
            'cancellation_available_per_day', 'cancellation_available_per_week', 'cancellation_available_per_month',
            'cancellation_available_per_day_date', 'cancellation_available_per_week_date', 'cancellation_available_per_month_date',
            'price_per_km_per_day', 'price_per_km_per_week', 'price_per_km_per_month',
            'minimum_driver_age',
            'pickup_times', // Assuming comma-separated times e.g. "10:00,14:00"
            'return_times', // Assuming comma-separated times e.g. "10:00,14:00"
            'image_urls', // Comma-separated image URLs. First one will be primary.
            'selected_plans_json', // JSON string for selected plans: [{"id":1, "plan_type":"Basic", "price":100, "features":["feat1"], "description":"desc"}, ...]
            'selected_addons_json' // JSON string for selected addons: [{"id":1, "price":10, "quantity":2}, ...]
        ];

        // Validate header
        if ($header !== $expectedHeader) {
            fclose($file);
            return redirect()->back()->withErrors(['csv_file' => 'CSV header does not match the expected format. Please download the template.']);
        }

        $vehiclesData = [];
        while (($row = fgetcsv($file)) !== false) {
            $vehiclesData[] = array_combine($header, $row);
        }
        fclose($file);

        $user = $request->user();
        $createdCount = 0;
        $errorMessages = [];

        foreach ($vehiclesData as $index => $vehicleData) {
            $validator = Validator::make($vehicleData, [
                'category_id' => 'required|exists:vehicle_categories,id',
                'brand' => 'required|string|max:50',
                'model' => 'required|string|max:50',
                'color' => 'required|string|max:30',
                'mileage' => 'required|numeric', // Changed to numeric for CSV
                'transmission' => 'required|string',
                'fuel' => 'required|string',
                'seating_capacity' => 'required|integer|min:1',
                'number_of_doors' => 'required|integer|min:2',
                'luggage_capacity' => 'required|integer|min:0',
                'horsepower' => 'required|numeric', // Changed to numeric
                'co2' => 'required|string',
                'location' => 'required|string',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'country' => 'nullable|string|max:100',
                'status' => 'required|in:available,rented,maintenance',
                'features' => 'nullable|string', // Comma-separated string
                'featured' => 'boolean',
                'security_deposit' => 'required|numeric', // Changed to numeric
                'payment_method' => 'required|string', // Comma-separated string
                'guidelines' => 'nullable|string|max:50000',
                'price_per_day' => 'nullable|numeric',
                'price_per_week' => 'nullable|numeric',
                'weekly_discount' => 'nullable|numeric',
                'price_per_month' => 'nullable|numeric',
                'monthly_discount' => 'nullable|numeric',
                'preferred_price_type' => 'required|in:day,week,month',
                'limited_km' => 'boolean',
                'cancellation_available' => 'boolean',
                'price_per_km' => 'nullable|numeric',
                'registration_number' => 'required|string|max:50',
                'registration_country' => 'required|string|max:50',
                'registration_date' => 'required|date_format:Y-m-d', // Ensure CSV date format matches
                'gross_vehicle_mass' => 'nullable|integer|min:0',
                'vehicle_height' => 'nullable|integer|min:0',
                'dealer_cost' => 'nullable|numeric|min:0',
                'phone_number' => 'required|string|max:15',
                'limited_km_per_day' => 'boolean',
                'limited_km_per_week' => 'boolean',
                'limited_km_per_month' => 'boolean',
                'limited_km_per_day_range' => 'nullable|numeric',
                'limited_km_per_week_range' => 'nullable|numeric',
                'limited_km_per_month_range' => 'nullable|numeric',
                'cancellation_available_per_day' => 'boolean',
                'cancellation_available_per_week' => 'boolean',
                'cancellation_available_per_month' => 'boolean',
                'cancellation_available_per_day_date' => 'nullable|integer',
                'cancellation_available_per_week_date' => 'nullable|integer',
                'cancellation_available_per_month_date' => 'nullable|integer',
                'price_per_km_per_day' => 'nullable|numeric',
                'price_per_km_per_week' => 'nullable|numeric',
                'price_per_km_per_month' => 'nullable|numeric',
                'minimum_driver_age' => 'nullable|integer',
                'pickup_times' => 'required|string', // Comma-separated string
                'return_times' => 'required|string', // Comma-separated string
                'image_urls' => 'nullable|string', // Comma-separated URLs
                'selected_plans_json' => 'nullable|json',
                'selected_addons_json' => 'nullable|json',
            ]);

            if ($validator->fails()) {
                $errorMessages[] = "Row " . ($index + 1) . ": " . implode(', ', $validator->errors()->all());
                continue; // Skip this row
            }

            try {
                DB::transaction(function () use ($vehicleData, $user, &$createdCount, $request) {
                    $vehicle = Vehicle::create([
                        'vendor_id' => $user->id,
                        'category_id' => $vehicleData['category_id'],
                        'brand' => $vehicleData['brand'],
                        'model' => $vehicleData['model'],
                        'color' => $vehicleData['color'],
                        'mileage' => $vehicleData['mileage'],
                        'transmission' => $vehicleData['transmission'],
                        'fuel' => $vehicleData['fuel'],
                        'seating_capacity' => $vehicleData['seating_capacity'],
                        'number_of_doors' => $vehicleData['number_of_doors'],
                        'luggage_capacity' => $vehicleData['luggage_capacity'],
                        'horsepower' => $vehicleData['horsepower'],
                        'co2' => $vehicleData['co2'],
                        'location' => $vehicleData['location'],
                        'latitude' => $vehicleData['latitude'] ?? null,
                        'longitude' => $vehicleData['longitude'] ?? null,
                        'city' => $vehicleData['city'] ?? null,
                        'state' => $vehicleData['state'] ?? null,
                        'country' => $vehicleData['country'] ?? null,
                        'status' => $vehicleData['status'],
                        'features' => !empty($vehicleData['features']) ? json_encode(explode(',', $vehicleData['features'])) : null,
                        'featured' => filter_var($vehicleData['featured'], FILTER_VALIDATE_BOOLEAN),
                        'security_deposit' => $vehicleData['security_deposit'],
                        'payment_method' => !empty($vehicleData['payment_method']) ? json_encode(explode(',', $vehicleData['payment_method'])) : null,
                        'guidelines' => $vehicleData['guidelines'],
                        'price_per_day' => $vehicleData['price_per_day'] ?? null,
                        'price_per_week' => $vehicleData['price_per_week'] ?? null,
                        'weekly_discount' => $vehicleData['weekly_discount'] ?? null,
                        'price_per_month' => $vehicleData['price_per_month'] ?? null,
                        'monthly_discount' => $vehicleData['monthly_discount'] ?? null,
                        'preferred_price_type' => $vehicleData['preferred_price_type'],
                        'limited_km' => filter_var($vehicleData['limited_km'], FILTER_VALIDATE_BOOLEAN),
                        'cancellation_available' => filter_var($vehicleData['cancellation_available'], FILTER_VALIDATE_BOOLEAN),
                        'price_per_km' => $vehicleData['price_per_km'] ?? null,
                        'pickup_times' => !empty($vehicleData['pickup_times']) ? explode(',', $vehicleData['pickup_times']) : [],
                        'return_times' => !empty($vehicleData['return_times']) ? explode(',', $vehicleData['return_times']) : [],
                    ]);

                    VehicleBenefit::create([
                        'vehicle_id' => $vehicle->id,
                        'limited_km_per_day' => filter_var($vehicleData['limited_km_per_day'], FILTER_VALIDATE_BOOLEAN),
                        'limited_km_per_week' => filter_var($vehicleData['limited_km_per_week'], FILTER_VALIDATE_BOOLEAN),
                        'limited_km_per_month' => filter_var($vehicleData['limited_km_per_month'], FILTER_VALIDATE_BOOLEAN),
                        'limited_km_per_day_range' => $vehicleData['limited_km_per_day_range'] === '' ? null : $vehicleData['limited_km_per_day_range'],
                        'limited_km_per_week_range' => $vehicleData['limited_km_per_week_range'] === '' ? null : $vehicleData['limited_km_per_week_range'],
                        'limited_km_per_month_range' => $vehicleData['limited_km_per_month_range'] === '' ? null : $vehicleData['limited_km_per_month_range'],
                        'cancellation_available_per_day' => filter_var($vehicleData['cancellation_available_per_day'], FILTER_VALIDATE_BOOLEAN),
                        'cancellation_available_per_week' => filter_var($vehicleData['cancellation_available_per_week'], FILTER_VALIDATE_BOOLEAN),
                        'cancellation_available_per_month' => filter_var($vehicleData['cancellation_available_per_month'], FILTER_VALIDATE_BOOLEAN),
                        'cancellation_available_per_day_date' => $vehicleData['cancellation_available_per_day_date'] === '' ? null : $vehicleData['cancellation_available_per_day_date'],
                        'cancellation_available_per_week_date' => $vehicleData['cancellation_available_per_week_date'] === '' ? null : $vehicleData['cancellation_available_per_week_date'],
                        'cancellation_available_per_month_date' => $vehicleData['cancellation_available_per_month_date'] === '' ? null : $vehicleData['cancellation_available_per_month_date'],
                        'price_per_km_per_day' => $vehicleData['price_per_km_per_day'] === '' ? null : $vehicleData['price_per_km_per_day'],
                        'price_per_km_per_week' => $vehicleData['price_per_km_per_week'] === '' ? null : $vehicleData['price_per_km_per_week'],
                        'price_per_km_per_month' => $vehicleData['price_per_km_per_month'] === '' ? null : $vehicleData['price_per_km_per_month'],
                        'minimum_driver_age' => $vehicleData['minimum_driver_age'] === '' ? null : $vehicleData['minimum_driver_age'],
                    ]);

                    VehicleSpecification::create([
                        'vehicle_id' => $vehicle->id,
                        'registration_number' => $vehicleData['registration_number'],
                        'registration_country' => $vehicleData['registration_country'],
                        'registration_date' => $vehicleData['registration_date'],
                        'gross_vehicle_mass' => $vehicleData['gross_vehicle_mass'] === '' ? null : $vehicleData['gross_vehicle_mass'],
                        'vehicle_height' => $vehicleData['vehicle_height'] === '' ? null : $vehicleData['vehicle_height'],
                        'dealer_cost' => $vehicleData['dealer_cost'] === '' ? null : $vehicleData['dealer_cost'],
                        'phone_number' => $vehicleData['phone_number'],
                    ]);

                    // Note: Handling images, plans, and addons from CSV requires more complex logic.
                    // For images, you might need to provide URLs in the CSV and then download/process them.
                    // For plans/addons, you'd need to parse IDs and potentially prices/quantities from CSV columns.
                    
                    // Handle Vehicle Images from URLs
                    if (!empty($vehicleData['image_urls'])) {
                        $imageUrls = explode(',', $vehicleData['image_urls']);
                        foreach ($imageUrls as $index => $url) {
                            $url = trim($url);
                            if (filter_var($url, FILTER_VALIDATE_URL)) {
                                VehicleImage::create([
                                    'vehicle_id' => $vehicle->id,
                                    'image_path' => $url, // Assuming URL is the path for now, or download and store
                                    'image_url' => $url,
                                    'image_type' => ($index === 0) ? 'primary' : 'gallery',
                                ]);
                            }
                        }
                    }

                    // Handle Selected Plans from JSON
                    if (!empty($vehicleData['selected_plans_json'])) {
                        $selectedPlans = json_decode($vehicleData['selected_plans_json'], true);
                        if (is_array($selectedPlans)) {
                            foreach ($selectedPlans as $selectedPlanData) {
                                if (isset($selectedPlanData['id'])) {
                                    VendorVehiclePlan::create([
                                        'vendor_id' => $user->id,
                                        'vehicle_id' => $vehicle->id,
                                        'plan_id' => $selectedPlanData['id'],
                                        'plan_type' => $selectedPlanData['plan_type'] ?? 'Default Plan Type',
                                        'price' => $selectedPlanData['price'] ?? 0,
                                        'features' => isset($selectedPlanData['features']) ? json_encode($selectedPlanData['features']) : null,
                                        'plan_description' => $selectedPlanData['plan_description'] ?? null,
                                    ]);
                                }
                            }
                        }
                    }

                    // Handle Selected Addons from JSON
                    if (!empty($vehicleData['selected_addons_json'])) {
                        $selectedAddons = json_decode($vehicleData['selected_addons_json'], true);
                        if (is_array($selectedAddons)) {
                            foreach ($selectedAddons as $selectedAddonData) {
                                if (isset($selectedAddonData['id'])) {
                                    $addon = BookingAddon::find($selectedAddonData['id']);
                                    if ($addon) {
                                        VendorVehicleAddon::create([
                                            'vendor_id' => $user->id,
                                            'vehicle_id' => $vehicle->id,
                                            'addon_id' => $selectedAddonData['id'],
                                            'extra_type' => $addon->extra_type,
                                            'extra_name' => $addon->extra_name,
                                            'price' => $selectedAddonData['price'] ?? $addon->price,
                                            'quantity' => $selectedAddonData['quantity'] ?? 1,
                                            'description' => $addon->description,
                                        ]);
                                    }
                                }
                            }
                        }
                    }

                    ActivityLogHelper::logActivity('create_bulk', 'Created a new vehicle via bulk upload', $vehicle, $request);

                    // Notify admin
                    $adminEmail = env('VITE_ADMIN_EMAIL', 'default@admin.com');
                    $admin = User::where('email', $adminEmail)->first();
                    if ($admin) {
                        $admin->notify(new VehicleCreatedNotification($vehicle));
                    }

                    // Notify vendor
                    Notification::route('mail', $user->email)
                        ->notify(new VendorVehicleCreateNotification($vehicle, $user));

                    $vendorProfile = VendorProfile::where('user_id', $user->id)->first();
                    if ($vendorProfile && $vendorProfile->company_email) {
                        Notification::route('mail', $vendorProfile->company_email)
                            ->notify(new VendorVehicleCreateCompanyNotification($vehicle, $user));
                    }
                    $createdCount++;
                });
            } catch (\Exception $e) {
                Log::error("Error processing vehicle row " . ($index + 1) . ": " . $e->getMessage());
                $errorMessages[] = "Row " . ($index + 1) . ": Failed to create vehicle. " . $e->getMessage();
            }
        }

        $message = $createdCount . " vehicles imported successfully.";
        if (!empty($errorMessages)) {
            $message .= " However, some rows had errors: <br>" . implode('<br>', $errorMessages);
            return redirect()->back()
                ->with('message', $message)
                ->with('type', 'warning')
                ->withErrors($errorMessages);
        }

        return redirect('/current-vendor-vehicles')->with([
            'message' => $message,
            'type' => 'success'
        ]);
    }

    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="vehicle_bulk_upload_template.csv"',
        ];

        $columns = [
            'category_id', 'brand', 'model', 'color', 'mileage', 'transmission', 'fuel',
            'seating_capacity', 'number_of_doors', 'luggage_capacity', 'horsepower', 'co2',
            'location', 'latitude', 'longitude', 'city', 'state', 'country', 'status',
            'features', 'featured', 'security_deposit', 'payment_method',
            'guidelines', 'price_per_day', 'price_per_week', 'weekly_discount',
            'price_per_month', 'monthly_discount', 'preferred_price_type',
            'limited_km', 'cancellation_available', 'price_per_km',
            'registration_number', 'registration_country', 'registration_date',
            'gross_vehicle_mass', 'vehicle_height', 'dealer_cost', 'phone_number',
            'limited_km_per_day', 'limited_km_per_week', 'limited_km_per_month',
            'limited_km_per_day_range', 'limited_km_per_week_range', 'limited_km_per_month_range',
            'cancellation_available_per_day', 'cancellation_available_per_week', 'cancellation_available_per_month',
            'cancellation_available_per_day_date', 'cancellation_available_per_week_date', 'cancellation_available_per_month_date',
            'price_per_km_per_day', 'price_per_km_per_week', 'price_per_km_per_month',
            'minimum_driver_age',
            'pickup_times', 'return_times',
            'image_urls', 'selected_plans_json', 'selected_addons_json', // New columns for template
        ];

        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            // Add an example data row
            $exampleRow = [
                '1', // category_id (Example: assuming 1 is a valid category ID)
                'Honda', // brand
                'Civic', // model
                'Red', // color
                '15000', // mileage
                'automatic', // transmission
                'petrol', // fuel
                '5', // seating_capacity
                '4', // number_of_doors
                '2', // luggage_capacity
                '140', // horsepower
                '120g/km', // co2
                '123 Example St, Sample City', // location
                '34.052235', // latitude
                '-118.243683', // longitude
                'Sample City', // city
                'CA', // state
                'US', // country
                'available', // status
                'Air Conditioning,GPS,Bluetooth', // features (comma-separated)
                '1', // featured (boolean: 1 for true, 0 for false)
                '200', // security_deposit
                'credit_card,cash', // payment_method (comma-separated)
                'No smoking. Pets allowed with approval.', // guidelines
                '50', // price_per_day
                '300', // price_per_week
                '10', // weekly_discount
                '1000', // price_per_month
                '15', // monthly_discount
                'day', // preferred_price_type
                '1', // limited_km (boolean: 1 for true, 0 for false)
                '1', // cancellation_available (boolean: 1 for true, 0 for false)
                '0.25', // price_per_km
                'XYZ123', // registration_number
                'US', // registration_country
                '2022-01-15', // registration_date (Y-m-d format)
                '1500', // gross_vehicle_mass
                '2', // vehicle_height (integer)
                '18000', // dealer_cost
                '+15551234567', // phone_number
                '1', // limited_km_per_day (boolean: 1 for true, 0 for false)
                '0', // limited_km_per_week (boolean: 1 for true, 0 for false)
                '0', // limited_km_per_month (boolean: 1 for true, 0 for false)
                '200', // limited_km_per_day_range
                '', // limited_km_per_week_range
                '', // limited_km_per_month_range
                '1', // cancellation_available_per_day (boolean: 1 for true, 0 for false)
                '0', // cancellation_available_per_week (boolean: 1 for true, 0 for false)
                '0', // cancellation_available_per_month (boolean: 1 for true, 0 for false)
                '3', // cancellation_available_per_day_date (days)
                '', // cancellation_available_per_week_date
                '', // cancellation_available_per_month_date
                '0.30', // price_per_km_per_day
                '', // price_per_km_per_week
                '', // price_per_km_per_month
                '21', // minimum_driver_age
                '09:00,10:00,14:00', // pickup_times (comma-separated HH:MM)
                '17:00,18:00,19:00', // return_times (comma-separated HH:MM)
                'https://example.com/image1.jpg,https://example.com/image2.png', // image_urls
                '[{"id":1,"plan_type":"Standard","price":50,"features":["Feature A","Feature B"],"description":"Standard plan details"},{"id":2,"plan_type":"Premium","price":100,"features":["Feature C","Feature D"],"description":"Premium plan details"}]', // selected_plans_json
                '[{"id":1,"price":10,"quantity":1},{"id":2,"price":15,"quantity":2}]' // selected_addons_json
            ];
            fputcsv($file, $exampleRow);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
