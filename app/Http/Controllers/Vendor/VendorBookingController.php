<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\UserDocument;
use App\Models\Vehicle;
use Inertia\Inertia;
use Illuminate\Http\Request;

class VendorBookingController extends Controller
{
    public function index(Request $request)
{
    $vendorId = auth()->id();
    $searchQuery = $request->input('search', '');

    $bookings = Booking::with(['customer', 'vehicle', 'payments'])
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
        ->paginate(2);

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

        if ($request->booking_status === 'confirmed') {
            $vehicle = Vehicle::find($booking->vehicle_id);
            $vehicle->update(['status' => 'rented']);
        }else if($request->booking_status === 'completed' || $request->booking_status === 'cancelled') {
            $vehicle = Vehicle::find($booking->vehicle_id);
            $vehicle->update(['status' => 'available']);
        }

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

    public function viewCustomerDocuments($customerId)
    {
        // Fetch customer by ID
        $customer = Customer::findOrFail($customerId);

        // Retrieve the documents for the user associated with the customer
        $documents = UserDocument::where('user_id', $customer->user_id)->get();

        // Return the documents as JSON
        return response()->json([
            'documents' => $documents
        ]);
    }
    
}