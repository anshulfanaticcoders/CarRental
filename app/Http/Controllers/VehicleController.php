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
use App\Helpers\ImageCompressionHelper;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Support\Facades\Session; 
use Illuminate\Support\Facades\Redirect; 
use App\Helpers\SchemaBuilder; // Import SchemaBuilder

class VehicleController extends Controller
{
    public function create()
    {
        $categories = DB::table('vehicle_categories')->select('id', 'name')->get();

        return Inertia::render('Auth/VehicleListing', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {

        // print_r($request->all());
        // die();
        // Validate incoming request data
        $request->validate([
            'category_id' => 'required|exists:vehicle_categories,id',
            'brand' => 'required|string|max:50',
            'model' => 'required|string|max:50',
            'color' => 'required|string|max:30',
            'mileage' => 'required|decimal:0,2',
            'transmission' => 'required|string',
            'fuel' => 'required|string',
            'seating_capacity' => 'required|integer|min:1',
            'number_of_doors' => 'required|integer|min:2',
            'luggage_capacity' => 'required|integer|min:0',
            'horsepower' => 'required|decimal:0,2',
            'co2' => 'required|string',
            'location' => 'required|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'full_vehicle_address' => 'nullable|string|max:255',
            'status' => 'required|in:available,rented,maintenance',
            'features' => 'array',
            'featured' => 'boolean',
            'security_deposit' => 'required|decimal:0,2',
            // 'payment_method' => 'required|string',
            'payment_method' => 'required|array',
            'payment_method.*' => 'string|in:credit_card,cheque,bank_wire,cryptocurrency,cash',
            'guidelines' => 'nullable|string|max:50000',
            'price_per_day' => 'nullable|decimal:0,2',
            'price_per_week' => 'nullable|decimal:0,2',
            'weekly_discount' => 'nullable|decimal:0,2',
            'price_per_month' => 'nullable|decimal:0,2',
            'monthly_discount' => 'nullable|decimal:0,2',
            'preferred_price_type' => 'required|in:day,week,month',
            'limited_km' => 'boolean',
            'cancellation_available' => 'boolean',
            'price_per_km' => 'nullable|decimal:0,2',

            'registration_number' => 'required|string|max:50',
            'registration_country' => 'required|string|max:50',
            'registration_date' => 'required|date',
            // 'gross_vehicle_mass' => 'required|integer|min:0',
            // 'vehicle_height' => 'required|integer|min:0',
            // 'dealer_cost' => 'required|decimal:0,2|min:0',
            'phone_number' => 'required|string|max:15',

            // New Vehicle Benefit fields
            'limited_km_per_day' => 'boolean',
            'limited_km_per_week' => 'boolean',
            'limited_km_per_month' => 'boolean',
            'limited_km_per_day_range' => 'nullable|decimal:0,2',
            'limited_km_per_week_range' => 'nullable|decimal:0,2',
            'limited_km_per_month_range' => 'nullable|decimal:0,2',
            'cancellation_available_per_day' => 'boolean',
            'cancellation_available_per_week' => 'boolean',
            'cancellation_available_per_month' => 'boolean',
            'cancellation_available_per_day_date' => 'nullable|integer',
            'cancellation_available_per_week_date' => 'nullable|integer',
            'cancellation_available_per_month_date' => 'nullable|integer',
            'price_per_km_per_day' => 'nullable|decimal:0,2',
            'price_per_km_per_week' => 'nullable|decimal:0,2',
            'price_per_km_per_month' => 'nullable|decimal:0,2',
            'minimum_driver_age' => 'nullable|integer',

            'pickup_times' => 'required|array',
            'return_times' => 'required|array',
            'primary_image_index' => 'required|numeric|min:0',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:10240',

        ]);

        if ($request->hasFile('images') && $request->primary_image_index >= count($request->file('images'))) {
            return back()->withErrors(['primary_image_index' => 'Primary image index is out of bounds.'])->withInput();
        }

        // Create the vehicle
        $vehicle = Vehicle::create([
            'vendor_id' => $request->user()->id,
            'category_id' => $request->category_id,
            'brand' => $request->brand,
            'model' => $request->model,
            'color' => $request->color,
            'mileage' => $request->mileage,
            'transmission' => $request->transmission,
            'fuel' => $request->fuel,
            'seating_capacity' => $request->seating_capacity,
            'number_of_doors' => $request->number_of_doors,
            'luggage_capacity' => $request->luggage_capacity,
            'horsepower' => $request->horsepower,
            'co2' => $request->co2,
            'location' => $request->location,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'city' => $request->city,
            'state' => $request->state,
            'country' => $request->country,
            'full_vehicle_address' => $request->full_vehicle_address,
            'status' => $request->status,
            'features' => json_encode($request->features),
            'featured' => $request->featured,
            'security_deposit' => $request->security_deposit,
            // 'payment_method' => $request->payment_method,
            'payment_method' => json_encode($request->payment_method),
            'guidelines' => $request->guidelines,
            'price_per_day' => $request->price_per_day,
            'price_per_week' => $request->price_per_week,
            'weekly_discount' => $request->weekly_discount,
            'price_per_month' => $request->price_per_month,
            'monthly_discount' => $request->monthly_discount,
            'preferred_price_type' => $request->preferred_price_type,
            'limited_km' => $request->limited_km ?? false,
            'cancellation_available' => $request->cancellation_available ?? false,
            'price_per_km' => $request->price_per_km,

            'pickup_times' => $request->pickup_times,
            'return_times' => $request->return_times,
        ]);


        VehicleBenefit::create([
            'vehicle_id' => $vehicle->id,
            'limited_km_per_day' => $request->limited_km_per_day ?? false,
            'limited_km_per_week' => $request->limited_km_per_week ?? false,
            'limited_km_per_month' => $request->limited_km_per_month ?? false,
            'limited_km_per_day_range' => $request->limited_km_per_day_range,
            'limited_km_per_week_range' => $request->limited_km_per_week_range,
            'limited_km_per_month_range' => $request->limited_km_per_month_range,
            'cancellation_available_per_day' => $request->cancellation_available_per_day ?? false,
            'cancellation_available_per_week' => $request->cancellation_available_per_week ?? false,
            'cancellation_available_per_month' => $request->cancellation_available_per_month ?? false,
            'cancellation_available_per_day_date' => $request->cancellation_available_per_day_date,
            'cancellation_available_per_week_date' => $request->cancellation_available_per_week_date,
            'cancellation_available_per_month_date' => $request->cancellation_available_per_month_date,
            'price_per_km_per_day' => $request->price_per_km_per_day,
            'price_per_km_per_week' => $request->price_per_km_per_week,
            'price_per_km_per_month' => $request->price_per_km_per_month,
            'minimum_driver_age' => $request->minimum_driver_age,
        ]);

        // Create the vehicle specifications
        VehicleSpecification::create([
            'vehicle_id' => $vehicle->id,
            'registration_number' => $request->registration_number,
            'registration_country' => $request->registration_country,
            'registration_date' => $request->registration_date,
            'gross_vehicle_mass' => $request->gross_vehicle_mass,
            'vehicle_height' => $request->vehicle_height,
            'dealer_cost' => $request->dealer_cost,
            'phone_number' => $request->phone_number,
        ]);


        // Save the selected plan details
        if ($request->has('selected_plans')) {
            $selectedPlans = $request->input('selected_plans');

            foreach ($selectedPlans as $selectedPlan) {
                VendorVehiclePlan::create([
                    'vendor_id' => $request->user()->id,
                    'vehicle_id' => $vehicle->id,
                    'plan_id' => $selectedPlan['id'],
                    'plan_type' => $selectedPlan['plan_type'],
                    'price' => $selectedPlan['plan_value'],
                    'features' => isset($selectedPlan['features']) ? json_encode($selectedPlan['features']) : null,
                    'plan_description' => isset($selectedPlan['plan_description']) ? $selectedPlan['plan_description'] : null,
                ]);
            }
        }

        // Save the selected addon details
        if ($request->has('selected_addons')) {
            $selectedAddons = $request->input('selected_addons');
            $addonPrices = $request->input('addon_prices');
            $addonQuantities = $request->input('addon_quantities');

            foreach ($selectedAddons as $addonId) {
                $addon = BookingAddon::find($addonId); // Fetch the addon details

                VendorVehicleAddon::create([
                    'vendor_id' => $request->user()->id,
                    'vehicle_id' => $vehicle->id,
                    'addon_id' => $addonId,
                    'extra_type' => $addon->extra_type, // Add extra_type
                    'extra_name' => $addon->extra_name, // Add extra_name
                    'price' => $addonPrices[$addonId],
                    'quantity' => $addonQuantities[$addonId],
                    'description' => $addon->description, // Add description
                ]);
            }
        }

        // Handle vehicle images
        if ($request->hasFile('images')) {
            $primaryImageIndex = (int)$request->primary_image_index;
            $folderName = 'vehicle_images';

            foreach ($request->file('images') as $index => $image) {
                $compressedImageUrl = ImageCompressionHelper::compressImage(
                    $image,
                    $folderName,
                    quality: 80, // Adjust quality as needed
                    maxWidth: 1200, // Optional: Set max width for vehicle images
                    maxHeight: 900 // Optional: Set max height for vehicle images
                );

                if ($compressedImageUrl) {
                    // Determine image type
                    $imageType = ($index === $primaryImageIndex) ? 'primary' : 'gallery';

                    // Get the full URL for the stored image
                    $fullImageUrl = Storage::disk('upcloud')->url($compressedImageUrl);

                    // Create vehicle image record - store the relative path in image_path and full URL in image_url
                    VehicleImage::create([
                        'vehicle_id' => $vehicle->id,
                        'image_path' => $compressedImageUrl, // Store the relative path
                        'image_url' => $fullImageUrl,        // Store the full URL
                        'image_type' => $imageType,
                    ]);
                } else {
                    // Handle compression failure for a specific image
                    // You might want to log this or return an error,
                    // but for now, we'll just skip this image.
                    // For a more robust solution, consider collecting errors and returning them.
                    ActivityLogHelper::logActivity('error', 'Failed to compress vehicle image: ' . $image->getClientOriginalName(), $vehicle, $request);
                }
            }
        }


        ActivityLogHelper::logActivity('create', 'Created a new vehicle', $vehicle, $request);


        // Notify the admin
        $adminEmail = env('VITE_ADMIN_EMAIL', 'default@admin.com');
        $admin = User::where('email', $adminEmail)->first();
        if ($admin) {
            $admin->notify(new VehicleCreatedNotification($vehicle));
        }

        // Notify the vendor
        $request->user()->notify(new VendorVehicleCreateNotification($vehicle, $request->user()));

        // Notify the company
        $vendorProfile = VendorProfile::where('user_id', $request->user()->id)->first();
        if ($vendorProfile && $vendorProfile->company_email) {
            Notification::route('mail', $vendorProfile->company_email)
                ->notify(new VendorVehicleCreateCompanyNotification($vehicle, $request->user()));
        }


        return redirect()->route('current-vendor-vehicles.index', ['locale' => app()->getLocale()])->with([
            'message' => 'Vehicle added successfully!',
            'type' => 'success'
        ]);
    }

    public function index()
    {
        $vehicles = Vehicle::with('category', 'user', 'vendorProfile')->get();
        return Inertia::render('Auth/VehicleIndex', [
            'vehicles' => $vehicles,
        ]);
    }

    public function getFeatures()
    {
        $features = VehicleFeature::with('category')->get();
        return response()->json($features);
    }

    //This is for getting particular vehicle information to the single car page
    public function show($locale, $id, Request $request)
    {

        $vehicle = Vehicle::with([
            'specifications',
            'images',
            'category',
            'user',
            'vendorProfile',
            'benefits',
            'vendorProfileData',
            'bookings' => function ($query) {
                $query->select('vehicle_id', 'pickup_date', 'return_date');
            },
            'blockings' => function ($query) {
                $query->select('vehicle_id', 'blocking_start_date', 'blocking_end_date');
            }
        ])->findOrFail($id);

        return Inertia::render('SingleCar', [
            'vehicle' => $vehicle,
            'booked_dates' => $vehicle->bookings->map(function ($booking) {
                return [
                    'pickup_date' => $booking->pickup_date ? $booking->pickup_date->format('Y-m-d') : null,
                    'return_date' => $booking->return_date ? $booking->return_date->format('Y-m-d') : null,
                ];
            }),
            'blocked_dates' => $vehicle->blockings->map(function ($blocking) {
                return [
                    'blocking_start_date' => $blocking->blocking_start_date ? $blocking->blocking_start_date->format('Y-m-d') : null,
                    'blocking_end_date' => $blocking->blocking_end_date ? $blocking->blocking_end_date->format('Y-m-d') : null,
                ];
            }),
            'schema' => SchemaBuilder::singleVehicle($vehicle), // Add vehicle schema
            'appUrl' => env('APP_URL'),
            'locale' => app()->getLocale(),
        ]);
    }

    //This is for getting particular vehicle information to the booking page
    public function booking(Request $request, $locale, $id)
    {
        if (!Session::get('can_access_booking_page')) {
            // If access is not granted, redirect to the single vehicle page
            return Redirect::route('vehicle.show', ['locale' => $locale, 'id' => $id])
                           ->with('error', 'Please initiate the booking process from the vehicle page.');
        }
        
        $vehicle = Vehicle::with(['specifications', 'images', 'category', 'user', 'vendorProfile', 'benefits', 'vendorPlans', 'addons', 'vendorProfileData'])
            ->findOrFail($id);

        return Inertia::render('Booking', [
            'vehicle' => $vehicle,
            'plans' => $vehicle->vendorPlans,
            'addons' => $vehicle->addons,
            'query' => $request->all(),
        ]);
    }


    public function searchLocations(Request $request)
    {
        $query = trim($request->input('text'));

        if (strlen($query) < 3) {
            return response()->json(['results' => []]);
        }

        $normalizedQuery = $this->normalizeString($query);
        $locations = Vehicle::select(
            DB::raw('MIN(location) as location'),
            'city',
            'state',
            'country',
            DB::raw('MIN(latitude) as latitude'),   // Representative latitude
            DB::raw('MIN(longitude) as longitude') // Representative longitude
        )
            ->where(function ($q) use ($normalizedQuery) {
                $q->whereRaw("regexp_replace(LOWER(city), '[^a-z0-9]', '') LIKE ?", ["%{$normalizedQuery}%"])
                    ->orWhereRaw("regexp_replace(LOWER(state), '[^a-z0-9]', '') LIKE ?", ["%{$normalizedQuery}%"])
                    ->orWhereRaw("regexp_replace(LOWER(country), '[^a-z0-9]', '') LIKE ?", ["%{$normalizedQuery}%"]);
            })
            ->whereNotNull('city') // Ensure city, country are present, state can be null
            ->whereNotNull('country')
            ->groupBy('city', 'state', 'country') // Group by the administrative areas
            ->orderByRaw('
                CASE
                    WHEN regexp_replace(LOWER(city), \'[^a-z0-9]\', \'\') = ? THEN 0    -- Exact city match
                    WHEN regexp_replace(LOWER(state), \'[^a-z0-9]\', \'\') = ? THEN 1   -- Exact state match
                    WHEN regexp_replace(LOWER(country), \'[^a-z0-9]\', \'\') = ? THEN 2 -- Exact country match
                    WHEN regexp_replace(LOWER(city), \'[^a-z0-9]\', \'\') LIKE ? THEN 3  -- Partial city match
                    WHEN regexp_replace(LOWER(state), \'[^a-z0-9]\', \'\') LIKE ? THEN 4 -- Partial state match
                    WHEN regexp_replace(LOWER(country), \'[^a-z0-9]\', \'\') LIKE ? THEN 5-- Partial country match
                    ELSE 6
                END', [
                $normalizedQuery,
                $normalizedQuery,
                $normalizedQuery, // Exact matches
                "%{$normalizedQuery}%",
                "%{$normalizedQuery}%",
                "%{$normalizedQuery}%" // Partial matches
            ])
            ->limit(50)
            ->get();

        $results = $locations->map(function ($area) use ($normalizedQuery) {
            // Normalize fields from the aggregated $area result
            $cityLower = $this->normalizeString($area->city ?? '');
            $stateLower = $this->normalizeString($area->state ?? '');
            $countryLower = $this->normalizeString($area->country ?? '');

            $matchedField = null;
            $label = null;
            $belowLabel = null;

            // Determine the matched field and set label/below_label
            // Priority: City > State > Country
            if (str_contains($cityLower, $normalizedQuery) && $area->city) {
                $matchedField = 'city';
                $label = $area->city;
                $belowLabelParts = [];
                if ($area->state)
                    $belowLabelParts[] = $area->state;
                if ($area->country)
                    $belowLabelParts[] = $area->country;
                $belowLabel = implode(', ', $belowLabelParts);
            } elseif (str_contains($stateLower, $normalizedQuery) && $area->state) {
                $matchedField = 'state';
                $label = $area->state;
                $belowLabel = $area->country ?? null;
            } elseif (str_contains($countryLower, $normalizedQuery) && $area->country) {
                $matchedField = 'country';
                $label = $area->country;
                $belowLabel = null;
            }

            if (!$label) {
                return null; // Skip if no valid label could be formed
            }

            // Generate a unique ID for the suggestion item
            // This ID should be based on the distinct combination of label and below_label
            $uniqueIdContent = $this->normalizeString($label) . ($belowLabel ? $this->normalizeString($belowLabel) : '');

            return [
                // Use a hash of the normalized label and below_label for a more stable ID
                'id' => md5($uniqueIdContent),
                'label' => $label,
                'below_label' => $belowLabel ?: null, // Ensure null if empty
                'location' => $area->location,
                'city' => $area->city,
                'state' => $area->state,
                'country' => $area->country,
                'latitude' => $area->latitude,   // Representative lat
                'longitude' => $area->longitude, // Representative lon
                'matched_field' => $matchedField,
            ];
        })->filter() // Remove any null entries (e.g. if label couldn't be formed)
            ->unique(function ($item) {
                // Ensure uniqueness based on the combination of normalized label and below_label
                return $this->normalizeString($item['label']) . ($item['below_label'] ? $this->normalizeString($item['below_label']) : '');
            })->values(); // Re-index the array

        return response()->json(['results' => $results]);
    }

    /**
     * Normalize a string by removing diacritics and converting to lowercase.
     *
     * @param string|null $string
     * @return string
     */
    private function normalizeString($string)
    {
        if (is_null($string)) {
            return '';
        }

        // Convert to lowercase and remove diacritics
        $normalized = strtolower($string);
        $normalized = transliterator_transliterate('NFKD; [:Nonspacing Mark:] Remove; NFC;', $normalized);
        $normalized = preg_replace('/[^a-z0-9]/', '', $normalized); // Keep only alphanumeric

        return $normalized;
    }

}
