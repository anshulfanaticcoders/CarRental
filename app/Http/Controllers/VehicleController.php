<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogHelper;
use App\Models\BookingAddon;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBenefit;
use App\Models\VehicleCategory;
use App\Models\VehicleFeature;
use App\Models\VehicleImage;
use App\Models\VehicleSpecification;
use App\Models\VendorProfile;
use App\Models\VehicleOperatingHour;
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
use App\Services\LocationSearchService;
use App\Services\Affiliate\AffiliateQrCodeService;
use App\Services\Vehicles\SippCodeSuggestionService;
use App\Services\Vehicles\VendorLocationSyncService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VehicleController extends Controller
{

    protected $locationSearchService;
    protected $vendorLocationSyncService;
    protected $sippCodeSuggestionService;

    public function __construct(
        LocationSearchService $locationSearchService,
        VendorLocationSyncService $vendorLocationSyncService,
        SippCodeSuggestionService $sippCodeSuggestionService
    )
    {
        $this->locationSearchService = $locationSearchService;
        $this->vendorLocationSyncService = $vendorLocationSyncService;
        $this->sippCodeSuggestionService = $sippCodeSuggestionService;
    }

    
    public function create()
    {
        $categories = DB::table('vehicle_categories')->select('id', 'name')->get();
        $vendorLocations = $this->vendorLocationsForUser(auth()->id());

        return Inertia::render('Auth/VehicleListingNew', [
            'categories' => $categories,
            'vendorLocations' => $vendorLocations,
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
            'mileage' => 'required|integer|min:0',
            'transmission' => 'required|string',
            'fuel' => 'required|string',
            'body_style' => 'required|string|max:50',
            'air_conditioning' => 'required|boolean',
            'seating_capacity' => 'required|integer|min:1',
            'number_of_doors' => 'required|integer|min:2',
            'luggage_capacity' => 'required|integer|min:0',
            'horsepower' => 'required|integer|min:0',
            'co2' => 'required|string',
            'vendor_location_id' => [
                'required',
                Rule::exists('vendor_locations', 'id')->where('vendor_id', $request->user()->id),
            ],
            'location' => 'nullable|string',
            'location_type' => 'nullable|string|max:255',
            'iata_code' => 'nullable|string|size:3',
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
            'terms_policy' => 'nullable|string|max:50000',
            'rental_policy' => 'nullable|string|max:100000',
            'fuel_policy' => 'nullable|in:full_to_full,same_to_same,pre_purchase',
            'pickup_instructions' => 'nullable|string|max:2000',
            'dropoff_instructions' => 'nullable|string|max:2000',
            'location_phone' => 'nullable|string|max:50',
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
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',

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
            'cancellation_fee_per_day' => 'nullable|numeric|min:0',
            'cancellation_available_per_week_date' => 'nullable|integer',
            'cancellation_available_per_month_date' => 'nullable|integer',
            'price_per_km_per_day' => 'nullable|decimal:0,2',
            'price_per_km_per_week' => 'nullable|decimal:0,2',
            'price_per_km_per_month' => 'nullable|decimal:0,2',
            'minimum_driver_age' => 'nullable|integer',

            'pickup_times' => 'nullable|array',
            'return_times' => 'nullable|array',

            'operating_hours' => 'required|array|size:7',
            'operating_hours.*.day' => 'required|integer|between:0,6',
            'operating_hours.*.is_open' => 'required|boolean',
            'operating_hours.*.open_time' => 'required_if:operating_hours.*.is_open,true|nullable|date_format:H:i',
            'operating_hours.*.close_time' => 'required_if:operating_hours.*.is_open,true|nullable|date_format:H:i',
            'primary_image_index' => 'required|numeric|min:0',
            'images' => 'required|array|min:5|max:20',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:10240',

            'selected_plans' => 'nullable|array|max:3',
            'selected_plans.*.plan_type' => 'required|string|in:Basic,Essential,Premium,Premium Plus',
            'selected_plans.*.plan_value' => 'required|numeric|min:0',
            'selected_plans.*.features' => 'nullable|array|max:5',
            'selected_plans.*.features.*' => 'string|max:255',
            'selected_plans.*.plan_description' => 'nullable|string|max:2000',

            'custom_addons' => 'nullable|array',
            'custom_addons.*.extra_name' => 'required|string|max:255',
            'custom_addons.*.extra_type' => 'nullable|string|max:255',
            'custom_addons.*.description' => 'nullable|string|max:2000',
            'custom_addons.*.price' => 'required|numeric|min:0',
            'custom_addons.*.quantity' => 'required|integer|min:1',

        ]);

        $selectedPlans = $request->input('selected_plans', []);
        if (!empty($selectedPlans)) {
            $pricePerDay = $request->price_per_day ?? 0;
            foreach ($selectedPlans as $selectedPlan) {
                $planType = $selectedPlan['plan_type'] ?? '';
                if ($planType === 'Basic') {
                    continue;
                }

                $planValue = $selectedPlan['plan_value'] ?? null;
                if (!is_numeric($planValue) || $planValue < $pricePerDay) {
                    return back()->withErrors([
                        'selected_plans' => 'Protection plan price must be at least the daily price.'
                    ])->withInput();
                }
            }
        }

        if ($request->hasFile('images') && $request->primary_image_index >= count($request->file('images'))) {
            return back()->withErrors(['primary_image_index' => 'Primary image index is out of bounds.'])->withInput();
        }

        try {
            DB::beginTransaction();

            $vendorLocation = $this->vendorLocationSyncService->resolveSelectedLocation(
                $request->user()->id,
                (int) $request->input('vendor_location_id')
            );

            if (!$vendorLocation) {
                throw ValidationException::withMessages([
                    'vendor_location_id' => ['Please select a valid vendor location.'],
                ]);
            }

            $vehicleLocationAttributes = $this->vendorLocationSyncService->vehicleLocationAttributes($vendorLocation);
            $resolvedSippCode = $this->sippCodeSuggestionService->suggest([
                'category_name' => VehicleCategory::query()->find($request->input('category_id'))?->name,
                'body_style' => $request->input('body_style'),
                'seating_capacity' => $request->input('seating_capacity'),
                'horsepower' => $request->input('horsepower'),
                'transmission' => $request->input('transmission'),
                'fuel' => $request->input('fuel'),
                'air_conditioning' => $request->input('air_conditioning'),
            ]);

            // Create the vehicle
            $vehicle = Vehicle::create([
                'vendor_id' => $request->user()->id,
                'vendor_location_id' => $vendorLocation->id,
                'category_id' => $request->category_id,
                'brand' => $request->brand,
                'model' => $request->model,
                'color' => $request->color,
                'mileage' => $request->mileage,
                'transmission' => $request->transmission,
                'fuel' => $request->fuel,
                'body_style' => $request->body_style,
                'air_conditioning' => $request->boolean('air_conditioning'),
                'sipp_code' => $resolvedSippCode,
                'seating_capacity' => $request->seating_capacity,
                'number_of_doors' => $request->number_of_doors,
                'luggage_capacity' => $request->luggage_capacity,
                'horsepower' => $request->horsepower,
                'co2' => $request->co2,
                'location' => $vehicleLocationAttributes['location'],
                'location_type' => $vehicleLocationAttributes['location_type'],
                'latitude' => $vehicleLocationAttributes['latitude'],
                'longitude' => $vehicleLocationAttributes['longitude'],
                'city' => $vehicleLocationAttributes['city'],
                'state' => $vehicleLocationAttributes['state'],
                'country' => $vehicleLocationAttributes['country'],
                'full_vehicle_address' => $vehicleLocationAttributes['full_vehicle_address'],
                'status' => $request->status,
                'features' => json_encode($request->features),
                'featured' => $request->featured,
                'security_deposit' => $request->security_deposit,
                // 'payment_method' => $request->payment_method,
                'payment_method' => json_encode($request->payment_method),
                'guidelines' => $request->guidelines,
                'terms_policy' => $request->terms_policy,
                'rental_policy' => $request->rental_policy,
                'fuel_policy' => $request->fuel_policy ?? 'full_to_full',
                'pickup_instructions' => $vehicleLocationAttributes['pickup_instructions'],
                'dropoff_instructions' => $vehicleLocationAttributes['dropoff_instructions'],
                'location_phone' => $vehicleLocationAttributes['location_phone'],
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
                'cancellation_fee_per_day' => $request->cancellation_fee_per_day,
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

            // Save operating hours (7 days)
            foreach ($request->input('operating_hours', []) as $hours) {
                VehicleOperatingHour::create([
                    'vehicle_id' => $vehicle->id,
                    'day_of_week' => $hours['day'],
                    'is_open' => $hours['is_open'],
                    'open_time' => $hours['is_open'] ? $hours['open_time'] : null,
                    'close_time' => $hours['is_open'] ? $hours['close_time'] : null,
                ]);
            }


            // Save the selected plan details
            if (!empty($selectedPlans)) {
                $planId = 1;

                foreach ($selectedPlans as $selectedPlan) {
                    $features = isset($selectedPlan['features']) && is_array($selectedPlan['features'])
                        ? array_values(array_filter($selectedPlan['features'], fn($feature) => trim($feature) !== ''))
                        : null;

                    $planType = $selectedPlan['plan_type'] ?? 'Basic';
                    $planValue = $planType === 'Basic'
                        ? ($request->price_per_day ?? 0)
                        : ($selectedPlan['plan_value'] ?? 0);

                    VendorVehiclePlan::create([
                        'vendor_id' => $request->user()->id,
                        'vehicle_id' => $vehicle->id,
                        'plan_id' => $planId,
                        'plan_type' => $planType,
                        'price' => $planValue,
                        'features' => $features ? json_encode(array_slice($features, 0, 5)) : null,
                        'plan_description' => $selectedPlan['plan_description'] ?? null,
                    ]);

                    $planId++;
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

            $customAddons = $request->input('custom_addons', []);
            if (!empty($customAddons)) {
                foreach ($customAddons as $customAddon) {
                    $extraName = trim($customAddon['extra_name'] ?? '');
                    if ($extraName === '') {
                        continue;
                    }
                    $extraType = trim($customAddon['extra_type'] ?? '') ?: 'custom';
                    $description = trim($customAddon['description'] ?? '');
                    $price = $customAddon['price'] ?? 0;
                    $quantity = $customAddon['quantity'] ?? 1;

                    $addon = BookingAddon::updateOrCreate(
                        [
                            'vendor_id' => $request->user()->id,
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
                        'vendor_id' => $request->user()->id,
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
            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            if ($exception instanceof ValidationException) {
                throw $exception;
            }
            \Log::error('Vehicle creation failed', [
                'vendor_id' => $request->user()->id,
                'message' => $exception->getMessage(),
            ]);
            return back()->withErrors([
                'vehicle' => 'We could not save this vehicle. Please review the form and try again.',
            ])->withInput();
        }


        // Notify the admin
        try {
            $adminEmail = env('VITE_ADMIN_EMAIL', 'default@admin.com');
            $admin = User::where('email', $adminEmail)->first();
            if ($admin) {
                $admin->notify(new VehicleCreatedNotification($vehicle));
            }
        } catch (\Exception $e) {
            // Log the error but don't expose it to the user
            \Log::error('Failed to send admin notification for vehicle creation: ' . $e->getMessage());
        }

        // Notify the vendor
        try {
            $request->user()->notify(new VendorVehicleCreateNotification($vehicle, $request->user()));
        } catch (\Exception $e) {
            // Log the error but don't expose it to the user
            \Log::error('Failed to send vendor notification for vehicle creation: ' . $e->getMessage());
        }

        // Notify the company (find user for DB storage)
        try {
            $vendorProfile = VendorProfile::where('user_id', $request->user()->id)->first();
            if ($vendorProfile && $vendorProfile->company_email) {
                $companyUser = User::where('email', $vendorProfile->company_email)->first();
                if ($companyUser) {
                    $companyUser->notify(new VendorVehicleCreateCompanyNotification($vehicle, $request->user()));
                } else {
                    Notification::route('mail', $vendorProfile->company_email)
                        ->notify(new VendorVehicleCreateCompanyNotification($vehicle, $request->user()));
                }
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send company notification for vehicle creation: ' . $e->getMessage());
        }


        return redirect()->route('current-vendor-vehicles.index', ['locale' => app()->getLocale()])->with([
            'message' => 'Vehicle added successfully!',
            'type' => 'success'
        ]);
    }

    private function vendorLocationsForUser(?int $vendorId)
    {
        if (!$vendorId) {
            return [];
        }

        return User::query()
            ->find($vendorId)
            ?->vendorLocations()
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'address_line_1',
                'city',
                'state',
                'country',
                'country_code',
                'latitude',
                'longitude',
                'location_type',
                'iata_code',
                'phone',
                'pickup_instructions',
                'dropoff_instructions',
            ])
            ->map(fn ($location) => [
                'id' => $location->id,
                'name' => $location->name,
                'address_line_1' => $location->address_line_1,
                'city' => $location->city,
                'state' => $location->state,
                'country' => $location->country,
                'country_code' => $location->country_code,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'location_type' => $location->location_type,
                'iata_code' => $location->iata_code,
                'phone' => $location->phone,
                'pickup_instructions' => $location->pickup_instructions,
                'dropoff_instructions' => $location->dropoff_instructions,
                'display_name' => trim(implode(' • ', array_filter([
                    $location->name,
                    $location->city,
                    $location->country,
                ]))),
            ])
            ->values()
            ->all() ?? [];
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

    public function getCategories()
    {
        $categories = VehicleCategory::query()
            ->select('id', 'name', 'slug', 'status')
            ->where('status', true)
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    }

    public function showAllVendorVehicles()
    {
        $vehicles = Vehicle::query()
            ->with(['category', 'vendorProfileData'])
            ->latest()
            ->get();

        return response()->json($vehicles);
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

        // Get affiliate data if available
        $affiliateService = new AffiliateQrCodeService();
        $affiliateData = $affiliateService->getAffiliateSessionData();

        return Inertia::render('SingleCar', [
            'vehicle' => $vehicle,
            'affiliate_data' => $affiliateData, // Pass affiliate data to the view
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
            'filters' => [
                'currency' => $request->query('currency', 'USD'),
            ],
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

        // Get affiliate data if available
        $affiliateService = new AffiliateQrCodeService();
        $affiliateData = $affiliateService->getAffiliateSessionData();

        return Inertia::render('Booking', [
            'vehicle' => $vehicle,
            'plans' => $vehicle->vendorPlans,
            'addons' => $vehicle->addons,
            'affiliate_data' => $affiliateData, // Pass affiliate data to booking view
            'query' => $request->all(),
            'filters' => [
                'currency' => $request->query('currency', 'USD'),
            ],
        ]);
    }


    public function searchLocations(Request $request)
    {
        $query = trim($request->input('text'));
        $results = $this->locationSearchService->searchLocations($query);
        return response()->json(['results' => $results]);
    }

}
