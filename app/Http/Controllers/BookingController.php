<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Booking::with(['customer', 'vehicle'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Bookings/Index', [
            'bookings' => $bookings
        ]);
    }

    public function create()
    {
        return Inertia::render('Bookings/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'pickup_date' => 'required|date',
            'return_date' => 'required|date|after:pickup_date',
            'pickup_location' => 'required|string',
            'return_location' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $booking = new Booking($validated);
            $booking->customer_id = auth()->id();
            $booking->booking_number = Booking::generateBookingNumber();
            
            // Calculate total days
            $pickupDate = \Carbon\Carbon::parse($validated['pickup_date']);
            $returnDate = \Carbon\Carbon::parse($validated['return_date']);
            $booking->total_days = $pickupDate->diffInDays($returnDate);

            // Get vehicle price and calculate amounts
            $vehicle = Vehicle::findOrFail($validated['vehicle_id']);
            $booking->base_price = $vehicle->price_per_day * $booking->total_days;
            $booking->tax_amount = $booking->base_price * 0.20; // Assuming 20% tax
            $booking->total_amount = $booking->base_price + $booking->extra_charges + $booking->tax_amount;

            $booking->save();

            DB::commit();

            return redirect()->route('bookings.show', $booking)
                ->with('success', 'Booking created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error creating booking. Please try again.');
        }
    }

    public function show(Booking $booking)
    {
        $booking->load(['customer', 'vehicle']);
        
        return Inertia::render('Bookings/Show', [
            'booking' => $booking
        ]);
    }

    public function update(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'booking_status' => 'sometimes|required|in:pending,confirmed,ongoing,completed,cancelled',
            'payment_status' => 'sometimes|required|in:pending,paid,partially_paid,refunded',
            'cancellation_reason' => 'required_if:booking_status,cancelled',
            'notes' => 'nullable|string',
        ]);

        $booking->update($validated);

        return back()->with('success', 'Booking updated successfully.');
    }

    public function destroy(Booking $booking)
    {
        $booking->delete();
        
        return redirect()->route('bookings.index')
            ->with('success', 'Booking deleted successfully.');
    }
}