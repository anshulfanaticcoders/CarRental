<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorVehiclePlan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlanController extends Controller
{
    public function index(Request $request)
{
    $perPage = $request->input('per_page', 7);
    $searchQuery = $request->input('search', '');

    $plans = VendorVehiclePlan::where('vendor_id', auth()->id())
        ->with(['vehicle', 'plan'])
        ->when($searchQuery, function ($query, $searchQuery) {
            $query->where(function ($q) use ($searchQuery) {
                $q->whereHas('vehicle', function ($q) use ($searchQuery) {
                    $q->where('brand', 'like', '%' . $searchQuery . '%');
                })->orWhere('plan_type', 'like', '%' . $searchQuery . '%')
                  ->orWhere('price', 'like', '%' . $searchQuery . '%');
            });
        })
        ->paginate($perPage);

        // Get all vehicles belonging to the current vendor
    $vehicles = \App\Models\Vehicle::where('vendor_id', auth()->id())->get();

    return Inertia::render('Vendor/Plan/Index', [
        'plans' => $plans,
        'vehicles' => $vehicles,
        'filters' => $request->all(),
    ]);
}

    public function edit($id)
    {
        $plan = VendorVehiclePlan::findOrFail($id);
        return Inertia::render('Vendor/Plan/Edit', [
            'plan' => $plan,
        ]);
    }

    public function update(Request $request, $id)
    {
        $plan = VendorVehiclePlan::findOrFail($id);

        $validated = $request->validate([
            'price' => 'required|numeric|min:0',
            'plan_type' => 'required|string',
            'plan_description' => 'required|string',
            'features' => 'nullable|array',
        ]);

        $validated['features'] = isset($validated['features']) ? json_encode($validated['features']) : json_encode([]);

        $plan->update($validated);

        return redirect()->route('VendorPlanIndex')->with('success', 'Plan updated successfully.');
    }

    public function store(Request $request)
{
    $validated = $request->validate([
        'vehicle_id' => 'required|exists:vehicles,id',
        'plan_type' => 'required|string',
        'price' => 'required|numeric|min:0',
        'features' => 'nullable|array',
        'plan_description' => 'required|string',
    ]);

    $validated['vendor_id'] = auth()->id();
    $validated['features'] = json_encode($validated['features']);
    
    // Find the highest plan_id for this vehicle and increment it
    $highestPlanId = VendorVehiclePlan::where('vehicle_id', $validated['vehicle_id'])
                        ->max('plan_id') ?? 0;
    $validated['plan_id'] = $highestPlanId + 1;
    
    $plan = VendorVehiclePlan::create($validated);

    return redirect()->route('VendorPlanIndex')->with('success', 'Plan created successfully.');
}


public function destroy($id)
{
    // Find the plan or return 404 if not found
    $plan = VendorVehiclePlan::findOrFail($id);
    
    // Check if the authenticated user owns this plan
    if ($plan->vendor_id !== auth()->id()) {
        return redirect()->route('VendorPlanIndex')
            ->with('error', 'You are not authorized to delete this plan.');
    }
    
    // Delete the plan
    $plan->delete();
    
    // Redirect with success message
    return redirect()->route('VendorPlanIndex')
        ->with('success', 'Plan deleted successfully.');
}
}