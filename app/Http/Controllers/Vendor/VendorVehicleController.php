<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use App\Models\VehicleSpecification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class VendorVehicleController extends Controller
{
    public function index(Request $request)
{
    $vendorId = auth()->id();
    $searchQuery = $request->input('search', '');

    $vehicles = Vehicle::with(['specifications', 'images', 'category', 'user', 'vendorProfile'])
        ->where('vendor_id', $vendorId)
        ->when($searchQuery, function ($query, $searchQuery) {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('brand', 'like', '%' . $searchQuery . '%')
                  ->orWhere('model', 'like', '%' . $searchQuery . '%')
                  ->orWhere('transmission', 'like', '%' . $searchQuery . '%')
                  ->orWhere('fuel', 'like', '%' . $searchQuery . '%')
                  ->orWhere('location', 'like', '%' . $searchQuery . '%')
                  ->orWhere('status', 'like', '%' . $searchQuery . '%');
            });
        })
        ->latest()
        ->paginate(6); // Paginate with 6 items per page

    return Inertia::render('Vendor/Vehicles/Index', [
        'vehicles' => $vehicles->items(),  // Get only the vehicle data
        'pagination' => [
            'current_page' => $vehicles->currentPage(),
            'last_page' => $vehicles->lastPage(),
            'per_page' => $vehicles->perPage(),
            'total' => $vehicles->total(),
        ],
        'filters' => $request->all(), // Add the filters to the response
    ]);
}


    public function edit($id)
    {
        $vehicle = Vehicle::with(['specifications', 'images'])->findOrFail($id);

        // Check if the vehicle belongs to the authenticated vendor
        if ($vehicle->vendor_id !== auth()->id()) {
            return redirect()->route('current-vendor-vehicles.index')
                ->with('error', 'You do not have permission to edit this vehicle');
        }

        $features = [
            'Bluetooth',
            'Music System',
            'Toolkit',
            'USB Charger',
            'Key Lock',
            'Back Camera',
            'Voice Control',
            'Navigation'
        ];

        return Inertia::render('Vendor/Vehicles/Edit', [
            'vehicle' => $vehicle,
            'categories' => DB::table('vehicle_categories')->select('id', 'name')->get(),
            'features' => array_map(function ($feature) {
                return ['id' => $feature, 'name' => $feature];
            }, $features)
        ]);
    }

    public function update(Request $request, $id)
    {

        // echo "<pre>";
        // print_r($id);
        // print_r($request->all());
        // die();
        $vehicle = Vehicle::findOrFail($id);

        // Check if the vehicle belongs to the authenticated vendor
        if ($vehicle->vendor_id !== auth()->id()) {
            return redirect()->route('current-vendor-vehicles.index')
                ->with('error', 'You do not have permission to update this vehicle');
        }

        // Validate incoming request data
        $validated = $request->validate([
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
            'location' => 'nullable|string',
            'status' => 'required|in:available,rented,maintenance',
            'features' => 'nullable|array',
            'featured' => 'boolean',
            'security_deposit' => 'required|numeric|min:0',
            'payment_method' => 'required|array',
            'payment_method.*' => 'string|in:credit_card,cheque,bank_wire,cryptocurrency,other',
            'price_per_day' => 'required|numeric|min:0',
            'price_per_week' => 'nullable|numeric|min:0',
            'weekly_discount' => 'nullable|numeric|min:0|max:1000',
            'price_per_month' => 'nullable|numeric|min:0',
            'monthly_discount' => 'nullable|numeric|min:0|max:10000',
            'preferred_price_type' => 'required|in:day,week,month',
            'registration_number' => 'required|string|max:50',
            'registration_country' => 'required|string|max:50',
            'registration_date' => 'required|date',
            'gross_vehicle_mass' => 'required|integer|min:0',
            'vehicle_height' => 'required|numeric|min:0',
            'dealer_cost' => 'required|numeric|min:0',
            'phone_number' => 'required|string|max:15',
            'limited_km' => 'boolean',
            'cancellation_available' => 'boolean',
            'price_per_km' => 'nullable|numeric|min:0',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $validated['limited_km'] = filter_var($request->limited_km, FILTER_VALIDATE_BOOLEAN);
        $validated['cancellation_available'] = filter_var($request->cancellation_available, FILTER_VALIDATE_BOOLEAN);
        // Set default values for nullable fields
        $latitude = $request->latitude ?? 0;
        $longitude = $request->longitude ?? 0;
        $features = $request->features ?? [];

        // Update vehicle
        $vehicle->update([
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
            'location' => $request->location ?? '',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'status' => $request->status,
            'features' => json_encode($features),
            'featured' => (bool) $request->featured,
            'security_deposit' => $request->security_deposit,
            'payment_method' => json_encode($request->payment_method),
            'price_per_day' => $request->price_per_day,
            'price_per_week' => $request->price_per_week == 0 ? null : $request->price_per_week,
            'weekly_discount' => $request->weekly_discount == 0 ? null : $request->weekly_discount,
            'price_per_month' => $request->price_per_month == 0 ? null : $request->price_per_month,
            'monthly_discount' => $request->monthly_discount == 0 ? null : $request->monthly_discount,
            'preferred_price_type' => $request->preferred_price_type,
            'limited_km' => $request->limited_km ?? false,
            'cancellation_available' => $request->cancellation_available ?? false,
            'price_per_km' => $request->price_per_km == 0 ? null : $request->price_per_km,
        ]);

        // Update or create specifications
        if ($vehicle->specifications) {
            $vehicle->specifications()->update([
                'registration_number' => $request->registration_number,
                'registration_country' => $request->registration_country,
                'registration_date' => $request->registration_date,
                'gross_vehicle_mass' => $request->gross_vehicle_mass,
                'vehicle_height' => $request->vehicle_height,
                'dealer_cost' => $request->dealer_cost,
                'phone_number' => $request->phone_number,
            ]);
        } else {
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
        }

        // Handle vehicle images if included in the request
        if ($request->hasFile('images')) {
            foreach ($vehicle->images as $image) {
                Storage::disk('upcloud')->delete($image->image_url);
                $image->delete();
            }

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
        }


        return redirect()->route('current-vendor-vehicles.index')
            ->with('success', 'Vehicle updated successfully');
    }

    public function destroy($id)
    {
        $vendorId = auth()->id();
        $vehicle = Vehicle::where('vendor_id', $vendorId)->findOrFail($id);

        // Delete images from storage
        foreach ($vehicle->images as $image) {
            Storage::disk('upcloud')->delete($image->image_url);
        }

        $vehicle->delete();

        return redirect()->route('current-vendor-vehicles.index')
            ->with('success', 'Vehicle deleted successfully');
    }

    public function deleteImage($vehicleId, $imageId)
    {
        $image = VehicleImage::where('vehicle_id', $vehicleId)->where('id', $imageId)->first();

        if (!$image) {
            return response()->json(['message' => 'Image not found'], 404);
        }

    if ($image->image_url && $image->image_url !== null) {
   
        try {
            Storage::disk('upcloud')->delete($image->image_url);
        } catch (\Exception $e) {

        }
    }
       

        // Delete from database
        $image->delete();

        return response()->json(['message' => 'Image deleted successfully']);
    }
}