<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorVehiclePlan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlanController extends Controller
{
    public function index()
    {
        $plans = VendorVehiclePlan::all();
        return Inertia::render('Vendor/Plan/Index', [
            'plans' => $plans,
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
        $request->validate([
            'plan_value' => 'required|numeric',
        ]);
        $plan->update([
            'price' => $request->input('plan_value'),
        ]);
        return redirect()->route('VendorPlanIndex');
    }
}