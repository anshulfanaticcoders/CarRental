<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\ApiBooking;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VendorExternalBookingController extends Controller
{
    public function index(Request $request, $locale)
    {
        $vendorId = auth()->id();
        $search = $request->input('search', '');
        $statusFilter = $request->input('status', '');

        $bookings = ApiBooking::with(['consumer', 'vehicle'])
            ->whereHas('vehicle', fn($q) => $q->where('vendor_id', $vendorId))
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('booking_number', 'like', '%' . $search . '%')
                      ->orWhere('driver_first_name', 'like', '%' . $search . '%')
                      ->orWhere('driver_last_name', 'like', '%' . $search . '%')
                      ->orWhere('driver_email', 'like', '%' . $search . '%')
                      ->orWhere('status', 'like', '%' . $search . '%');
                });
            })
            ->when($statusFilter, fn($query, $status) => $query->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('Vendor/ExternalBookings/Index', [
            'bookings' => $bookings->items(),
            'pagination' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
            'filters' => ['search' => $search, 'status' => $statusFilter],
        ]);
    }

    public function show($locale, ApiBooking $apiBooking)
    {
        if ($apiBooking->vehicle->vendor_id !== auth()->id()) {
            abort(403);
        }

        $apiBooking->load(['consumer', 'extras', 'vehicle']);

        return Inertia::render('Vendor/ExternalBookings/Show', [
            'booking' => $apiBooking,
        ]);
    }

    public function updateStatus(Request $request, $locale, ApiBooking $apiBooking)
    {
        if ($apiBooking->vehicle->vendor_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:confirmed,completed',
        ]);

        $apiBooking->update(['status' => $validated['status']]);

        return redirect()->back()->with('success', 'Booking status updated successfully.');
    }
}
