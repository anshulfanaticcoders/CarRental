<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlansController extends Controller
{
    public function index(Request $request)
{
    $search = $request->query('search');
    
    $plans = Plan::when($search, function ($query, $search) {
        return $query->where('plan_type', 'LIKE', "%{$search}%");
    })->paginate(10);

    return Inertia::render('AdminDashboardPages/Plans/Index', [
        'plans' => $plans,
        'filters' => $request->only(['search']),
    ]);
}


    public function store(Request $request)
    {
        $validated = $request->validate([
            'plan_type' => 'required|string|max:255',
            'plan_value' => 'required|numeric|min:0',
            'features' => 'nullable|array',
            'plan_description' => 'nullable|string',
        ]);

        Plan::create($validated);

        return redirect()->route('plans.index')->with('success', 'Plan created successfully.');
    }

    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'plan_type' => 'required|string|max:255',
            'plan_value' => 'required|numeric|min:0',
            'features' => 'nullable|array',
            'plan_description' => 'nullable|string',
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