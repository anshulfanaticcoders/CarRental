<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\DamageProtection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DamageProtectionController extends Controller
{
    public function index(Booking $booking)
    {
        // Fetch existing damage protection record without creating a new one
        $damageProtection = DamageProtection::where('booking_id', $booking->id)->first();

        return inertia('Vendor/DamageProtection/Index', [
            'booking' => $booking,
            'damageProtection' => $damageProtection
        ]);
    }

    public function uploadBeforeImages(Request $request, Booking $booking)
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
                // Store image in damage_protections/before directory on UpCloud Object Storage
                $path = $image->store('damage_protections/before', 'upcloud');
                $beforeImages[] = $path;
            }
        }

        $damageProtection->update([
            'before_images' => $beforeImages
        ]);

        return back()->with('success', 'Before images uploaded successfully')->with('damageProtection', $damageProtection);
    }

    public function uploadAfterImages(Request $request, Booking $booking)
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
                // Store image in damage_protections/after directory on UpCloud Object Storage
                $path = $image->store('damage_protections/after', 'upcloud');
                $afterImages[] = $path;
            }
        }

        $damageProtection->update([
            'after_images' => $afterImages
        ]);

        return back()->with('success', 'After images uploaded successfully')->with('damageProtection', $damageProtection);
    }

    public function deleteBeforeImages(Booking $booking)
    {
        $damageProtection = DamageProtection::where('booking_id', $booking->id)->first();

        if ($damageProtection) {
            // Remove all before images from UpCloud Object Storage
            $beforeImages = $damageProtection->before_images ?? [];
            foreach ($beforeImages as $imagePath) {
                if (Storage::disk('upcloud')->exists($imagePath)) {
                    Storage::disk('upcloud')->delete($imagePath);
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

    public function deleteAfterImages(Booking $booking)
    {
        $damageProtection = DamageProtection::where('booking_id', $booking->id)->first();

        if ($damageProtection) {
            // Remove all after images from UpCloud Object Storage
            $afterImages = $damageProtection->after_images ?? [];
            foreach ($afterImages as $imagePath) {
                if (Storage::disk('upcloud')->exists($imagePath)) {
                    Storage::disk('upcloud')->delete($imagePath);
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