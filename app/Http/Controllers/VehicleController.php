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
use App\Models\VendorVehicleAddon;
use App\Models\VendorVehiclePlan;
use App\Notifications\VehicleCreatedNotification;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

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
            'limited_km_per_day_range' => 'nullable|integer',
            'limited_km_per_week_range' => 'nullable|integer',
            'limited_km_per_month_range' => 'nullable|integer|min:0',
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

        ]);

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
        $primaryImageUploaded = false;
        foreach ($request->file('images') as $index => $image) {
            // Store image on UpCloud storage
            $folderName = 'vehicle_images';
            $path = $image->store($folderName, 'upcloud');
            $url = Storage::disk('upcloud')->url($path);

            // Determine image type
            $imageType = ($index === 0) ? 'primary' : 'gallery';

            // Create vehicle image record - store the full URL
            VehicleImage::create([
                'vehicle_id' => $vehicle->id,
                'image_path' => $path, // Store the path
                'image_url' => $url,   // Store the full URL
                'image_type' => $imageType,
            ]);
        }


        ActivityLogHelper::logActivity('create', 'Created a new vehicle', $vehicle, $request);


        // Notify the admin
        // $admin = User::where('email', 'anshul@fanaticcoders.com')->first(); // Replace with your admin email
        // if ($admin) {
        //     $admin->notify(new VehicleCreatedNotification($vehicle));
        // }


        return redirect('/current-vendor-vehicles')->with([
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
        $features = VehicleFeature::all();
        return response()->json($features);
    }

    //This is for getting particular vehicle information to the single car page 
    public function show($id, Request $request)
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
                    'pickup_date' => $booking->pickup_date->format('Y-m-d'),
                    'return_date' => $booking->return_date->format('Y-m-d'),
                ];
            }),
            'blocked_dates' => $vehicle->blockings->map(function ($blocking) {
                return [
                    'blocking_start_date' => $blocking->blocking_start_date->format('Y-m-d'),
                    'blocking_end_date' => $blocking->blocking_end_date->format('Y-m-d'),
                ];
            })
        ]);
    }

    //This is for getting particular vehicle information to the booking page 
    public function booking(Request $request, $id)
    {
        $vehicle = Vehicle::with(['specifications', 'images', 'category', 'user', 'vendorProfile', 'benefits', 'vendorPlans', 'addons', 'vendorProfileData'])
            ->findOrFail($id);

        return Inertia::render('Booking', [
            'vehicle' => $vehicle,
            'plans' => $vehicle->vendorPlans,
            'addons' => $vehicle->addons,
            'query' => $request->all(),
        ]);
    }



    //     public function vendorVehicle()
// {
//     // Get the currently authenticated vendor's ID
//     $vendorId = auth()->id();

    //     // Get all vehicles belonging to this vendor
//     $vehicles = Vehicle::with(['specifications', 'images', 'category', 'user'])
//         ->where('vendor_id', $vendorId)
//         ->get();

    //     return Inertia::render('Vendor/VendorVehicles', [
//         'vehicles' => $vehicles,
//     ]);
// }
}