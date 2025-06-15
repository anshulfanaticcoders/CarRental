<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorVehiclePlan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlanController extends Controller
{
    public function index(Request $request, $locale)
{
    $vendorId = auth()->id();
    $searchQuery = $request->input('search', '');

    $vehicles = \App\Models\Vehicle::where('vendor_id', $vendorId)
        ->with('vendorPlans', 'images') // Fetch associated plans and images
        ->when($searchQuery, function ($query, $searchQuery) {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('brand', 'like', '%' . $searchQuery . '%')
                  ->orWhere('model', 'like', '%' . $searchQuery . '%');
            });
        })
        ->latest()
        ->paginate(7);

    return Inertia::render('Vendor/Plan/Index', [
        'vehicles' => $vehicles,
    ]);
}

    public function edit($locale, $id)
    {
        $plan = VendorVehiclePlan::findOrFail($id);
        return Inertia::render('Vendor/Plan/Edit', [
            'plan' => $plan,
        ]);
    }

    public function update(Request $request, $locale, $id)
    {
        $validated = $request->validate([
            'plans' => 'present|array|max:2',
            'plans.*.id' => 'nullable|exists:vendor_vehicle_plans,id',
            'plans.*.plan_type' => 'required|string',
            'plans.*.price' => 'required|numeric|min:0',
            'plans.*.plan_description' => 'required|string',
            'plans.*.features' => 'nullable|array',
        ]);

        $vehicleId = \App\Models\Vehicle::findOrFail($id)->id;

        $existingPlanIds = VendorVehiclePlan::where('vehicle_id', $vehicleId)
            ->where('vendor_id', auth()->id())
            ->pluck('id')
            ->toArray();

        $incomingPlanIds = collect($validated['plans'])->pluck('id')->filter()->toArray();

        // Delete plans that are not in the incoming request
        $plansToDelete = array_diff($existingPlanIds, $incomingPlanIds);
        if (!empty($plansToDelete)) {
            VendorVehiclePlan::whereIn('id', $plansToDelete)->delete();
        }

        foreach ($validated['plans'] as $planData) {
            $planData['features'] = json_encode($planData['features']);
            $planData['vehicle_id'] = $vehicleId;
            $planData['vendor_id'] = auth()->id();

            if (isset($planData['id'])) {
                // Update existing plan
                $plan = VendorVehiclePlan::findOrFail($planData['id']);
                $plan->update($planData);
            } else {
                // Create new plan
                $highestPlanId = VendorVehiclePlan::where('vehicle_id', $vehicleId)
                                    ->max('plan_id') ?? 0;
                $planData['plan_id'] = $highestPlanId + 1;
                VendorVehiclePlan::create($planData);
            }
        }

        return redirect()->route('VendorPlanIndex', ['locale' => $locale])->with('success', 'Plans updated successfully.');
    }

    public function store(Request $request, $locale)
    {
        // This method is no longer needed, as the update method handles both creation and updates.
        // You can remove this method or leave it empty.
    }


public function destroy($locale, $id)
{
    // Find the plan or return 404 if not found
    $plan = VendorVehiclePlan::findOrFail($id);
    
    // Check if the authenticated user owns this plan
    if ($plan->vendor_id !== auth()->id()) {
        return response()->json(['error' => 'You are not authorized to delete this plan.'], 403);
    }
    
    // Delete the plan
    $plan->delete();
    
    // Return a success response
    return response()->json(['message' => 'Plan deleted successfully.']);
}

}
