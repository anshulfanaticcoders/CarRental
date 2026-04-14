<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Services\Vehicles\VehicleDeletionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VehicleDashboardController extends Controller
{
    public function __construct(
        private readonly VehicleDeletionService $vehicleDeletionService,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');

        // Build the vehicle query
        $vehiclesQuery = Vehicle::with(['User', 'vendorProfileData', 'vendorLocation', 'images']);

        // Apply search filter
        if ($search) {
            $vehiclesQuery->where(function ($query) use ($search) {
                $query->where('brand', 'like', "%{$search}%")
                      ->orWhere('model', 'like', "%{$search}%")
                      ->orWhere('status', 'like', "%{$search}%")
                      ->orWhere('color', 'like', "%{$search}%")
                      ->orWhere('price_per_day', 'like', "%{$search}%")
                      ->orWhere('full_vehicle_address', 'like', "%{$search}%")
                      ->orWhereHas('vendorLocation', function ($locationQuery) use ($search) {
                          $locationQuery->where('name', 'like', "%{$search}%")
                              ->orWhere('city', 'like', "%{$search}%")
                              ->orWhere('country', 'like', "%{$search}%");
                      })
                      ->orWhereHas('vendorProfileData', function ($vendorProfileQuery) use ($search) {
                          $vendorProfileQuery->where('company_name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('User', function ($userQuery) use ($search) {
                          $userQuery->where('first_name', 'like', "%{$search}%")
                               ->orWhere('last_name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                      });
            });
        }

        // Apply status filter
        if ($status && $status !== 'all') {
            $vehiclesQuery->where('status', $status);
        }

        $vehicles = $vehiclesQuery->orderBy('created_at', 'desc')->paginate(7);

        // Get vehicle status counts
        $statusCounts = [
            'total' => Vehicle::count(),
            'available' => Vehicle::where('status', 'available')->count(),
            'rented' => Vehicle::where('status', 'rented')->count(),
            'maintenance' => Vehicle::where('status', 'maintenance')->count(),
        ];

        return Inertia::render('AdminDashboardPages/Vehicles/Index', [
            'users' => $vehicles,
            'statusCounts' => $statusCounts,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Vehicle $vehicle)
    {
        return Inertia::render('AdminDashboardPages/Vehicles/Edit', [
            'vehicle' => $vehicle,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Vehicle $vendor_vehicle)
    {
        $validatedData = $request->validate([
            'location' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'price_per_day' => 'nullable|numeric|min:0',
            'price_per_week' => 'nullable|numeric|min:0',
            'price_per_month' => 'nullable|numeric|min:0',
            'preferred_price_type' => 'nullable|string|in:day,week,month',
        ]);

        // Convert 0 values to null for price fields
        foreach (['price_per_day', 'price_per_week', 'price_per_month'] as $priceField) {
            if (isset($validatedData[$priceField]) && $validatedData[$priceField] === 0) {
                $validatedData[$priceField] = null;
            }
        }

        // Construct full_vehicle_address
        $fullAddressParts = [];
        if (!empty($validatedData['location'])) {
            $fullAddressParts[] = $validatedData['location'];
        }
        if (!empty($validatedData['city'])) {
            $fullAddressParts[] = $validatedData['city'];
        }
        if (!empty($validatedData['state'])) {
            $fullAddressParts[] = $validatedData['state'];
        }
        if (!empty($validatedData['country'])) {
            $fullAddressParts[] = $validatedData['country'];
        }
        $validatedData['full_vehicle_address'] = implode(', ', array_filter($fullAddressParts));

        $vendor_vehicle->update($validatedData);

        return redirect()->route('admin.vehicles.index')->with('success', 'Vehicle updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vehicle $vendor_vehicle)
    {
        $vehicleId = $vendor_vehicle->id;

        dispatch(function () use ($vehicleId) {
            $vehicle = Vehicle::query()
                ->whereKey($vehicleId)
                ->with(['images', 'bookings.damageProtection'])
                ->first();

            if (!$vehicle) {
                return;
            }

            app(VehicleDeletionService::class)->delete($vehicle);
        })->afterResponse();

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Deletion started for 1 vehicle.',
                'accepted_count' => 1,
                'accepted_ids' => [$vehicleId],
            ], 202);
        }

        return redirect()->route('admin.vehicles.index')->with('success', 'Deletion started for 1 vehicle.');
    }

    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:vehicles,id'],
        ]);

        $ids = array_values(array_unique($validated['ids']));
        $acceptedCount = count($ids);

        dispatch(function () use ($ids) {
            $vehicles = Vehicle::query()
                ->whereIn('id', $ids)
                ->with(['images', 'bookings.damageProtection'])
                ->get();

            if ($vehicles->isEmpty()) {
                return;
            }

            app(VehicleDeletionService::class)->deleteMany($vehicles);
        })->afterResponse();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Deletion started for {$acceptedCount} vehicle(s).",
                'accepted_count' => $acceptedCount,
                'accepted_ids' => $ids,
            ], 202);
        }

        return redirect()->route('admin.vehicles.index')
            ->with('success', "Deletion started for {$acceptedCount} vehicle(s).");
    }
}
