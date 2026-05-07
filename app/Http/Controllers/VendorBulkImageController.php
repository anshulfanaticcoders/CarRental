<?php

namespace App\Http\Controllers;

use App\Models\VendorBulkVehicleImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Helpers\ImageCompressionHelper;

class VendorBulkImageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($locale)
    {
        $user = Auth::user();
        $images = VendorBulkVehicleImage::where('user_id', $user->id)->orderBy('created_at', 'desc')->get();

        return response()->json($images->map(function ($image) {
            return [
                'id' => $image->id,
                'url' => Storage::disk('upcloud')->url($image->image_path),
                'thumbnail_url' => $image->thumbnail_path
                    ? Storage::disk('upcloud')->url($image->thumbnail_path)
                    : Storage::disk('upcloud')->url($image->image_path),
                'original_name' => $image->original_name,
                'created_at' => $image->created_at->toFormattedDateString(),
            ];
        }));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $locale)
    {
        $request->validate([
            'images' => 'required|array|max:50', // Max 50 images
            'images.*' => 'image|mimes:jpeg,png,jpg|max:' . (int) config('vehicle_images.upload_max_kb', 5120),
        ]);

        $user = Auth::user();
        $uploadedImages = [];
        $errors = [];

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $imageFile) {
                try {
                    $originalName = $imageFile->getClientOriginalName();
                    $folderName = 'vehicle_images/' . $user->id;
                    $imageValidationError = ImageCompressionHelper::validateVehicleImageUpload($imageFile);
                    if ($imageValidationError !== null) {
                        throw new \Exception($imageValidationError);
                    }

                    $compressedImageSet = ImageCompressionHelper::compressVehicleImageSet($imageFile, $folderName);

                    if (!$compressedImageSet) {
                        throw new \Exception("Failed to compress image: {$originalName}");
                    }

                    $image = VendorBulkVehicleImage::create([
                        'user_id' => $user->id,
                        'image_path' => $compressedImageSet['image_path'],
                        'thumbnail_path' => $compressedImageSet['thumbnail_path'],
                        'original_name' => $originalName,
                    ]);

                    $uploadedImages[] = [
                        'id' => $image->id,
                        'url' => Storage::disk('upcloud')->url($image->image_path),
                        'thumbnail_url' => Storage::disk('upcloud')->url($image->thumbnail_path),
                        'original_name' => $image->original_name,
                    ];
                } catch (\Exception $e) {
                    $errors[] = "Could not upload {$originalName}: " . $e->getMessage();
                }
            }
        }

        if (count($errors) > 0) {
            $errorMessage = count($uploadedImages) > 0
                ? count($uploadedImages) . ' image(s) uploaded successfully, but some images failed: ' . implode(' ', $errors)
                : implode(' ', $errors);

            return redirect()
                ->back()
                ->with(count($uploadedImages) > 0 ? 'success' : 'error', count($uploadedImages) > 0 ? count($uploadedImages) . ' image(s) uploaded successfully.' : $errorMessage)
                ->with(count($uploadedImages) > 0 ? 'error' : 'info', count($uploadedImages) > 0 ? $errorMessage : null);
        }

        return redirect()
            ->back()
            ->with('success', count($uploadedImages) . ' image(s) uploaded successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($locale, VendorBulkVehicleImage $image)
    {
        // Ensure the authenticated user owns this image
        if (Auth::id() !== $image->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete from storage
        Storage::disk('upcloud')->delete($image->image_path);
        if ($image->thumbnail_path) {
            Storage::disk('upcloud')->delete($image->thumbnail_path);
        }

        // Delete from database
        $image->delete();

        return response()->json(['message' => 'Image deleted successfully.']);
    }

    /**
     * Remove multiple specified resources from storage.
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:vendor_bulk_vehicle_images,id',
        ]);

        $user = Auth::user();
        $imageIds = $request->input('ids');

        // Eager load images to delete
        $images = VendorBulkVehicleImage::where('user_id', $user->id)
            ->whereIn('id', $imageIds)
            ->get();

        if ($images->isEmpty()) {
            return response()->json(['message' => 'No images found to delete.'], 404);
        }

        // Delete images from storage
        foreach ($images as $image) {
            Storage::disk('upcloud')->delete($image->image_path);
            if ($image->thumbnail_path) {
                Storage::disk('upcloud')->delete($image->thumbnail_path);
            }
        }

        // Delete from database
        VendorBulkVehicleImage::whereIn('id', $images->pluck('id'))->delete();

        return response()->json(['message' => 'Selected images deleted successfully.']);
    }
}
