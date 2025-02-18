<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingPayment;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Vehicle;
use Carbon\Carbon;
use Inertia\Inertia;

class BusinessReportsController extends Controller
{
    public function index()
    {
        // Total Revenue calculations from BookingPayments
        $totalRevenue = BookingPayment::where('payment_status', BookingPayment::STATUS_SUCCEEDED)
            ->sum('amount');
        
        $previousMonthRevenue = BookingPayment::where('payment_status', BookingPayment::STATUS_SUCCEEDED)
            ->whereBetween('payment_date', 
                [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()]
            )->sum('amount');
            
        $revenueGrowth = $this->calculateGrowthPercentage($totalRevenue, $previousMonthRevenue);

        // Active Bookings metrics
        $activeBookings = Booking::where('booking_status', 'confirmed')->count();
        $yesterdayBookings = Booking::where('booking_status', 'confirmed')
            ->whereDate('created_at', Carbon::yesterday())
            ->count();
        $bookingsGrowth = $activeBookings - $yesterdayBookings;

        // Fleet Utilization metrics
        $totalVehicles = Vehicle::count();
        $inUseVehicles = Vehicle::where('status', 'available')->count();
        $fleetUtilization = $totalVehicles > 0 ? round(($inUseVehicles / $totalVehicles) * 100) : 0;
        $lastWeekUtilization = $this->getLastWeekFleetUtilization();
        $fleetUtilizationGrowth = $fleetUtilization - $lastWeekUtilization;

        // Monthly data for charts
        $monthlyData = $this->getMonthlyData();
        $weeklyData = $this->getWeeklyData();
        $dailyData = $this->getDailyData();
        $locationData = $this->getDataLocationWise();

        return Inertia::render('AdminDashboardPages/BusinessReports/Index', [
            'totalRevenue' => $totalRevenue,
            'revenueGrowth' => $revenueGrowth,
            'activeBookings' => $activeBookings,
            'bookingsGrowth' => $bookingsGrowth,
            'fleetUtilization' => $fleetUtilization,
            'fleetUtilizationGrowth' => $fleetUtilizationGrowth,
            'monthlyData' => $monthlyData,
            'weeklyData' => $weeklyData,
            'dailyData' => $dailyData,
            'locationData' => $locationData,
        ]);
    }

    private function getMonthlyData()
    {
        return collect(range(0, 11))->map(function($month) {
            $date = Carbon::now()->subMonths($month);
            
            $revenue = BookingPayment::where('payment_status', BookingPayment::STATUS_SUCCEEDED)
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('amount');
                
                
            $bookings = Booking::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();

            $fleetUtilization = $this->calculateFleetUtilization($date);

            return [
                'name' => $date->format('M Y'),
                'revenue' => $revenue,
                'bookings' => $bookings,
                'fleetUtilization' => $fleetUtilization
            ];
        })->reverse()->values();
    }

    private function calculateGrowthPercentage($current, $previous)
    {
        if ($previous > 0) {
            return round((($current - $previous) / $previous) * 100, 1);
        }
        return $current > 0 ? 100 : 0;
    }

    private function calculateFleetUtilization($date)
    {
        $totalVehicles = Vehicle::count();
        $inUseVehicles = Vehicle::where('status', 'available')
            ->whereYear('updated_at', $date->year)
            ->whereMonth('updated_at', $date->month)
            ->count();
            
        return $totalVehicles > 0 ? round(($inUseVehicles / $totalVehicles) * 100) : 0;
    }

    private function getLastWeekFleetUtilization()
    {
        $totalVehicles = Vehicle::count();
        $lastWeekInUseVehicles = Vehicle::where('status', 'available')
            ->whereBetween('updated_at', [Carbon::now()->subWeek(), Carbon::now()])
            ->count();
            
        return $totalVehicles > 0 ? round(($lastWeekInUseVehicles / $totalVehicles) * 100) : 0;
    }


    private function getWeeklyData()
{
    return collect(range(0, 11))->map(function ($week) {
        $date = Carbon::now()->subWeeks($week);

        $revenue = BookingPayment::where('payment_status', BookingPayment::STATUS_SUCCEEDED)
            ->whereYear('created_at', $date->year)
            ->whereRaw('WEEK(created_at, 1) = ?', [$date->weekOfYear])
            ->sum('amount');

        $bookings = Booking::whereYear('created_at', $date->year)
            ->whereRaw('WEEK(created_at, 1) = ?', [$date->weekOfYear])
            ->count();

        $fleetUtilization = $this->calculateFleetUtilizationWeekly($date);

        return [
            'name' => 'Week ' . $date->weekOfYear . ' ' . $date->format('Y'),
            'revenue' => $revenue,
            'bookings' => $bookings,
            'fleetUtilization' => $fleetUtilization,
        ];
    })->reverse()->values();
}

private function calculateFleetUtilizationWeekly($date)
{
    $totalVehicles = Vehicle::count();
    $inUseVehicles = Vehicle::where('status', 'available')
        ->whereYear('updated_at', $date->year)
        ->whereRaw('WEEK(updated_at, 1) = ?', [$date->weekOfYear])
        ->count();

    return $totalVehicles > 0 ? round(($inUseVehicles / $totalVehicles) * 100) : 0;
}


private function getDailyData()
{
    return collect(range(0, 11))->map(function ($day) {
        $date = Carbon::now()->subDays($day);

        $revenue = BookingPayment::where('payment_status', BookingPayment::STATUS_SUCCEEDED)
            ->whereYear('created_at', $date->year)
            ->whereMonth('created_at', $date->month)
            ->whereDay('created_at', $date->day)
            ->sum('amount');

        $bookings = Booking::whereYear('created_at', $date->year)
            ->whereMonth('created_at', $date->month)
            ->whereDay('created_at', $date->day)
            ->count();

        $fleetUtilization = $this->calculateFleetUtilizationDaily($date);

        return [
            'name' => $date->format('M d, Y'), // Example: "Feb 18, 2025"
            'revenue' => $revenue,
            'bookings' => $bookings,
            'fleetUtilization' => $fleetUtilization,
        ];
    })->reverse()->values();
}

private function calculateFleetUtilizationDaily($date)
{
    $totalVehicles = Vehicle::count();
    $inUseVehicles = Vehicle::where('status', 'available')
        ->whereYear('updated_at', $date->year)
        ->whereMonth('updated_at', $date->month)
        ->whereDay('updated_at', $date->day)
        ->count();

    return $totalVehicles > 0 ? round(($inUseVehicles / $totalVehicles) * 100) : 0;
}

private function getDataLocationWise()
{
    return collect(range(0, 11))->map(function ($monthOffset) {
        $date = Carbon::now()->subMonths($monthOffset);

        // Get revenue and booking count grouped by pickup_location for the month
        $bookingsByLocation = Booking::whereYear('created_at', $date->year)
            ->whereMonth('created_at', $date->month)
            ->selectRaw('pickup_location, COUNT(*) as bookings, SUM(total_amount) as revenue')
            ->groupBy('pickup_location')
            ->get();

        // Map data for each pickup location
        return $bookingsByLocation->map(function ($data) use ($date) {
            return [
                'name' => $date->format('M Y'), // Example: "Feb 2025"
                'pickup_location' => $data->pickup_location,
                'revenue' => $data->revenue ?? 0,
                'bookings' => $data->bookings ?? 0,
                'fleetUtilization' => $this->calculateFleetUtilizationLocationWise($date, $data->pickup_location),
            ];
        });
    })->flatten(1)->reverse()->values();
}

private function calculateFleetUtilizationLocationWise($date, $pickupLocation)
{
    $totalVehicles = Vehicle::count();
    $inUseVehicles = Booking::where('pickup_location', $pickupLocation)
        ->whereYear('pickup_date', $date->year)
        ->whereMonth('pickup_date', $date->month)
        ->count();

    return $totalVehicles > 0 ? round(($inUseVehicles / $totalVehicles) * 100) : 0;
}

}
