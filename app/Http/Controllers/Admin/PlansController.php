<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlansController extends Controller
{
    public function index()
    {
        $plans = Plan::paginate(10);
        
        return Inertia::render('AdminDashboardPages/Plans/Index', [
            'plans' => $plans,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'plan_type' => 'required|string|max:255',
            'plan_value' => 'required|numeric|min:0',
            'features' => 'nullable|array'
        ]);

        Plan::create($validated);

        return redirect()->route('plans.index')->with('success', 'Plan created successfully.');
    }

    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'plan_type' => 'required|string|max:255',
            'plan_value' => 'required|numeric|min:0',
            'features' => 'nullable|array'
        ]);

        $plan->update($validated);

        return redirect()->route('plans.index')->with('success', 'Plan updated successfully.');
    }

    public function destroy(Plan $plan)
    {
        $plan->delete();
        return redirect()->route('plans.index')->with('success', 'Plan deleted successfully.');
    }
}