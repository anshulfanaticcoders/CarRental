<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FooterCategorySetting;
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
            $data['image'] = $request->file('image')->store('categoryImages', 'upcloud');
            $data['image'] = Storage::disk('upcloud')->url($data['image']);
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
            if ($vehicleCategory->image) {
                $oldImagePath = str_replace(Storage::disk('upcloud')->url(''), '', $vehicleCategory->image);
                Storage::disk('upcloud')->delete($oldImagePath);
            }

            $data['image'] = $request->file('image')->store('categoryImages', 'upcloud');
            $data['image'] = Storage::disk('upcloud')->url($data['image']);
        }

        $vehicleCategory->update($data);

        return redirect()->route('vehicles-categories.index')->with('success', 'Vehicle Category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VehicleCategory $vehicleCategory)
    {
        if ($vehicleCategory->image) {
            $oldImagePath = str_replace(Storage::disk('upcloud')->url(''), '', $vehicleCategory->image);
            Storage::disk('upcloud')->delete($oldImagePath);
        }
        $vehicleCategory->delete();
        return redirect()->route('vehicles-categories.index')->with('success', 'Vehicle Category deleted successfully.');
    }



    public function footerSettings()
    {
        $categories = VehicleCategory::all();

        // Get selected categories from the footer_category_settings table
        $footerSettings = FooterCategorySetting::where('type', 'categories')->first();
        $selectedCategories = [];

        if ($footerSettings) {
            // Decode the JSON stored in the settings value
            $selectedCategories = json_decode($footerSettings->value, true) ?? [];
        }

        return Inertia::render('AdminDashboardPages/Settings/FooterCategory/Index', [
            'categories' => $categories,
            'selectedCategories' => $selectedCategories,
        ]);
    }

    public function updateFooterSettings(Request $request)
    {
        $selectedCategories = $request->input('selected_categories', []);

        // Save to footer_category_settings table - using upsert to create or update
        FooterCategorySetting::updateOrCreate(
            ['type' => 'categories'],
            ['value' => json_encode($selectedCategories)]
        );

        return redirect()->route('admin.settings.footer-categories')->with('status', 'Footer settings updated successfully');
    }

    // Method to get footer categories for the front-end
    public function getFooterCategories()
    {
        // Get selected category IDs from footer settings
        $footerSettings = FooterCategorySetting::where('type', 'categories')->first();
        $selectedCategoryIds = [];

        if ($footerSettings) {
            $selectedCategoryIds = json_decode($footerSettings->value, true) ?? [];
        }

        // Get the actual category data for the selected IDs
        $categories = VehicleCategory::whereIn('id', $selectedCategoryIds)->get();

        return response()->json($categories);
    }
}
