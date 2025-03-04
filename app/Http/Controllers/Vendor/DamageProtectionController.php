<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\DamageProtection;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DamageProtectionController extends Controller
{
    public function index($bookingId)
    {
        $booking = Booking::with('damageProtection')->findOrFail($bookingId);
        return Inertia::render('Vendor/DamageProtection/Index', [
            'booking' => $booking,
            'damageProtection' => $booking->damageProtection,
        ]);
    }

    public function storeBeforeImages(Request $request, $bookingId)
{
    $request->validate([
        'before_images' => 'required|array|max:5',
        'before_images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    ]);

    $booking = Booking::findOrFail($bookingId);
    $damageProtection = DamageProtection::firstOrCreate(['booking_id' => $bookingId]);

    // Merge existing images
    $existingImages = $damageProtection->before_images ?? [];
    $newImages = [];

    foreach ($request->file('before_images') as $image) {
        $path = $image->store('damage_protections/before', 'public');
        $newImages[] = $path;
    }

    // Merge old and new images
    $damageProtection->before_images = array_merge($existingImages, $newImages);
    $damageProtection->save();

    return redirect()->back()->with('success', 'Before images uploaded successfully.');
}


public function storeAfterImages(Request $request, $bookingId)
{
    $request->validate([
        'after_images' => 'required|array|max:5',
        'after_images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    ]);

    $booking = Booking::findOrFail($bookingId);
    $damageProtection = DamageProtection::firstOrNew(['booking_id' => $bookingId]);

    $afterImages = $damageProtection->after_images ?? []; // Get existing images

    foreach ($request->file('after_images') as $image) {
        $path = $image->store('damage_protections/after', 'public');
        $afterImages[] = $path;
    }

    $damageProtection->after_images = $afterImages;
    $damageProtection->save();

    return redirect()->back()->with('success', 'After images uploaded successfully.');
}

}
