<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VehicleCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class VehicleCategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = VehicleCategory::paginate(10); // Add pagination
        
        return Inertia::render('AdminDashboardPages/VehicleCategories/Index', [
            'categories' => $categories
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('AdminDashboardPages/VehicleCategories/Create');

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:vehicle_categories',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->all();
        $data['slug'] = $request->slug ?? Str::slug($request->name);
        
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categoryImages', 'public');
        }

        VehicleCategory::create($data);

        return redirect()->route('vehicles-categories.index')->with('success', 'Vehicle Category created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(VehicleCategory $vehicleCategory)
    {
        return Inertia::render('AdminDashboardPages/VehicleCategories/Show', [
            'category' => $vehicleCategory,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(VehicleCategory $vehicleCategory)
    {
        return Inertia::render('VehicleCategories/Edit', [
            'category' => $vehicleCategory
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request,  VehicleCategory $vehicleCategory)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:vehicle_categories,slug,' . $vehicleCategory->id,
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->all();
        $data['slug'] = $request->slug ?? Str::slug($request->name);
        
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categoryImages', 'public');
        }

        $vehicleCategory->update($data);

        return redirect()->route('vehicles-categories.index')->with('success', 'Vehicle Category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VehicleCategory $vehicleCategory)
    {
        $vehicleCategory->delete();
        return redirect()->route('vehicles-categories.index')->with('success', 'Vehicle Category deleted successfully.');
    }

    // this is to show categories on home page
    public function getActiveCategories()
    {
        $categories = VehicleCategory::where('status', true)->get();
        
        return Inertia::render('Welcome', [
            'categories' => $categories
        ]);
    }
}
