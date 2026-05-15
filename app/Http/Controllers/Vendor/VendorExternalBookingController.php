<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\ApiBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class VendorExternalBookingController extends Controller
{
    public function index(Request $request, $locale)
    {
        $vendorId = auth()->id();
        $search = $request->input('search', '');
        $statusFilter = $request->input('status', '');

        $base = ApiBooking::query()
            ->whereHas('vehicle', fn($q) => $q->where('vendor_id', $vendorId))
            ->where('is_test', false);

        $statusCounts = (clone $base)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $totalRevenue = (clone $base)
            ->whereIn('status', ['confirmed', 'completed'])
            ->sum('total_amount');

        $thisMonthRevenue = (clone $base)
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('total_amount');

        $analytics = [
            'total_bookings' => (clone $base)->count(),
            'pending_count' => $statusCounts['pending'] ?? 0,
            'confirmed_count' => $statusCounts['confirmed'] ?? 0,
            'completed_count' => $statusCounts['completed'] ?? 0,
            'cancelled_count' => $statusCounts['cancelled'] ?? 0,
            'total_revenue' => (float) $totalRevenue,
            'this_month_revenue' => (float) $thisMonthRevenue,
        ];

        $bookings = $base
            ->with(['consumer', 'vehicle'])
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
            'analytics' => $analytics,
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

        // When vendor confirms — send confirmation email to the driver
        if ($validated['status'] === 'confirmed') {
            try {
                Notification::route('mail', $apiBooking->driver_email)
                    ->notify(new \App\Notifications\ApiBooking\ApiBookingCreatedDriverNotification($apiBooking));
            } catch (\Exception $e) {
                Log::warning('Failed to send driver confirmation email', [
                    'booking_number' => $apiBooking->booking_number,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect()->back()->with('success', 'Booking status updated successfully.');
    }
}
