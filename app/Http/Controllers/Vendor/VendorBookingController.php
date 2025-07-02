<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\User;
use App\Models\UserDocument;
use App\Models\Vehicle;
use App\Models\VendorProfile;
use App\Notifications\Booking\BookingStatusUpdatedAdminNotification;
use App\Notifications\Booking\BookingStatusUpdatedCompanyNotification;
use App\Notifications\Booking\BookingStatusUpdatedCustomerNotification;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Illuminate\Http\Request;

class VendorBookingController extends Controller
{
    public function index(Request $request, $locale)
{
    $vendorId = auth()->id();
    $searchQuery = $request->input('search', '');

    $bookings = Booking::with(['customer', 'vehicle', 'payments','vendorProfile'])
        ->whereHas('vehicle', function ($query) use ($vendorId) {
            $query->where('vendor_id', $vendorId);
        })
        ->when($searchQuery, function ($query, $searchQuery) {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('booking_number', 'like', '%' . $searchQuery . '%')
                  ->orWhereHas('customer', function ($q) use ($searchQuery) {
                      $q->where('first_name', 'like', '%' . $searchQuery . '%')
                        ->orWhere('last_name', 'like', '%' . $searchQuery . '%');
                  })
                  ->orWhereHas('vehicle', function ($q) use ($searchQuery) {
                      $q->where('brand', 'like', '%' . $searchQuery . '%')
                        ->orWhere('model', 'like', '%' . $searchQuery . '%');
                  })
                  ->orWhere('booking_status', 'like', '%' . $searchQuery . '%')
                  ->orWhereHas('payments', function ($q) use ($searchQuery) {
                      $q->where('payment_status', 'like', '%' . $searchQuery . '%');
                  })
                  ->orWhere('pickup_date', 'like', '%' . $searchQuery . '%')
                  ->orWhere('return_date', 'like', '%' . $searchQuery . '%');
            });
        })
        ->orderBy('created_at', 'desc')
        ->paginate(8);

    return Inertia::render('Vendor/Bookings/Index', [
        'bookings' => $bookings->items(),
        'pagination' => [
            'current_page' => $bookings->currentPage(),
            'last_page' => $bookings->lastPage(),
            'per_page' => $bookings->perPage(),
            'total' => $bookings->total(),
        ],
        'filters' => $request->all(),
    ]);
}
    

    public function update(Request $request, $locale, Booking $booking)
    {
        // Verify that the authenticated vendor owns this booking
        if ($booking->vehicle->vendor_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'booking_status' => 'required|in:pending,confirmed,completed,cancelled',
        ]);

        $booking->update($validated);

        if ($request->booking_status === 'cancelled') {
            $booking->pickup_date = null;
            $booking->return_date = null;
            $booking->save();
        }

        if ($request->booking_status === 'confirmed') {
            $vehicle = Vehicle::find($booking->vehicle_id);
            $vehicle->update(['status' => 'rented']);
        }else if($request->booking_status === 'completed' || $request->booking_status === 'cancelled') {
            $vehicle = Vehicle::find($booking->vehicle_id);
            $vehicle->update(['status' => 'available']);
        }

        // Send notifications
        $customer = $booking->customer;
        $vehicle = $booking->vehicle;
        $vendor = User::find($vehicle->vendor_id);
        $vendorProfile = VendorProfile::where('user_id', $vehicle->vendor_id)->first();

        // Notify Admin
        $adminEmail = env('VITE_ADMIN_EMAIL', 'default@admin.com');
        $admin = User::where('email', $adminEmail)->first();
        if ($admin) {
            $admin->notify(new BookingStatusUpdatedAdminNotification($booking, $customer, $vehicle, $vendor));
        }

        // Notify Customer
        if ($customer && $customer->user) { // Ensure customer and associated user exist
            $customer->user->notify(new BookingStatusUpdatedCustomerNotification($booking, $customer, $vehicle, $vendor));
        }

        // Notify Company
    if ($vendorProfile && $vendorProfile->company_email) {
        Notification::route('mail', $vendorProfile->company_email)
            ->notify(new BookingStatusUpdatedCompanyNotification($booking, $customer, $vehicle, $vendorProfile));
    }

        return response()->json([
            'message' => 'Booking status updated successfully',
            'booking' => $booking->fresh()
        ]);
    }

    public function cancel(Request $request, $locale, Booking $booking)
    {
        // Verify that the authenticated vendor owns this booking
        if ($booking->vehicle->vendor_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'cancellation_reason' => 'required|string',
        ]);

        $booking->update([
            'booking_status' => 'cancelled',
            'cancellation_reason' => $validated['cancellation_reason'],
        ]);

        $vehicle = Vehicle::find($booking->vehicle_id);
        $vehicle->update(['status' => 'available']);
        // Send notifications
        $customer = $booking->customer;
        $vehicle = $booking->vehicle;
        $vendor = User::find($vehicle->vendor_id);

        // Notify Admin
        $adminEmail = env('VITE_ADMIN_EMAIL', 'default@admin.com');
        $admin = User::where('email', $adminEmail)->first();
        if ($admin) {
            $admin->notify(new BookingStatusUpdatedAdminNotification($booking, $customer, $vehicle, $vendor));
        }

        // Notify Customer
        if ($customer) {
            Notification::route('mail', $customer->email)
                ->notify(new BookingStatusUpdatedCustomerNotification($booking, $customer, $vehicle, $vendor));
        }

        return back()->with('success', 'Booking cancelled successfully.');
    }

    public function viewCustomerDocuments($locale, $customerId)
    {
        // Fetch customer by ID
        $customer = Customer::findOrFail($customerId);

        // Retrieve the single document for the user associated with the customer
        $document = UserDocument::where('user_id', $customer->user_id)->first([
            'id',
            'user_id',
            'driving_license_front',
            'driving_license_back',
            'passport_front',
            'passport_back',
            'verification_status',
            'created_at',
        ]);

        // Return the document as JSON
        return response()->json([
            'document' => $document,
        ]);
    }
    
}
