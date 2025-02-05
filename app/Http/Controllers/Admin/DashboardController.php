<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Booking;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    // public function index()
    // {
    //     // Fetch total users
    //     $totalCustomers = User::where('role', 'customer')->count();
    //     $totalVendors = User::where('role', 'vendor')->count();

    //     // Fetch total vehicles
    //     $totalVehicles = Vehicle::count();

    //     // Fetch active bookings (assuming 'active' status is stored in the bookings table)
    //     $activeBookings = Booking::where('booking_status', 'pending')->count();

    //     // Fetch total revenue (assuming revenue is stored in the bookings table)
    //     $totalRevenue = Booking::sum('total_amount');

    //     return Inertia::render('AdminDashboardPages/Dashboard/Index', [
    //         'totalUsers' => $totalCustomers,
    //         'totalVendors' => $totalVendors,
    //         'totalVehicles' => $totalVehicles,
    //         'activeBookings' => $activeBookings,
    //         'totalRevenue' => $totalRevenue,
    //     ]);
    // }


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
            'revenueGrowth' => $revenueGrowth
        ]);
    }
    private function calculateGrowthPercentage($current, $previous)
    {
        if ($previous > 0) {
            return round((($current - $previous) / $previous) * 100, 1);
        }
        return $current > 0 ? 100 : 0;
    }
}
