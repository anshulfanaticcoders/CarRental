<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;


class BookingDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $vehicles = Booking::when($search, function ($query) use ($search) {
            $query->where('plan', 'like', "%{$search}%") // Search directly in the Booking table
                ->orWhere('booking_number', 'like', "%{$search}%") // Search directly in the Booking table
                ->orWhereHas('customer', function ($customerQuery) use ($search) {
                    $customerQuery->where('first_name', 'like', "%{$search}%") // Search in Customer table
                        ->orWhere('last_name', 'like', "%{$search}%");
                })
                ->orWhereHas('vehicle', function ($vehicleQuery) use ($search) {
                    $vehicleQuery->where('brand', 'like', "%{$search}%") // Search in Vehicle table
                        ->orWhere('color', 'like', "%{$search}%");
                })
                ->orWhereHas('vehicle.vendorProfileData', function ($vendorQuery) use ($search) {
                    $vendorQuery->where('company_name', 'like', "%{$search}%")
                        ->orWhere('company_email', 'like', "%{$search}%");
                });
        })
            ->with(['customer', 'vehicle.vendorProfileData']) // Load related data
            ->paginate(4);

        return Inertia::render('AdminDashboardPages/Bookings/Index', [
            'users' => $vehicles,
            'filters' => $request->only(['search']),
        ]);
    }
}
