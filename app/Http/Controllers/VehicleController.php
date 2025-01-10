<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleFeature;
use App\Models\VehicleImage;
use App\Models\VehicleSpecification;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'number_of_doors' => 'required|integer|min:1',
            'luggage_capacity' => 'required|integer|min:0',
            'horsepower' => 'required|integer|min:0',
            'co2' => 'required|string',
            'location' => 'required|string',
            //  'latitude' => 'required|decimal:10,8', // Add latitude validation
            //  'longitude' => 'required|decimal:11,8',
            'status' => 'required|in:available,rented,maintenance',
            'features' => 'array',
            'featured' => 'boolean',
            'security_deposit' => 'required|decimal:0,2|min:0',
            'payment_method' => 'required|string',
            'price_per_day' => 'required|decimal:0,2|min:0',

            'registration_number' => 'required|string|max:50',
            'registration_country' => 'required|string|max:50',
            'registration_date' => 'required|date',
            'gross_vehicle_mass' => 'required|integer|min:0',
            'vehicle_height' => 'required|integer|min:0',
            'dealer_cost' => 'required|decimal:0,2|min:0',
            'phone_number' => 'required|string|max:15',
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
            'latitude' => $request->latitude, // Save latitude
           'longitude' => $request->longitude,
            'status' => $request->status,
            'features' => json_encode($request->features),
            'featured' => $request->featured,
            'security_deposit' => $request->security_deposit,
            'payment_method' => $request->payment_method,
            'price_per_day' => $request->price_per_day,
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

        // Handle vehicle images
        $primaryImageUploaded = false;
        foreach ($request->file('images') as $index => $image) {
            $imagePath = $image->store('vehicle_images', 'public');

            // Determine image type
            $imageType = ($index === 0) ? 'primary' : 'gallery';

            // Create vehicle image record
            VehicleImage::create([
                'vehicle_id' => $vehicle->id,
                'image_path' => $imagePath,
                'image_type' => $imageType,
            ]);
        }

        return redirect(RouteServiceProvider::HOMEPAGE);
    }

    public function index()
    {
        $vehicles = Vehicle::with('category', 'user')->get();
        return Inertia::render('Auth/VehicleIndex', [
            'vehicles' => $vehicles,
        ]);
    }

    public function getFeatures()
    {
        $features = VehicleFeature::all();
        return response()->json($features);
    }

    public function show($id)
    {
        $vehicle = Vehicle::with(['specifications', 'images', 'category', 'user'])
            ->findOrFail($id);
        return response()->json($vehicle);
    }
    public function showAllVendorVehicles()
{
    $vehicles = Vehicle::with(['specifications', 'images', 'category', 'user'])->get();
    return response()->json($vehicles);
}
}