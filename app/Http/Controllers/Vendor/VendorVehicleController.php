<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleFeature;
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

        $vehicles = Vehicle::with(['specifications', 'images', 'category', 'user', 'vendorProfile', 'benefits'])
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
        $vehicle = Vehicle::with(['specifications', 'images', 'benefits'])->findOrFail($id);
        // print_r($vehicle->all());
        // die();

        // Check if the vehicle belongs to the authenticated vendor
        if ($vehicle->vendor_id !== auth()->id()) {
            return redirect()->route('current-vendor-vehicles.index')
                ->with('error', 'You do not have permission to edit this vehicle');
        }

        return Inertia::render('Vendor/Vehicles/Edit', [
            'vehicle' => $vehicle,
            'categories' => DB::table('vehicle_categories')->select('id', 'name')->get(),
            // Fetch features specific to the vehicle's category
            'features' => VehicleFeature::where('category_id', $vehicle->category_id)->get(['id', 'feature_name as name', 'icon_url'])->toArray()
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

        // echo "<pre>";
        // print_r($request->all());

        // Validate incoming request data
        $validated = $request->validate([
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
            'location' => 'nullable|string',
            'status' => 'required|in:available,rented,maintenance',
            'features' => 'nullable|array', // Features will be an array of selected feature names
            'featured' => 'boolean',
            'security_deposit' => 'required|numeric|min:0',
            'payment_method' => 'required|array',
            'payment_method.*' => 'string|in:credit_card,cheque,bank_wire,cryptocurrency,cash',
            'price_per_day' => 'required|numeric|min:0',
            'price_per_week' => 'nullable|numeric|min:0',
            'weekly_discount' => 'nullable|numeric|min:0|max:100000',
            'price_per_month' => 'nullable|numeric|min:0',
            'monthly_discount' => 'nullable|numeric|min:0|max:100000',
            'preferred_price_type' => 'required|in:day,week,month',
            'registration_number' => 'required|string|max:50',
            'registration_country' => 'required|string|max:50',
            'registration_date' => 'required|date',
            'gross_vehicle_mass' => 'required|integer|min:0',
            'vehicle_height' => 'required|numeric|min:0',
            'dealer_cost' => 'required|numeric|min:0',
            'phone_number' => 'required|string|max:15',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB per image
            'primary_image_index' => 'nullable|integer|min:0', // Index of the primary image among newly uploaded files
            'existing_primary_image_id' => 'nullable|integer|exists:vehicle_images,id', // ID of an existing image to set as primary

            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',

            // Vehicle benefits fields
            'benefits.limited_km_per_day' => 'boolean',
            'benefits.limited_km_per_week' => 'boolean',
            'benefits.limited_km_per_month' => 'boolean',
            'benefits.limited_km_per_day_range' => 'nullable|decimal:0,2|min:0',
            'benefits.limited_km_per_week_range' => 'nullable|decimal:0,2|min:0',
            'benefits.limited_km_per_month_range' => 'nullable|decimal:0,2|min:0',
            'benefits.cancellation_available_per_day' => 'boolean',
            'benefits.cancellation_available_per_week' => 'boolean',
            'benefits.cancellation_available_per_month' => 'boolean',
            'benefits.cancellation_available_per_day_date' => 'nullable|integer|min:0',
            'benefits.cancellation_available_per_week_date' => 'nullable|integer|min:0',
            'benefits.cancellation_available_per_month_date' => 'nullable|integer|min:0',
            'benefits.price_per_km_per_day' => 'nullable|numeric|min:0',
            'benefits.price_per_km_per_week' => 'nullable|numeric|min:0',
            'benefits.price_per_km_per_month' => 'nullable|numeric|min:0',
            'benefits.minimum_driver_age' => 'required|integer|min:18',
            'guidelines' => 'nullable|string|max:50000',
            'pickup_times' => 'required|array',
            'return_times' => 'required|array',
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
            'city' => $request->city ?? '',
            'state' => $request->state ?? '',
            'country' => $request->country ?? '',
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
            'guidelines' => $request->guidelines,
            'pickup_times' => $request->pickup_times,
            'return_times' => $request->return_times,
        ]);



        // Update or create vehicle benefits
        $benefitsData = [
            'limited_km_per_day' => (bool) ($request->input('benefits.limited_km_per_day', false)),
            'limited_km_per_week' => (bool) ($request->input('benefits.limited_km_per_week', false)),
            'limited_km_per_month' => (bool) ($request->input('benefits.limited_km_per_month', false)),
            'limited_km_per_day_range' => $request->input('benefits.limited_km_per_day_range'),
            'limited_km_per_week_range' => $request->input('benefits.limited_km_per_week_range'),
            'limited_km_per_month_range' => $request->input('benefits.limited_km_per_month_range'),
            'cancellation_available_per_day' => (bool) ($request->input('benefits.cancellation_available_per_day', false)),
            'cancellation_available_per_week' => (bool) ($request->input('benefits.cancellation_available_per_week', false)),
            'cancellation_available_per_month' => (bool) ($request->input('benefits.cancellation_available_per_month', false)),
            'cancellation_available_per_day_date' => $request->input('benefits.cancellation_available_per_day_date'),
            'cancellation_available_per_week_date' => $request->input('benefits.cancellation_available_per_week_date'),
            'cancellation_available_per_month_date' => $request->input('benefits.cancellation_available_per_month_date'),
            'price_per_km_per_day' => $request->input('benefits.price_per_km_per_day'),
            'price_per_km_per_week' => $request->input('benefits.price_per_km_per_week'),
            'price_per_km_per_month' => $request->input('benefits.price_per_km_per_month'),
            'minimum_driver_age' => $request->input('benefits.minimum_driver_age'),
        ];

        if ($vehicle->benefits) {
            $vehicle->benefits()->update($benefitsData);
        } else {
            $vehicle->benefits()->create($benefitsData);
        }

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
        // Handle primary image update for existing images
        if ($request->filled('existing_primary_image_id') && !$request->hasFile('images')) {
            VehicleImage::where('vehicle_id', $vehicle->id)->update(['image_type' => 'gallery']); // Reset all to gallery
            VehicleImage::where('id', $request->existing_primary_image_id)
                        ->where('vehicle_id', $vehicle->id)
                        ->update(['image_type' => 'primary']);
        }

        // Handle vehicle images if included in the request
        if ($request->hasFile('images')) {
            $currentImageCount = $vehicle->images->count();
            $newImageCount = count($request->file('images'));

            if ($currentImageCount + $newImageCount > 20) {
                return redirect()->back()->with('error', 'Maximum of 20 images allowed');
            }

            $primaryImageIndex = $request->filled('primary_image_index') ? (int)$request->primary_image_index : -1; // -1 if not making a new image primary

            // If a new primary is set, or if existing_primary_image_id was set, ensure all existing are gallery first
            if ($primaryImageIndex !== -1 || $request->filled('existing_primary_image_id')) {
                 VehicleImage::where('vehicle_id', $vehicle->id)->update(['image_type' => 'gallery']);
            }
            
            // If existing_primary_image_id is set and it's different from any new primary, prioritize existing.
            if ($request->filled('existing_primary_image_id')) {
                VehicleImage::where('id', $request->existing_primary_image_id)
                            ->where('vehicle_id', $vehicle->id)
                            ->update(['image_type' => 'primary']);
                // If an existing image is set to primary, new images cannot also be primary from this batch.
                $primaryImageIndex = -1; 
            }


            foreach ($request->file('images') as $index => $image) {
                $originalName = $image->getClientOriginalName();
                $folderName = 'vehicle_images';
                $path = $image->storeAs($folderName, $originalName, 'upcloud');
                $url = Storage::disk('upcloud')->url($path);

                $imageType = 'gallery';
                if ($primaryImageIndex === $index) {
                    // This new image is designated as primary
                    // Ensure all other images (existing and newly added in this loop before this one) are gallery
                    VehicleImage::where('vehicle_id', $vehicle->id)->update(['image_type' => 'gallery']);
                    $imageType = 'primary';
                } elseif ($vehicle->images()->where('image_type', 'primary')->doesntExist() && $index === 0 && $primaryImageIndex === -1 && !$request->filled('existing_primary_image_id')) {
                    // If no primary is set yet (neither existing nor new), make the first uploaded image primary
                    $imageType = 'primary';
                }


                VehicleImage::create([
                    'vehicle_id' => $vehicle->id,
                    'image_path' => $path,
                    'image_url' => $url,
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
