<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\DamageProtection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DamageProtectionController extends Controller
{
    public function index($locale, Booking $booking)
    {
        // Fetch existing damage protection record without creating a new one
        $damageProtection = DamageProtection::where('booking_id', $booking->id)->first();

        return inertia('Vendor/DamageProtection/Index', [
            'booking' => $booking,
            'damageProtection' => $damageProtection,
        ]);
    }

    public function uploadBeforeImages(Request $request, $locale, Booking $booking)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);
    
        $damageProtection = DamageProtection::firstOrCreate([
            'booking_id' => $booking->id
        ]);
    
        $beforeImages = $damageProtection->before_images ?? [];
    
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $folderName = 'damage_protections/before';
                $compressedImageUrl = \App\Helpers\ImageCompressionHelper::compressImage(
                    $image,
                    $folderName,
                    quality: 80, // Adjust quality as needed (0-100)
                    maxWidth: 1200, // Optional: Set max width
                    maxHeight: 900 // Optional: Set max height
                );

                if ($compressedImageUrl) {
                    $url = Storage::disk('upcloud')->url($compressedImageUrl);
                    $beforeImages[] = $url;
                } else {
                    // Handle compression failure, e.g., log error or return an error response
                    return back()->withErrors(['images' => 'Failed to compress image: ' . $image->getClientOriginalName()]);
                }
            }
        }
    
        // // Debugging
        // print_r([
        //     'beforeImages' => $beforeImages,
        //     'damageProtection' => $damageProtection->toArray(),
        // ]);
        // die();
    
        $damageProtection->update([
            'before_images' => $beforeImages
        ]);
    
        return back()->with('success', 'Before images uploaded successfully')->with('damageProtection', $damageProtection);
    }
    

    public function uploadAfterImages(Request $request, $locale, Booking $booking)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $damageProtection = DamageProtection::firstOrCreate([
            'booking_id' => $booking->id
        ]);

        $afterImages = $damageProtection->after_images ?? [];

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $folderName = 'damage_protections/after';
                $compressedImageUrl = \App\Helpers\ImageCompressionHelper::compressImage(
                    $image,
                    $folderName,
                    quality: 80, // Adjust quality as needed (0-100)
                    maxWidth: 1200, // Optional: Set max width
                    maxHeight: 900 // Optional: Set max height
                );

                if ($compressedImageUrl) {
                    $url = Storage::disk('upcloud')->url($compressedImageUrl);
                    $afterImages[] = $url;
                } else {
                    // Handle compression failure, e.g., log error or return an error response
                    return back()->withErrors(['images' => 'Failed to compress image: ' . $image->getClientOriginalName()]);
                }
            }
        }

        $damageProtection->update([
            'after_images' => $afterImages
        ]);

        return back()->with('success', 'After images uploaded successfully')->with('damageProtection', $damageProtection);
    }

    public function deleteBeforeImages($locale, Booking $booking)
    {
        $damageProtection = DamageProtection::where('booking_id', $booking->id)->first();

        if ($damageProtection) {
            // Remove all before images from UpCloud Object Storage
            $beforeImages = $damageProtection->before_images ?? [];
            foreach ($beforeImages as $imageUrl) {
                $imageKey = basename($imageUrl);
                if (Storage::disk('upcloud')->exists('damage_protections/before/' . $imageKey)) {
                    Storage::disk('upcloud')->delete('damage_protections/before/' . $imageKey);
                }
            }

            // Update the damage protection record
            $damageProtection->update([
                'before_images' => []
            ]);

            return back()->with('success', 'All before images deleted successfully')->with('damageProtection', $damageProtection);
        }

        return back()->with('error', 'No before images found');
    }

    public function deleteAfterImages($locale, Booking $booking)
    {
        $damageProtection = DamageProtection::where('booking_id', $booking->id)->first();

        if ($damageProtection) {
            // Remove all after images from UpCloud Object Storage
            $afterImages = $damageProtection->after_images ?? [];
            foreach ($afterImages as $imageUrl) {
                $imageKey = basename($imageUrl);
                if (Storage::disk('upcloud')->exists('damage_protections/after/' . $imageKey)) {
                    Storage::disk('upcloud')->delete('damage_protections/after/' . $imageKey);
                }
            }

            // Update the damage protection record
            $damageProtection->update([
                'after_images' => []
            ]);

            return back()->with('success', 'All after images deleted successfully')->with('damageProtection', $damageProtection);
        }

        return back()->with('error', 'No after images found');
    }

    // Optional: Method to get image URL
    public function getImageUrl($imagePath)
    {
        return Storage::disk('upcloud')->url($imagePath);
    }
}
