<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VehicleCategory;
use App\Models\VehicleFeature;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminFeaturesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Logic to fetch categories and their features will be added here
        // For now, let's return a placeholder or an empty array
        $categories = VehicleCategory::with('features')->get();
        return Inertia::render('AdminDashboardPages/Features/Index', [
            'categories' => $categories
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * We'll pass the specific category to which the feature is being added.
     */
    public function create(VehicleCategory $category)
    {
        return Inertia::render('AdminDashboardPages/Features/Create', [
            'category' => $category
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:vehicle_categories,id',
            'feature_name' => 'required|string|max:255',
            'icon_url' => 'nullable|url|max:255',
        ]);

        VehicleFeature::create($validated);

        // Redirect to the index page or a success page
        // Might need to redirect back to the category's feature list
        return redirect()->route('admin.features.index')->with('success', 'Feature added successfully.');
    }

    /**
     * Display the specified resource. (Not typically used for features directly, usually part of index or edit)
     */
    public function show(VehicleFeature $feature)
    {
        // Not typically needed for this kind of resource management via Inertia
        // return Inertia::render('AdminDashboardPages/Features/Show', ['feature' => $feature]);
        return redirect()->route('admin.features.edit', $feature);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(VehicleFeature $feature)
    {
        // Eager load the category to avoid N+1 issues if displaying category info
        $feature->load('category'); 
        return Inertia::render('AdminDashboardPages/Features/Edit', [
            'feature' => $feature
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, VehicleFeature $feature)
    {
        $validated = $request->validate([
            // category_id is not typically changed during an update of a feature,
            // if it needs to be changed, it's more like deleting and recreating.
            // 'category_id' => 'required|exists:vehicle_categories,id', 
            'feature_name' => 'required|string|max:255',
            'icon_url' => 'nullable|url|max:255',
        ]);

        $feature->update($validated);

        return redirect()->route('admin.features.index')->with('success', 'Feature updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VehicleFeature $feature)
    {
        $feature->delete();
        return redirect()->route('admin.features.index')->with('success', 'Feature deleted successfully.');
    }
}
