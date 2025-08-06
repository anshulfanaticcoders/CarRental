<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VehicleDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $vehicles = Vehicle::whereHas('User', function ($query) use ($search) {
            if ($search) {
                $query->where('brand', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('color', 'like', "%{$search}%")
                    ->orWhere('price_per_day', 'like', "%{$search}%")
                    ->orWhere('full_vehicle_address', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            }
        })
        ->orderBy('created_at', 'desc')
            ->with(['User','vendorProfile'])
            ->paginate(6);

        return Inertia::render('AdminDashboardPages/Vehicles/Index', [
            'users' => $vehicles,
            'filters' => $request->only(['search']),
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
        $vendor_vehicle->delete();

        return redirect()->route('admin.vehicles.index')->with('success', 'Vehicle deleted successfully.');
    }
}
