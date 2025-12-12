<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Helpers\ImageCompressionHelper;

class AdminAdvertisementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $advertisements = Advertisement::latest()->paginate(10);
        return Inertia::render('AdminDashboardPages/Advertisements/Index', [
            'advertisements' => $advertisements,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'offer_type' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'button_text' => 'required|string|max:255',
            'button_link' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'is_external' => 'boolean',
        ]);

        $imagePath = null;

        if ($request->hasFile('image')) {
            $folderName = 'advertisements';

            // Compress and upload to UpCloud
            // Using portrait dimensions for the vertical ad banner (4:6 aspect ratio)
            $compressedImagePath = ImageCompressionHelper::compressImage(
                $request->file('image'),
                $folderName,
                quality: 85,
                maxWidth: 800,
                maxHeight: 1200
            );

            if ($compressedImagePath) {
                $imagePath = Storage::disk('upcloud')->url($compressedImagePath);
            }
        }

        if (!$imagePath) {
            return back()->withErrors(['image' => 'Failed to upload image.']);
        }

        Advertisement::create([
            'offer_type' => $request->offer_type,
            'title' => $request->title,
            'description' => $request->description,
            'button_text' => $request->button_text,
            'button_link' => $request->button_link,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'image_path' => $imagePath,
            'is_active' => $request->has('is_active') ? $request->is_active : true,
            'is_external' => $request->has('is_external') ? $request->is_external : false,
        ]);

        return redirect()->back()->with('success', 'Advertisement created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Advertisement $advertisement)
    {
        $request->validate([
            'offer_type' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'button_text' => 'required|string|max:255',
            'button_link' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'is_external' => 'boolean',
        ]);

        $data = $request->except('image');

        if ($request->hasFile('image')) {
            // Delete old image from UpCloud if exists
            if ($advertisement->image_path) {
                try {
                    $parsedPath = parse_url($advertisement->image_path, PHP_URL_PATH);
                    $relativePath = ltrim($parsedPath, '/');
                    if ($relativePath) {
                        Storage::disk('upcloud')->delete($relativePath);
                    }
                } catch (\Exception $e) {
                    \Log::error("Failed to delete old advertisement image: " . $e->getMessage());
                }
            }

            $folderName = 'advertisements';

            // Compress and upload new image to UpCloud
            // Using portrait dimensions for the vertical ad banner (4:6 aspect ratio)
            $compressedImagePath = ImageCompressionHelper::compressImage(
                $request->file('image'),
                $folderName,
                quality: 85,
                maxWidth: 800,
                maxHeight: 1200
            );

            if ($compressedImagePath) {
                $data['image_path'] = Storage::disk('upcloud')->url($compressedImagePath);
            } else {
                return back()->withErrors(['image' => 'Failed to upload image.']);
            }
        }

        $advertisement->update($data);

        return redirect()->back()->with('success', 'Advertisement updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Advertisement $advertisement)
    {
        // Delete image from UpCloud if exists
        if ($advertisement->image_path) {
            try {
                $parsedPath = parse_url($advertisement->image_path, PHP_URL_PATH);
                $relativePath = ltrim($parsedPath, '/');
                if ($relativePath) {
                    Storage::disk('upcloud')->delete($relativePath);
                }
            } catch (\Exception $e) {
                \Log::error("Failed to delete advertisement image: " . $e->getMessage());
            }
        }

        $advertisement->delete();

        return redirect()->back()->with('success', 'Advertisement deleted successfully.');
    }
}
