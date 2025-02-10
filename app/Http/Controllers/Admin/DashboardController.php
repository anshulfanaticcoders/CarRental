<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingPayment;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Booking;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Current and previous month
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        $previousYear = Carbon::now()->subMonth()->year;
        $previousMonth = Carbon::now()->subMonth()->month;

        // Get counts for Customers
        $currentMonthCustomers = User::where('role', 'customer')->whereYear('created_at', $currentYear)->whereMonth('created_at', $currentMonth)->count();
        $previousMonthCustomers = User::where('role', 'customer')->whereYear('created_at', $previousYear)->whereMonth('created_at', $previousMonth)->count();
        $customerGrowthPercentage = $this->calculateGrowthPercentage($currentMonthCustomers, $previousMonthCustomers);

        // Get counts for Vendors
        $currentMonthVendors = User::where('role', 'vendor')->whereYear('created_at', $currentYear)->whereMonth('created_at', $currentMonth)->count();
        $previousMonthVendors = User::where('role', 'vendor')->whereYear('created_at', $previousYear)->whereMonth('created_at', $previousMonth)->count();
        $vendorGrowthPercentage = $this->calculateGrowthPercentage($currentMonthVendors, $previousMonthVendors);

        // Get counts for Vehicles
        $currentMonthVehicles = Vehicle::whereYear('created_at', $currentYear)->whereMonth('created_at', $currentMonth)->count();
        $previousMonthVehicles = Vehicle::whereYear('created_at', $previousYear)->whereMonth('created_at', $previousMonth)->count();
        $vehicleGrowthPercentage = $this->calculateGrowthPercentage($currentMonthVehicles, $previousMonthVehicles);

        // Get counts for Bookings
        $currentMonthBookings = Booking::whereYear('created_at', $currentYear)->whereMonth('created_at', $currentMonth)->count();
        $previousMonthBookings = Booking::whereYear('created_at', $previousYear)->whereMonth('created_at', $previousMonth)->count();
        $bookingGrowthPercentage = $this->calculateGrowthPercentage($currentMonthBookings, $previousMonthBookings);

        // Total counts
        $totalCustomers = User::where('role', 'customer')->count();
        $totalVendors = User::where('role', 'vendor')->count();
        $totalVehicles = Vehicle::count();
        //$activeBookings = Booking::where('status', 'active')->count();
        $activeBookings = Booking::count();
        $totalRevenue = Booking::sum('total_amount');

        // Revenue in the last hour
        $lastHourRevenue = Booking::where('created_at', '>=', Carbon::now()->subHour())->sum('total_amount');
        $previousHourRevenue = Booking::whereBetween('created_at', [Carbon::now()->subHours(2), Carbon::now()->subHour()])->sum('total_amount');

        // Calculate revenue growth since the last hour
        $revenueGrowth = $lastHourRevenue - $previousHourRevenue;

        $bookingOverview = $this->getBookingOverview();
        $revenueData = $this->getRevenueData();
        $recentSalesData = $this->getRecentSales();

        return Inertia::render('AdminDashboardPages/Overview/Index', [
            'totalCustomers' => $totalCustomers,
            'customerGrowthPercentage' => $customerGrowthPercentage,
            'totalVendors' => $totalVendors,
            'vendorGrowthPercentage' => $vendorGrowthPercentage,
            'totalVehicles' => $totalVehicles,
            'vehicleGrowthPercentage' => $vehicleGrowthPercentage,
            'activeBookings' => $activeBookings,
            'bookingGrowthPercentage' => $bookingGrowthPercentage,
            'totalRevenue' => $totalRevenue,
            'revenueGrowth' => $revenueGrowth,
            'bookingOverview' => $bookingOverview,
            'revenueData' => $revenueData,
            'recentSales' => $recentSalesData['recentSales'],
            'currentMonthSales' => $recentSalesData['currentMonthSales'],
        ]);
    }
    private function calculateGrowthPercentage($current, $previous)
    {
        if ($previous > 0) {
            return round((($current - $previous) / $previous) * 100, 1);
        }
        return $current > 0 ? 100 : 0;
    }

    protected function getBookingOverview()
    {
        $months = collect(range(1, 12))->map(function($month) {
            $date = Carbon::create()->month($month);
            
            // Get completed bookings
            $completedCount = Booking::where('booking_status', 'completed')
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', $month)
                ->count();
                
            // Get confirmed bookings
            $confirmedCount = Booking::where('booking_status', 'confirmed')
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', $month)
                ->count();
                
            // Get pending bookings
            $pendingCount = Booking::where('booking_status', 'pending')
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', $month)
                ->count();
            // Get pending bookings
            $cancelledCount = Booking::where('booking_status', 'cancelled')
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', $month)
                ->count();

            return [
                'name' => $date->format('M'),
                'completed' => $completedCount,
                'confirmed' => $confirmedCount,
                'pending' => $pendingCount,
                'cancelled' => $cancelledCount,
                'total' => $completedCount + $confirmedCount + $pendingCount + $cancelledCount
            ];
        });

        return $months->values()->all();
    }
    protected function getRevenueData()
    {
        // Get last 12 months of revenue data
        $revenueData = collect(range(0, 11))->map(function($monthsAgo) {
            $date = now()->subMonths($monthsAgo);
            
            $monthlyRevenue = BookingPayment::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('amount');
                
            $completedBookings = Booking::where('booking_status', 'completed')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();

            return [
                'name' => $date->format('M'),
                'total' => $monthlyRevenue,
                'bookings' => $completedBookings
            ];
        });

        return $revenueData->reverse()->values()->all();
    }
    protected function getRecentSales()
    {
        // Get the 10 most recent bookings with customer and vehicle details
        $recentSales = Booking::with(['customer', 'vehicle'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Calculate the number of sales for the current month
        $currentMonthSales = Booking::whereYear('created_at', Carbon::now()->year)
            ->whereMonth('created_at', Carbon::now()->month)
            ->count();

        // Format data for the recent sales table
        $formattedSales = $recentSales->map(function ($booking) {
            return [
                'booking_number' => $booking->booking_number,
                'customer_name' => $booking->customer->first_name . ' ' . $booking->customer->last_name,
                'vehicle' => $booking->vehicle->name,
                'total_amount' => $booking->total_amount,
                'created_at' => $booking->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return [
            'recentSales' => $formattedSales,
            'currentMonthSales' => $currentMonthSales, // Add this to the response
        ];
    }
}
