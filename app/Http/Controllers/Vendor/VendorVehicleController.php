<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleSpecification;
use App\Models\VehicleImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

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

    public function storeOrUpdate(Request $request, $id = null)
    {
        $validated = $request->validate([
            // ... (your validation rules – same as before)
        ]);
    
        $vendorId = auth()->id();
    
        if ($id) { // Update existing vehicle
            $vehicle = Vehicle::where('vendor_id', $vendorId)->findOrFail($id);
            $vehicle->update($validated);
            $vehicle->specifications()->update($validated['specifications']);
    
        } else { // Create new vehicle
            $vehicle = new Vehicle($validated);
            $vehicle->vendor_id = $vendorId; // Assign vendor ID
            $vehicle->save();
    
            $vehicle->specifications()->create($validated['specifications']);
        }
    
        // Handle image uploads (common for both create and update)
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('vehicle_images', 'public');
                VehicleImage::create([
                    'vehicle_id' => $vehicle->id,
                    'image_path' => $path,
                    'image_type' => 'gallery',
                ]);
            }
        }
    
    
        return redirect()->route('vehicles.index')
            ->with('success', 'Vehicle ' . ($id ? 'updated' : 'created') . ' successfully');
    }

    public function edit($id)
    {
        $vendorId = auth()->id();
        
        $vehicle = Vehicle::with(['specifications', 'images', 'category'])
            ->where('vendor_id', $vendorId)
            ->findOrFail($id);

        $categories = \DB::table('vehicle_categories')->select('id', 'name')->get();

        return Inertia::render('Vendor/Vehicles/Edit', [
            'vehicle' => $vehicle,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, $id)
    {
        $vendorId = auth()->id();
        $vehicle = Vehicle::where('vendor_id', $vendorId)->findOrFail($id);

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
            'location' => 'required|string',
            'status' => 'required|in:available,rented,maintenance',
            'features' => 'array',
            'featured' => 'boolean',
            'security_deposit' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'price_per_day' => 'required|numeric|min:0',
            
            // Specifications
            'specifications.registration_number' => 'required|string|max:50',
            'specifications.registration_country' => 'required|string|max:50',
            'specifications.registration_date' => 'required|date',
            'specifications.gross_vehicle_mass' => 'required|integer|min:0',
            'specifications.vehicle_height' => 'required|integer|min:0',
            'specifications.dealer_cost' => 'required|numeric|min:0',
            'specifications.phone_number' => 'required|string|max:15',
        ]);

        $vehicle->update($validated);
        $vehicle->specifications()->update($validated['specifications']);

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

        return redirect()->route('vehicles.index')
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

        return redirect()->route('vehicles.index')
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