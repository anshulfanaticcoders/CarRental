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
    public function index()
{
    $vendorId = auth()->id();

    $vehicles = Vehicle::with(['specifications', 'images', 'category', 'user','vendorProfile'])
        ->where('vendor_id', $vendorId)
        ->latest()
        ->paginate(8); // Paginate with 10 items per page

    return Inertia::render('Vendor/Vehicles/Index', [
        'vehicles' => $vehicles->items(),  // Get only the vehicle data
        'pagination' => [
            'current_page' => $vehicles->currentPage(),
            'last_page' => $vehicles->lastPage(),
            'per_page' => $vehicles->perPage(),
            'total' => $vehicles->total(),
        ],
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
            'features' => array_map(function($feature) {
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
        ]);

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
            'featured' => (bool)$request->featured,
            'security_deposit' => $request->security_deposit,
            'payment_method' => json_encode($request->payment_method),
            'price_per_day' => $request->price_per_day,
            'price_per_week' => $request->price_per_week ?? null,
            'weekly_discount' => $request->weekly_discount ?? null,
            'price_per_month' => $request->price_per_month ?? null,
            'monthly_discount' => $request->monthly_discount ?? null,
            'preferred_price_type' => $request->preferred_price_type,
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
            foreach ($request->file('images') as $index => $image) {
                $imagePath = $image->store('vehicle_images', 'public');

                // Determine image type
                $imageType = ($index === 0 && !$vehicle->images()->where('image_type', 'primary')->exists()) 
                    ? 'primary' 
                    : 'gallery';

                // Create vehicle image record
                VehicleImage::create([
                    'vehicle_id' => $vehicle->id,
                    'image_path' => $imagePath,
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
            Storage::disk('public')->delete($image->image_path);
        }

        $vehicle->delete();

        return redirect()->route('current-vendor-vehicles.index')
            ->with('success', 'Vehicle deleted successfully');
    }

    public function deleteImage($vehicleId, $imageId)
    {
        $vendorId = auth()->id();
        $vehicle = Vehicle::where('vendor_id', $vendorId)->findOrFail($vehicleId);
        $image = $vehicle->images()->findOrFail($imageId);

        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return back()->with('success', 'Image deleted successfully');
    }
}