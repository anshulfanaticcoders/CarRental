<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleSpecification;
use App\Models\VehicleImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class VendorVehicleController extends Controller
{
    public function index()
    {
        $vendorId = auth()->id();
        
        $vehicles = Vehicle::with(['specifications', 'images', 'category', 'user'])
            ->where('vendor_id', $vendorId)
            ->latest()
            ->get();

        return Inertia::render('Vendor/Vehicles/Index', [
            'vehicles' => $vehicles,
        ]);
    }

    public function edit(Vehicle $vehicle)
    {
        // $this->authorize('update', $vehicle);
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
            'vehicle' => $vehicle->load(['specifications', 'images']),
            'categories' => DB::table('vehicle_categories')->get(),
            'features' => array_map(function($feature) {
                return ['id' => $feature, 'name' => $feature];
            }, $features)
        ]);
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        // $this->authorize('update', $vehicle);

        $validated = $request->validate([
            // Vehicle fields
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
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'status' => 'required|in:available,rented,maintenance',
            'features' => 'array',
            'featured' => 'boolean',
            'security_deposit' => 'required|numeric|min:0',
            'payment_method' => 'required|array',
            'price_per_day' => 'required|numeric|min:0',

            // Specifications
            'registration_number' => 'required|string|max:50',
            'registration_country' => 'required|string|max:50',
            'registration_date' => 'required|date',
            'gross_vehicle_mass' => 'required|integer|min:0',
            'vehicle_height' => 'required|integer|min:0',
            'dealer_cost' => 'required|numeric|min:0',
            'phone_number' => 'required|string|max:15',

            // Images
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Update vehicle
        $vehicle->update([
            ...$validated,
            'features' => json_encode($validated['features']),
            'payment_method' => json_encode($validated['payment_method']),
        ]);

        // Update specifications
        $vehicle->specifications()->update([
            'registration_number' => $validated['registration_number'],
            'registration_country' => $validated['registration_country'],
            'registration_date' => $validated['registration_date'],
            'gross_vehicle_mass' => $validated['gross_vehicle_mass'],
            'vehicle_height' => $validated['vehicle_height'],
            'dealer_cost' => $validated['dealer_cost'],
            'phone_number' => $validated['phone_number'],
        ]);


        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('vehicle_images', 'public');
                VehicleImage::create([
                    'vehicle_id' => $vehicle->id,
                    'image_path' => $path,
                    'image_type' => 'gallery'
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