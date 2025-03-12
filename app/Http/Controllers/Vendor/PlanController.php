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

    return Inertia::render('Vendor/Plan/Index', [
        'plans' => $plans,
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

        $request->validate([
            'price' => 'required|numeric', 
            'plan_type' => 'required|string', 
        ]);

        $plan->update([
            'price' => $request->input('price'),
            'plan_type' => $request->input('plan_type'),
        ]);

        return redirect()->route('VendorPlanIndex');
    }
}