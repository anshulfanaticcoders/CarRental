<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VehicleCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class VehicleCategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $categories = VehicleCategory::when($search, function ($query, $search) {
            return $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        })
            ->paginate(10); // Add pagination

        return Inertia::render('AdminDashboardPages/VehicleCategories/Index', [
            'users' => $categories,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
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
     * Update the specified resource in storage.
     */
    public function update(Request $request,  VehicleCategory $vehicleCategory)
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = [
            'name' => $request->name,
            'description' => $request->description,
            'slug' => $request->slug ?? Str::slug($request->name)
        ];

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($vehicleCategory->image && Storage::disk('public')->exists($vehicleCategory->image)) {
                Storage::disk('public')->delete($vehicleCategory->image);
            }

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
}
