<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingAmount;
use App\Models\Vehicle;
use Carbon\Carbon;
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

        // Get vendor's currency from profile
        $vendorCurrencyRaw = auth()->user()->profile?->currency ?? 'EUR';
        $currencyMap = [
            '€' => 'EUR',
            '$' => 'USD',
            '£' => 'GBP',
            'د.إ' => 'AED',
            '₹' => 'INR',
            '¥' => 'JPY',
        ];
        $currency = $currencyMap[$vendorCurrencyRaw] ?? strtoupper($vendorCurrencyRaw);

        // Revenue: Use vendor_total_amount from booking_amounts (vendor's actual earnings in their currency)
        $vendorBookingIds = Booking::whereHas('vehicle', function ($query) use ($vendorId) {
            $query->where('vendor_id', $vendorId);
        })->pluck('id');

        $totalRevenue = BookingAmount::whereIn('booking_id', $vendorBookingIds)
            ->sum('vendor_total_amount');

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

            // Use vendor_total_amount from booking_amounts (vendor's actual revenue in their currency)
            $monthlyRevenue = BookingAmount::whereHas('booking', function ($query) use ($vendorId, $date) {
                $query->whereHas('vehicle', function ($q) use ($vendorId) {
                    $q->where('vendor_id', $vendorId);
                })
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month);
            })->sum('vendor_total_amount');

            return [
                'name' => $date->format('M'),
                'total' => $monthlyRevenue,
            ];
        });

        return $revenueData->reverse()->values()->all();
    }
}
