<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleFeature;
use App\Models\VehicleImage;
use App\Models\VehicleSpecification;
use App\Models\BookingAddon;
use App\Models\VendorVehicleAddon;
use App\Models\VendorVehiclePlan;
use App\Helpers\ImageCompressionHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log; // Add this line
use Inertia\Inertia;
use Illuminate\Validation\ValidationException;

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
                        ->orWhere('full_vehicle_address', 'like', '%' . $searchQuery . '%')
                        ->orWhere('status', 'like', '%' . $searchQuery . '%');
                });
            })
            ->latest()
            ->paginate(6); // Paginate with 6 items per page

        // Get category statistics for ALL vehicles (not just current page)
        $categoryStats = Vehicle::with('category')
            ->where('vendor_id', $vendorId)
            ->get()
            ->groupBy('category.name')
            ->map(function ($vehicles, $categoryName) {
                return [
                    'name' => $categoryName ?: 'Uncategorized',
                    'count' => $vehicles->count()
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->take(8)
            ->toArray();

        return Inertia::render('Vendor/Vehicles/Index', [
            'vehicles' => $vehicles->items(),  // Get only the vehicle data
            'pagination' => [
                'current_page' => $vehicles->currentPage(),
                'last_page' => $vehicles->lastPage(),
                'per_page' => $vehicles->perPage(),
                'total' => $vehicles->total(),
            ],
            'categoryStats' => $categoryStats, // Add category statistics
            'totalVehicles' => $vehicles->total(), // Add total vehicles count
            'filters' => $request->all(), // Add the filters to the response
        ]);
    }


    public function edit($locale, $id)
    {
        $vehicle = Vehicle::with(['specifications', 'images', 'benefits', 'vendorPlans', 'addons'])->findOrFail($id);
        // print_r($vehicle->all());
        // die();

        // Check if the vehicle belongs to the authenticated vendor
        if ($vehicle->vendor_id !== auth()->id()) {
            return redirect()->route('current-vendor-vehicles.index', ['locale' => app()->getLocale()])
                ->with('error', 'You do not have permission to edit this vehicle');
        }

        return Inertia::render('Vendor/Vehicles/Edit', [
            'vehicle' => $vehicle,
            'categories' => DB::table('vehicle_categories')->select('id', 'name')->get(),
            // Fetch features specific to the vehicle's category
            'features' => VehicleFeature::where('category_id', $vehicle->category_id)->get(['id', 'feature_name as name', 'icon_url'])->toArray()
        ]);
    }

    public function update(Request $request, $locale, $id)
    {

        // echo "<pre>";
        // print_r($id);
        // print_r($request->all());
        // die();
        $vehicle = Vehicle::findOrFail($id);

        // Check if the vehicle belongs to the authenticated vendor
        if ($vehicle->vendor_id !== auth()->id()) {
            return redirect()->route('current-vendor-vehicles.index', ['locale' => app()->getLocale()])
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
            'location_type' => 'required|string|max:255',
            'status' => 'required|in:available,rented,maintenance',
            'features' => 'nullable|array', // Features will be an array of selected feature names
            'featured' => 'boolean',
            'security_deposit' => 'required|numeric|min:0',
            'payment_method' => 'required|array',
            'payment_method.*' => 'string|in:credit_card,cheque,bank_wire,cryptocurrency,cash',
            'price_per_day' => 'nullable|numeric|min:0',
            'price_per_week' => 'nullable|numeric|min:0',
            'weekly_discount' => 'nullable|numeric|min:0|max:100000',
            'price_per_month' => 'nullable|numeric|min:0',
            'monthly_discount' => 'nullable|numeric|min:0|max:100000',
            'preferred_price_type' => 'required|in:day,week,month',
            'registration_number' => 'required|string|max:50',
            'registration_country' => 'required|string|max:50',
            'registration_date' => 'required|date',
            'gross_vehicle_mass' => 'nullable|integer|min:0',
            'vehicle_height' => 'nullable|numeric|min:0',
            'dealer_cost' => 'nullable|numeric|min:0',
            'phone_number' => 'required|string|max:15',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'primary_image_index' => 'nullable|integer|min:0',
            'existing_primary_image_id' => 'nullable|integer|exists:vehicle_images,id',

            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'full_vehicle_address' => 'nullable|string|max:255',
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

            'selected_plans' => 'nullable|array|max:3',
            'selected_plans.*.plan_type' => 'required|string|in:Basic,Essential,Premium,Premium Plus',
            'selected_plans.*.plan_value' => 'required|numeric|min:0',
            'selected_plans.*.features' => 'nullable|array|max:5',
            'selected_plans.*.features.*' => 'string|max:255',
            'selected_plans.*.plan_description' => 'nullable|string|max:2000',

            'selected_addons' => 'nullable|array',
            'selected_addons.*' => 'integer|exists:booking_addons,id',
            'addon_prices' => 'nullable|array',
            'addon_prices.*' => 'nullable|numeric|min:0',
            'addon_quantities' => 'nullable|array',
            'addon_quantities.*' => 'nullable|integer|min:1',

            'custom_addons' => 'nullable|array',
            'custom_addons.*.extra_name' => 'required|string|max:255',
            'custom_addons.*.extra_type' => 'nullable|string|max:255',
            'custom_addons.*.description' => 'nullable|string|max:2000',
            'custom_addons.*.price' => 'required|numeric|min:0',
            'custom_addons.*.quantity' => 'required|integer|min:1',
        ]);

        // Set default values for nullable fields
        $latitude = ($request->has('latitude') && $request->input('latitude') !== '')
            ? $request->input('latitude')
            : $vehicle->latitude;
        $longitude = ($request->has('longitude') && $request->input('longitude') !== '')
            ? $request->input('longitude')
            : $vehicle->longitude;
        $features = $request->features ?? [];

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
                    throw ValidationException::withMessages([
                        'selected_plans' => ['Protection plan price must be at least the daily price.']
                    ]);
                }
            }
        }

        $selectedAddons = $request->input('selected_addons', []);
        $addonPrices = $request->input('addon_prices', []);
        $addonQuantities = $request->input('addon_quantities', []);
        $customAddons = $request->input('custom_addons', []);
        if (!empty($selectedAddons)) {
            foreach ($selectedAddons as $addonId) {
                $price = $addonPrices[$addonId] ?? null;
                $quantity = $addonQuantities[$addonId] ?? null;
                if (!is_numeric($price) || $price < 0) {
                    throw ValidationException::withMessages([
                        'addon_prices' => ['Please provide a valid price for each selected addon.']
                    ]);
                }
                if (!is_numeric($quantity) || $quantity < 1) {
                    throw ValidationException::withMessages([
                        'addon_quantities' => ['Please provide a valid quantity for each selected addon.']
                    ]);
                }
            }
        }

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

        try {
            DB::transaction(function () use ($vehicle, $request, $latitude, $longitude, $features, $benefitsData, $selectedPlans, $selectedAddons, $addonPrices, $addonQuantities, $customAddons) {
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
                    'location_type' => $request->location_type,
                    'city' => $request->city ?? '',
                    'state' => $request->state ?? '',
                    'country' => $request->country ?? '',
                    'full_vehicle_address' => $request->full_vehicle_address,
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

                VendorVehiclePlan::where('vehicle_id', $vehicle->id)->delete();
                if (!empty($selectedPlans)) {
                    $planId = 1;
                    foreach ($selectedPlans as $selectedPlan) {
                        $planType = $selectedPlan['plan_type'] ?? 'Basic';
                        if ($planType === 'Basic') {
                            continue;
                        }

                        $features = isset($selectedPlan['features']) && is_array($selectedPlan['features'])
                            ? array_values(array_filter($selectedPlan['features'], fn($feature) => trim($feature) !== ''))
                            : null;

                        VendorVehiclePlan::create([
                            'vendor_id' => $request->user()->id,
                            'vehicle_id' => $vehicle->id,
                            'plan_id' => $planId,
                            'plan_type' => $planType,
                            'price' => $selectedPlan['plan_value'] ?? 0,
                            'features' => $features ? json_encode(array_slice($features, 0, 5)) : null,
                            'plan_description' => $selectedPlan['plan_description'] ?? null,
                        ]);

                        $planId++;
                    }
                }

                VendorVehicleAddon::where('vehicle_id', $vehicle->id)->delete();
                if (!empty($selectedAddons)) {
                    foreach ($selectedAddons as $addonId) {
                        $addon = BookingAddon::find($addonId);
                        if (!$addon) {
                            continue;
                        }

                        VendorVehicleAddon::create([
                            'vendor_id' => $request->user()->id,
                            'vehicle_id' => $vehicle->id,
                            'addon_id' => $addonId,
                            'extra_type' => $addon->extra_type,
                            'extra_name' => $addon->extra_name,
                            'price' => $addonPrices[$addonId] ?? $addon->price ?? 0,
                            'quantity' => $addonQuantities[$addonId] ?? 1,
                            'description' => $addon->description,
                        ]);
                    }
                }

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
            });
        } catch (\Throwable $error) {
            Log::error('Vehicle update failed', [
                'vehicle_id' => $vehicle->id,
                'vendor_id' => $request->user()->id,
                'message' => $error->getMessage(),
            ]);
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'We could not update this vehicle. Please review the form and try again.'
                ], 500);
            }
            return back()->with('error', 'We could not update this vehicle. Please review the form and try again.')
                ->withInput();
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
                $folderName = 'vehicle_images';
                $compressedImageUrl = ImageCompressionHelper::compressImage(
                    $image,
                    $folderName,
                    quality: 80, // Adjust quality as needed (0-100)
                    maxWidth: 1200, // Optional: Set max width
                    maxHeight: 800 // Optional: Set max height
                );

                if (!$compressedImageUrl) {
                    return redirect()->back()->with('error', 'Failed to compress image: ' . $image->getClientOriginalName());
                }

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

                // Get the full URL for the stored image
                $fullImageUrl = Storage::disk('upcloud')->url($compressedImageUrl);

                VehicleImage::create([
                    'vehicle_id' => $vehicle->id,
                    'image_path' => $compressedImageUrl, // Store the relative path
                    'image_url' => $fullImageUrl,        // Store the full URL
                    'image_type' => $imageType,
                ]);
            }
        }


        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Vehicle updated successfully',
                'vehicle' => $vehicle->fresh(['specifications', 'images', 'benefits', 'vendorPlans', 'addons']),
            ]);
        }

        return back()->with('success', 'Vehicle updated successfully');
    }

    public function destroy($locale, $id)
    {
        $vendorId = auth()->id();
        $vehicle = Vehicle::where('vendor_id', $vendorId)->findOrFail($id);

        // Delete images from storage
        foreach ($vehicle->images as $image) {
            Storage::disk('upcloud')->delete($image->image_url);
        }

        $vehicle->delete();

        return redirect()->route('current-vendor-vehicles.index', ['locale' => app()->getLocale()])
            ->with('success', 'Vehicle deleted successfully');
    }

    public function deleteImage($locale, $vehicleId, $imageId) // Added $locale parameter
    {
        $image = VehicleImage::where('vehicle_id', $vehicleId)->where('id', $imageId)->first();

        if (!$image) {
            Log::warning("Image not found for deletion. Vehicle ID: {$vehicleId}, Image ID: {$imageId}"); // Log if image not found
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

    public function bulkDestroy(Request $request)
    {
        $vendorId = auth()->id();
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:vehicles,id',
        ]);

        $vehicleIdsToDelete = $validated['ids'];

        // Fetch only vehicles that belong to the authenticated vendor
        $vehicles = Vehicle::where('vendor_id', $vendorId)
                            ->whereIn('id', $vehicleIdsToDelete)
                            ->with('images') // Eager load images
                            ->get();

        if ($vehicles->isEmpty()) {
            return redirect()->route('current-vendor-vehicles.index', ['locale' => app()->getLocale()])
                             ->with('error', 'No vehicles found or you do not have permission to delete them.');
        }

        $deletedCount = 0;
        foreach ($vehicles as $vehicle) {
            // Delete images from storage
            foreach ($vehicle->images as $image) {
                if ($image->image_url) { // Check if image_url is not null
                    try {
                        Storage::disk('upcloud')->delete($image->image_url);
                    } catch (\Exception $e) {
                        // Log error or handle as needed
                    }
                }
            }
            $vehicle->delete();
            $deletedCount++;
        }

        if ($deletedCount > 0) {
            return redirect()->route('current-vendor-vehicles.index', ['locale' => app()->getLocale()])
                             ->with('success', $deletedCount . ' vehicle(s) deleted successfully.');
        }

        return redirect()->route('current-vendor-vehicles.index', ['locale' => app()->getLocale()])
                         ->with('error', 'Could not delete the selected vehicles.');
    }
}
