<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Vehicle;
use App\Models\VendorVehiclePlan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VendorVehiclePlanController extends Controller
{
    /**
     * Get all plans for the vendor to select from
     */
    public function getPlans()
    {
        $plans = Plan::all();
        
        return response()->json([
            'plans' => $plans
        ]);
    }
    
    /**
     * Store a newly created vendor vehicle plan
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'plan_id' => 'required|exists:plans,id',
            'plan_type' => 'required|string',
            'price' => 'required|numeric|min:0',
            'features' => 'nullable|array'
        ]);
        
        $validated['vendor_id'] = auth()->id();
        
        // Check if plan already exists for this vehicle and vendor
        $existingPlan = VendorVehiclePlan::where('vendor_id', auth()->id())
            ->where('vehicle_id', $request->vehicle_id)
            ->where('plan_type', $request->plan_type)
            ->first();
            
        if ($existingPlan) {
            $existingPlan->update($validated);
            return response()->json(['message' => 'Plan updated successfully', 'plan' => $existingPlan]);
        }
        
        $plan = VendorVehiclePlan::create($validated);
        
        return response()->json(['message' => 'Plan added successfully', 'plan' => $plan]);
    }
    
    /**
     * Get vehicle plans for a specific vehicle
     */
    public function getVehiclePlans($vehicleId)
    {
        $vehiclePlans = VendorVehiclePlan::where('vendor_id', auth()->id())
            ->where('vehicle_id', $vehicleId)
            ->with('plan')
            ->get();
            
        return response()->json([
            'vehiclePlans' => $vehiclePlans
        ]);
    }
    
    /**
     * Delete a vehicle plan
     */
    public function destroy($id)
    {
        $plan = VendorVehiclePlan::where('id', $id)
            ->where('vendor_id', auth()->id())
            ->firstOrFail();
            
        $plan->delete();
        
        return response()->json(['message' => 'Plan removed successfully']);
    }
}