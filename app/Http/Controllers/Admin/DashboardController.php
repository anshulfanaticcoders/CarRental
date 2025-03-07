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
        $activeBookings = Booking::count();
        $totalRevenue = BookingPayment::sum('amount');

        // Revenue in the last hour
        $lastHourRevenue = BookingPayment::where('created_at', '>=', Carbon::now()->subHour())->sum('amount');
        $previousHourRevenue = BookingPayment::whereBetween('created_at', [Carbon::now()->subHours(2), Carbon::now()->subHour()])->sum('amount');

        // Calculate revenue growth since the last hour
        $revenueGrowth = $this->calculateGrowthPercentage($lastHourRevenue, $previousHourRevenue);

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
            'paymentOverview' => $this->getPaymentOverview(),
            'totalCompletedPayments' => $this->getTotalPayments(BookingPayment::STATUS_SUCCEEDED),
            'totalCancelledPayments' => $this->getTotalPayments(BookingPayment::STATUS_FAILED),
            'paymentGrowthPercentage' => $this->calculatePaymentGrowth(),
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
        return collect(range(1, 12))->map(function ($month) {
            $date = Carbon::create()->month($month);

            return [
                'name' => $date->format('M'),
                'completed' => Booking::where('booking_status', 'completed')->whereYear('created_at', now()->year)->whereMonth('created_at', $month)->count(),
                'confirmed' => Booking::where('booking_status', 'confirmed')->whereYear('created_at', now()->year)->whereMonth('created_at', $month)->count(),
                'pending' => Booking::where('booking_status', 'pending')->whereYear('created_at', now()->year)->whereMonth('created_at', $month)->count(),
                'cancelled' => Booking::where('booking_status', 'cancelled')->whereYear('created_at', now()->year)->whereMonth('created_at', $month)->count(),
            ];
        })->values()->all();
    }

    protected function getRevenueData()
    {
        return collect(range(0, 11))->map(function ($monthsAgo) {
            $date = now()->subMonths($monthsAgo);

            return [
                'name' => $date->format('M'),
                'total' => BookingPayment::whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->sum('amount'),
                'bookings' => Booking::where('booking_status', 'completed')->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->count(),
            ];
        })->reverse()->values()->all();
    }

    protected function getRecentSales()
    {
        $recentSales = Booking::with(['customer', 'vehicle'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $currentMonthSales = Booking::whereYear('created_at', Carbon::now()->year)
            ->whereMonth('created_at', Carbon::now()->month)
            ->count();

        $formattedSales = $recentSales->map(function ($booking) {
            return [
                'booking_number' => $booking->booking_number,
                'customer_name' => optional($booking->customer)->first_name . ' ' . optional($booking->customer)->last_name,
                'vehicle' => optional($booking->vehicle)->name,
                'total_amount' => $booking->total_amount,
                'payment_status' => optional($booking->payment)->status ?? 'Pending',
                'created_at' => $booking->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return [
            'recentSales' => $formattedSales,
            'currentMonthSales' => $currentMonthSales,
        ];
    }


    protected function getPaymentOverview()
    {
        return collect(range(1, 12))->map(function ($month) {
            $date = Carbon::create()->month($month);

            return [
                'name' => $date->format('M'),
                'completed' => BookingPayment::where('payment_status', BookingPayment::STATUS_SUCCEEDED)
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', $month)
                    ->sum('amount'),
                'pending' => BookingPayment::where('payment_status', BookingPayment::STATUS_PENDING)
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', $month)
                    ->sum('amount'),
                'failed' => BookingPayment::where('payment_status', BookingPayment::STATUS_FAILED)
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', $month)
                    ->sum('amount'),
            ];
        })->values()->all();
    }


    protected function getTotalPayments($status)
    {
        return BookingPayment::where('payment_status', $status)
            ->whereYear('created_at', now()->year)
            ->sum('amount');
    }

    protected function calculatePaymentGrowth()
    {
        $currentMonthPayments = BookingPayment::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('amount');

        $previousMonthPayments = BookingPayment::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->sum('amount');

        if ($previousMonthPayments == 0) {
            return $currentMonthPayments > 0 ? 100 : 0;
        }

        return round((($currentMonthPayments - $previousMonthPayments) / $previousMonthPayments) * 100, 2);
    }

}
