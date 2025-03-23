<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BookingDashboardController extends Controller
{
    public function index(Request $request)
    {
        return $this->getBookings($request, 'all'); 
    }

    public function pending(Request $request)
    {
        return $this->getBookings($request, 'pending');
    }

    public function confirmed(Request $request)
    {
        return $this->getBookings($request, 'confirmed');
    }

    public function completed(Request $request)
    {
        return $this->getBookings($request, 'completed');
    }
        public function cancelled(Request $request)
    {
        return $this->getBookings($request, 'cancelled');
    }


    private function getBookings(Request $request, $status)
    {
        $search = $request->query('search');

        $bookings = Booking::when($search, function ($query) use ($search) {
            $query->where('plan', 'like', "%{$search}%")
                ->orWhere('booking_number', 'like', "%{$search}%")
                ->orWhereHas('customer', function ($customerQuery) use ($search) {
                    $customerQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })
                ->orWhereHas('vehicle', function ($vehicleQuery) use ($search) {
                    $vehicleQuery->where('brand', 'like', "%{$search}%")
                        ->orWhere('color', 'like', "%{$search}%");
                })
                ->orWhereHas('vehicle.vendorProfileData', function ($vendorQuery) use ($search) {
                    $vendorQuery->where('company_name', 'like', "%{$search}%")
                        ->orWhere('company_email', 'like', "%{$search}%");
                });
        });

        if ($status !== 'all') { // Filter by status if not 'all'
            $bookings->where('booking_status', $status);
        }

        $bookings = $bookings->orderBy('created_at', 'desc')->with(['customer', 'vehicle.vendorProfileData','payments','vendorProfile'])->paginate(4);
        

        return Inertia::render('AdminDashboardPages/Bookings/Index', [
            'users' => $bookings,
            'filters' => $request->only(['search']), // Only search filter
            'currentStatus' => $status, // Pass current status to the view
        ]);
    }
}