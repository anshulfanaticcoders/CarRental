<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiBooking;
use App\Models\ApiConsumer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class AdminExternalBookingController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $statusFilter = $request->input('status', '');
        $consumerFilter = $request->input('api_consumer_id', '');

        $bookings = ApiBooking::with(['consumer', 'vehicle'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('booking_number', 'like', '%' . $search . '%')
                      ->orWhere('driver_first_name', 'like', '%' . $search . '%')
                      ->orWhere('driver_last_name', 'like', '%' . $search . '%')
                      ->orWhere('driver_email', 'like', '%' . $search . '%')
                      ->orWhere('vehicle_name', 'like', '%' . $search . '%');
                });
            })
            ->when($statusFilter, fn($query, $status) => $query->where('status', $status))
            ->when($consumerFilter, fn($query, $consumerId) => $query->where('api_consumer_id', $consumerId))
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $consumers = ApiConsumer::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('AdminDashboardPages/ExternalBookings/Index', [
            'bookings' => $bookings,
            'consumers' => $consumers,
            'filters' => [
                'search' => $search,
                'status' => $statusFilter,
                'api_consumer_id' => $consumerFilter,
            ],
        ]);
    }

    public function show(ApiBooking $apiBooking)
    {
        $apiBooking->load(['consumer', 'extras', 'vehicle', 'vehicle.vendor']);

        return Inertia::render('AdminDashboardPages/ExternalBookings/Show', [
            'booking' => $apiBooking,
        ]);
    }

    public function confirm(ApiBooking $apiBooking)
    {
        if ($apiBooking->status !== 'pending') {
            return redirect()->back()->with('error', 'Only pending bookings can be confirmed.');
        }

        $apiBooking->update(['status' => 'confirmed']);

        try {
            Notification::route('mail', $apiBooking->driver_email)
                ->notify(new \App\Notifications\ApiBooking\ApiBookingCreatedDriverNotification($apiBooking));
        } catch (\Exception $e) {
            Log::warning('Failed to send driver confirmation email', [
                'booking_number' => $apiBooking->booking_number,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->back()->with('success', 'Booking confirmed successfully.');
    }

    public function cancel(Request $request, ApiBooking $apiBooking)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        if (in_array($apiBooking->status, ['cancelled', 'completed'])) {
            return redirect()->back()->with('error', 'This booking cannot be cancelled.');
        }

        $apiBooking->update([
            'status' => 'cancelled',
            'cancellation_reason' => $request->input('reason'),
            'cancelled_at' => now(),
        ]);

        try {
            Notification::route('mail', $apiBooking->driver_email)
                ->notify(new \App\Notifications\ApiBooking\ApiBookingCancelledDriverNotification($apiBooking));
        } catch (\Exception $e) {
            Log::warning('Failed to send driver cancellation email', [
                'booking_number' => $apiBooking->booking_number,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $vendor = $apiBooking->vehicle && $apiBooking->vehicle->vendor_id
                ? User::find($apiBooking->vehicle->vendor_id)
                : null;
            if ($vendor) {
                $vendor->notify(new \App\Notifications\ApiBooking\ApiBookingCancelledVendorNotification($apiBooking));
            }
        } catch (\Exception $e) {
            Log::warning('Failed to send vendor cancellation email', [
                'booking_number' => $apiBooking->booking_number,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->back()->with('success', 'Booking cancelled successfully.');
    }

    public function markCompleted(ApiBooking $apiBooking)
    {
        if ($apiBooking->status !== 'confirmed') {
            return redirect()->back()->with('error', 'Only confirmed bookings can be marked as completed.');
        }

        $apiBooking->update(['status' => 'completed']);

        return redirect()->back()->with('success', 'Booking marked as completed.');
    }
}
