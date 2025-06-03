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
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            // Add other fields that can be updated here
        ]);

        $vendor_vehicle->update($validatedData);

        return redirect()->route('admin.vehicles.index')->with('success', 'Vehicle updated successfully.');
    }
}
