<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class VendorOverviewController extends Controller
{
    public function index()
    {
        $vendorId = auth()->id();

        // Counts
        $totalVehicles = Vehicle::where('vendor_id', $vendorId)->count();
        $totalBookings = Booking::whereHas('vehicle', function ($query) use ($vendorId) {
            $query->where('vendor_id', $vendorId);
        })->count();

        $activeBookings = Booking::whereHas('vehicle', function ($query) use ($vendorId) {
            $query->where('vendor_id', $vendorId);
        })->where('booking_status', 'confirmed')->count();
        $completedBookings = Booking::whereHas('vehicle', function ($query) use ($vendorId) {
            $query->where('vendor_id', $vendorId);
        })->where('booking_status', 'completed')->count();
        $cancelledBookings = Booking::whereHas('vehicle', function ($query) use ($vendorId) {
            $query->where('vendor_id', $vendorId);
        })->where('booking_status', 'cancelled')->count();

        $totalRevenue = BookingPayment::whereHas('booking', function ($query) use ($vendorId) {
            $query->whereHas('vehicle', function ($query) use ($vendorId) {
                $query->where('vendor_id', $vendorId);
            });
        })->sum('amount');

        $currency = auth()->user()->profile?->currency ?? '$';

        // New count for active vehicles
        $activeVehicles = Vehicle::where('vendor_id', $vendorId)
            ->where('status', 'available') // Assuming 'status' column and 'available' value
            ->count();

        // New count for rented vehicles
        $rentedVehicles = Vehicle::where('vendor_id', $vendorId)
            ->where('status', 'rented') // Assuming 'status' column and 'available' value
            ->count();

        // New count for rented vehicles
        $maintenanceVehicles = Vehicle::where('vendor_id', $vendorId)
            ->where('status', 'maintenance') // Assuming 'status' column and 'available' value
            ->count();

        // Booking Overview Chart Data (by status)
        $bookingOverview = $this->getBookingOverview($vendorId);

        // Revenue Data (last 12 months)
        $revenueData = $this->getRevenueData($vendorId);

        return Inertia::render('Vendor/Overview/Index', [
            'totalVehicles' => $totalVehicles,
            'totalBookings' => $totalBookings,
            'activeBookings' => $activeBookings,
            'completedBookings' => $completedBookings,
            'cancelledBookings' => $cancelledBookings,
            'totalRevenue' => $totalRevenue,
            'bookingOverview' => $bookingOverview,
            'revenueData' => $revenueData,
            'activeVehicles' => $activeVehicles,
            'rentedVehicles' => $rentedVehicles,
            'maintenanceVehicles' => $maintenanceVehicles,
            'currency' => $currency,
        ]);
    }


    protected function getBookingOverview($vendorId)
    {
        $months = collect(range(1, 12))->map(function ($month) use ($vendorId) {
            $date = Carbon::create()->month($month);

            $data =  Booking::select('booking_status', DB::raw('count(*) as total'))
                ->whereHas('vehicle', function ($query) use ($vendorId) {
                    $query->where('vendor_id', $vendorId);
                })
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', $month)
                ->groupBy('booking_status')
                ->get();

            $counts = $data->pluck('total', 'booking_status');

            return [
                'name' => $date->format('M'),
                'completed' => $counts['completed'] ?? 0,
                'confirmed' => $counts['confirmed'] ?? 0,
                'pending' => $counts['pending'] ?? 0,
                'cancelled' => $counts['cancelled'] ?? 0,
            ];
        });

        return $months->values()->all();
    }

    protected function getRevenueData($vendorId)
    {
        $revenueData = collect(range(0, 11))->map(function ($monthsAgo) use ($vendorId) {
            $date = now()->subMonths($monthsAgo);

            $monthlyRevenue = BookingPayment::whereHas('booking', function ($query) use ($vendorId) {
                $query->whereHas('vehicle', function ($query) use ($vendorId) {
                    $query->where('vendor_id', $vendorId);
                });
            })
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('amount');


            return [
                'name' => $date->format('M'),
                'total' => $monthlyRevenue,
            ];
        });

        return $revenueData->reverse()->values()->all();
    }

    /**
     * Get booking details with revenue data for the modal
     */
    public function getBookingDetailsWithRevenue(Request $request)
    {
        $vendorId = auth()->id();

        // Get bookings for this vendor with their relationships
        $bookings = Booking::with(['customer', 'vehicle.vendorProfile'])
            ->whereHas('vehicle', function ($query) use ($vendorId) {
                $query->where('vendor_id', $vendorId);
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($booking) {
                // Use vendor profile currency as fallback if booking_currency is null
                $currency = $booking->booking_currency ?? $booking->vehicle?->vendorProfile?->currency ?? 'USD';

                return [
                    'id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'customer' => [
                        'first_name' => $booking->customer?->first_name,
                        'last_name' => $booking->customer?->last_name,
                    ],
                    'vehicle' => [
                        'brand' => $booking->vehicle?->brand,
                        'model' => $booking->vehicle?->model,
                    ],
                    'booking_currency' => $currency,
                    'amount_paid' => (float) $booking->amount_paid,
                    'total_amount' => (float) $booking->total_amount,
                    'pending_amount' => (float) $booking->pending_amount,
                    'booking_status' => $booking->booking_status,
                ];
            })
            ->filter(function ($booking) {
                // Only include bookings that have some amount paid
                return $booking['amount_paid'] > 0;
            });

        // Calculate currency breakdown
        $currencyBreakdown = $bookings
            ->groupBy('booking_currency')
            ->map(function ($bookingsByCurrency, $currency) {
                return $bookingsByCurrency->sum('amount_paid');
            })
            ->toArray();

        return response()->json([
            'bookings' => $bookings,
            'currencyBreakdown' => $currencyBreakdown,
        ]);
    }
}
