<?php

namespace App\Http\Controllers\Api\Mobile\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingAmount;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OverviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $vendorId = $request->user()->id;

        $vendorBookingIds = Booking::whereHas('vehicle', fn ($q) => $q->where('vendor_id', $vendorId))->pluck('id');

        $totalVehicles = Vehicle::where('vendor_id', $vendorId)->count();
        $activeVehicles = Vehicle::where('vendor_id', $vendorId)->where('status', 'available')->count();
        $rentedVehicles = Vehicle::where('vendor_id', $vendorId)->where('status', 'rented')->count();
        $maintenanceVehicles = Vehicle::where('vendor_id', $vendorId)->where('status', 'maintenance')->count();

        $totalBookings = $vendorBookingIds->count();
        $confirmedBookings = Booking::whereIn('id', $vendorBookingIds)->where('booking_status', 'confirmed')->count();
        $pendingBookings = Booking::whereIn('id', $vendorBookingIds)->where('booking_status', 'pending')->count();
        $activeBookings = Booking::whereIn('id', $vendorBookingIds)->where('booking_status', 'active')->count();
        $completedBookings = Booking::whereIn('id', $vendorBookingIds)->where('booking_status', 'completed')->count();
        $cancelledBookings = Booking::whereIn('id', $vendorBookingIds)->where('booking_status', 'cancelled')->count();

        $todayBookings = Booking::whereIn('id', $vendorBookingIds)
            ->whereDate('pickup_date', Carbon::today())
            ->count();

        $upcomingBookings = Booking::whereIn('id', $vendorBookingIds)
            ->whereIn('booking_status', ['confirmed', 'active'])
            ->whereDate('pickup_date', '>=', Carbon::today())
            ->count();

        $vendorCurrencyRaw = $request->user()->profile?->currency ?? 'EUR';
        $currencyMap = [
            '€' => 'EUR', '$' => 'USD', '£' => 'GBP',
            'د.إ' => 'AED', '₹' => 'INR', '¥' => 'JPY',
        ];
        $currency = $currencyMap[$vendorCurrencyRaw] ?? strtoupper($vendorCurrencyRaw);

        $totalRevenue = (float) BookingAmount::whereIn('booking_id', $vendorBookingIds)
            ->sum('vendor_total_amount');
        $monthRevenue = (float) BookingAmount::whereIn('booking_id', $vendorBookingIds)
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->sum('vendor_total_amount');

        return response()->json([
            'currency' => $currency,
            'counts' => [
                'total_vehicles' => $totalVehicles,
                'active_vehicles' => $activeVehicles,
                'rented_vehicles' => $rentedVehicles,
                'maintenance_vehicles' => $maintenanceVehicles,
                'total_bookings' => $totalBookings,
                'pending_bookings' => $pendingBookings,
                'confirmed_bookings' => $confirmedBookings,
                'active_bookings' => $activeBookings,
                'completed_bookings' => $completedBookings,
                'cancelled_bookings' => $cancelledBookings,
                'today_bookings' => $todayBookings,
                'upcoming_bookings' => $upcomingBookings,
            ],
            'revenue' => [
                'total' => $totalRevenue,
                'this_month' => $monthRevenue,
            ],
        ]);
    }
}
