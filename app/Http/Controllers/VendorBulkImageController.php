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
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg', // Max 2MB per image
        ]);

        $user = Auth::user();
        $uploadedImages = [];
        $errors = [];

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $imageFile) {
                try {
                    $originalName = $imageFile->getClientOriginalName();
                    $folderName = 'vehicle_images/' . $user->id;
                    $compressedImageUrl = ImageCompressionHelper::compressImage(
                        $imageFile,
                        $folderName,
                        quality: 80, // Adjust quality as needed (0-100)
                        maxWidth: 1200, // Optional: Set max width for vehicle images
                        maxHeight: 900 // Optional: Set max height for vehicle images
                    );

                    if (!$compressedImageUrl) {
                        throw new \Exception("Failed to compress image: {$originalName}");
                    }

                    $image = VendorBulkVehicleImage::create([
                        'user_id' => $user->id,
                        'image_path' => $compressedImageUrl,
                        'original_name' => $originalName,
                    ]);

                    $uploadedImages[] = [
                        'id' => $image->id,
                        'url' => Storage::disk('upcloud')->url($image->image_path),
                        'original_name' => $image->original_name,
                    ];
                } catch (\Exception $e) {
                    $errors[] = "Could not upload {$originalName}: " . $e->getMessage();
                }
            }
        }

        if (count($errors) > 0) {
            $flashMessage = [
                'type' => count($uploadedImages) > 0 ? 'warning' : 'error', // warning if some succeeded, error if all failed
                'message' => 'Some images could not be uploaded. Please see details below.',
                'uploaded_images_count' => count($uploadedImages),
                'failed_images_details' => $errors,
            ];
            if (count($uploadedImages) > 0) {
                $flashMessage['message'] = count($uploadedImages) . ' image(s) uploaded successfully. However, some images failed:';
            }
            return redirect()->back()->with('flash', $flashMessage);
        }

        return redirect()->back()->with('flash', [
            'type' => 'success',
            'message' => count($uploadedImages) . ' image(s) uploaded successfully.',
            'uploaded_images_count' => count($uploadedImages),
        ]);
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

        // Delete from database
        $image->delete();

        return response()->json(['message' => 'Image deleted successfully.']);
    }
}
