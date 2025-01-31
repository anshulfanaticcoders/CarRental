<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Inertia\Inertia;
use Illuminate\Http\Request;

class VendorBookingController extends Controller
{
    public function index()
    {
        $vendorId = auth()->id();

        $bookings = Booking::with(['customer', 'vehicle', 'payments'])
            ->whereHas('vehicle', function ($query) use ($vendorId) {
                $query->where('vendor_id', $vendorId);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Vendor/Bookings/Index', [
            'bookings' => $bookings
        ]);
    }

    public function update(Request $request, Booking $booking)
    {
        // Verify that the authenticated vendor owns this booking
        if ($booking->vehicle->vendor_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'booking_status' => 'required|in:pending,confirmed,completed,cancelled',
        ]);

        $booking->update($validated);

        return response()->json([
            'message' => 'Booking status updated successfully',
            'booking' => $booking->fresh()
        ]);
    }

    public function cancel(Booking $booking)
    {
        // Verify that the authenticated vendor owns this booking
        if ($booking->vehicle->vendor_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $booking->update(['booking_status' => 'cancelled']);

        return response()->json([
            'message' => 'Booking cancelled successfully'
        ]);
    }
}