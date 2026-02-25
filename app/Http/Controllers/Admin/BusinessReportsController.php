<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingAmount;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Vehicle;
use Carbon\Carbon;
use Inertia\Inertia;

class BusinessReportsController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'period' => 'nullable|string|in:week,month,year',
        ]);

        $period = $request->input('period', 'year');
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

        if (!$startDate || !$endDate) {
            switch ($period) {
                case 'week':
                    $startDate = now()->startOfWeek();
                    $endDate = now()->endOfWeek();
                    break;
                case 'month':
                    $startDate = now()->startOfMonth();
                    $endDate = now()->endOfMonth();
                    break;
                case 'year':
                default:
                    $startDate = now()->startOfYear();
                    $endDate = now()->endOfYear();
                    break;
            }
        }

        $dateRange = ['start' => $startDate->format('Y-m-d'), 'end' => $endDate->format('Y-m-d')];
        $previousStartDate = $startDate->copy()->sub(1, $period);
        $previousEndDate = $endDate->copy()->sub(1, $period);
        $adminCurrency = strtoupper(config('currency.base_currency', 'EUR'));

        // Total Revenue from booking_amounts (admin amounts in EUR)
        $totalRevenue = BookingAmount::whereHas('booking', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('created_at', [$startDate, $endDate]);
        })->sum('admin_total_amount');

        $previousPeriodRevenue = BookingAmount::whereHas('booking', function ($q) use ($previousStartDate, $previousEndDate) {
            $q->whereBetween('created_at', [$previousStartDate, $previousEndDate]);
        })->sum('admin_total_amount');

        $revenueGrowth = $this->calculateGrowthPercentage($totalRevenue, $previousPeriodRevenue);

        // Active Bookings metrics
        $activeBookings = Booking::where('booking_status', 'confirmed')->whereBetween('created_at', [$startDate, $endDate])->count();
        $previousPeriodBookings = Booking::where('booking_status', 'confirmed')
            ->whereBetween('created_at', [$previousStartDate, $previousEndDate])
            ->count();
        $bookingsGrowth = $this->calculateGrowthPercentage($activeBookings, $previousPeriodBookings);

        // Fleet Utilization metrics
        $totalVehicles = Vehicle::count();
        $inUseVehicles = Vehicle::where('status', 'rented')->whereBetween('updated_at', [$startDate, $endDate])->count();
        $fleetUtilization = $totalVehicles > 0 ? round(($inUseVehicles / $totalVehicles) * 100) : 0;
        $lastPeriodInUseVehicles = Vehicle::where('status', 'rented')->whereBetween('updated_at', [$previousStartDate, $previousEndDate])->count();
        $lastPeriodFleetUtilization = $totalVehicles > 0 ? round(($lastPeriodInUseVehicles / $totalVehicles) * 100) : 0;
        $fleetUtilizationGrowth = $fleetUtilization - $lastPeriodFleetUtilization;

        // Chart and table data
        $monthlyData = $this->getMonthlyData($startDate, $endDate);
        $weeklyData = $this->getWeeklyData($startDate, $endDate);
        $dailyData = $this->getDailyData($startDate, $endDate);
        $locationData = $this->getDataLocationWise($startDate, $endDate);
        $businessReportTableData = $this->getBusinessReportTableData($startDate, $endDate, $adminCurrency);

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
            'businessReportTableData' => $businessReportTableData,
            'dateRange' => $dateRange,
            'adminCurrency' => $adminCurrency,
        ]);
    }

    private function getMonthlyData(Carbon $startDate, Carbon $endDate)
    {
        $data = [];
        $currentDate = $startDate->copy()->startOfMonth();
        while ($currentDate <= $endDate) {
            $monthEndDate = $currentDate->copy()->endOfMonth();

            $revenue = BookingAmount::whereHas('booking', function ($q) use ($currentDate, $monthEndDate) {
                $q->whereBetween('created_at', [$currentDate, $monthEndDate]);
            })->sum('admin_total_amount');

            $bookings = Booking::whereBetween('created_at', [$currentDate, $monthEndDate])->count();
            $fleetUtilization = $this->calculateFleetUtilization($currentDate, 'month');

            $data[] = [
                'name' => $currentDate->format('M Y'),
                'revenue' => round((float) $revenue, 2),
                'bookings' => $bookings,
                'fleetUtilization' => $fleetUtilization,
            ];
            $currentDate->addMonth();
        }
        return $data;
    }

    private function getWeeklyData(Carbon $startDate, Carbon $endDate)
    {
        $data = [];
        $currentDate = $startDate->copy()->startOfWeek();
        while ($currentDate <= $endDate) {
            $weekEndDate = $currentDate->copy()->endOfWeek();

            $revenue = BookingAmount::whereHas('booking', function ($q) use ($currentDate, $weekEndDate) {
                $q->whereBetween('created_at', [$currentDate, $weekEndDate]);
            })->sum('admin_total_amount');

            $bookings = Booking::whereBetween('created_at', [$currentDate, $weekEndDate])->count();
            $fleetUtilization = $this->calculateFleetUtilization($currentDate, 'week');

            $data[] = [
                'name' => 'Week ' . $currentDate->weekOfYear,
                'revenue' => round((float) $revenue, 2),
                'bookings' => $bookings,
                'fleetUtilization' => $fleetUtilization,
            ];
            $currentDate->addWeek();
        }
        return $data;
    }

    private function getDailyData(Carbon $startDate, Carbon $endDate)
    {
        $data = [];
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $revenue = BookingAmount::whereHas('booking', function ($q) use ($currentDate) {
                $q->whereDate('created_at', $currentDate);
            })->sum('admin_total_amount');

            $bookings = Booking::whereDate('created_at', $currentDate)->count();
            $fleetUtilization = $this->calculateFleetUtilization($currentDate, 'day');

            $data[] = [
                'name' => $currentDate->format('M d, Y'),
                'revenue' => round((float) $revenue, 2),
                'bookings' => $bookings,
                'fleetUtilization' => $fleetUtilization,
            ];
            $currentDate->addDay();
        }
        return $data;
    }

    private function getDataLocationWise(Carbon $startDate, Carbon $endDate)
    {
        return Booking::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('pickup_location, COUNT(*) as bookings')
            ->groupBy('pickup_location')
            ->get()
            ->map(function ($data) use ($startDate, $endDate) {
                $locationRevenue = BookingAmount::whereHas('booking', function ($q) use ($data, $startDate, $endDate) {
                    $q->where('pickup_location', $data->pickup_location)
                      ->whereBetween('created_at', [$startDate, $endDate]);
                })->sum('admin_total_amount');

                return [
                    'name' => $data->pickup_location,
                    'revenue' => round((float) $locationRevenue, 2),
                    'bookings' => $data->bookings ?? 0,
                ];
            });
    }

    private function calculateFleetUtilization(Carbon $date, $periodType)
    {
        $totalVehicles = Vehicle::count();
        if ($totalVehicles == 0) return 0;

        $query = Vehicle::where('status', 'rented');
        if ($periodType == 'month') {
            $query->whereYear('updated_at', $date->year)->whereMonth('updated_at', $date->month);
        } elseif ($periodType == 'week') {
            $query->whereBetween('updated_at', [$date->copy()->startOfWeek(), $date->copy()->endOfWeek()]);
        } else {
            $query->whereDate('updated_at', $date);
        }
        $inUseVehicles = $query->count();

        return round(($inUseVehicles / $totalVehicles) * 100);
    }

    private function getBusinessReportTableData(Carbon $startDate, Carbon $endDate, string $adminCurrency)
    {
        return Booking::with(['customer', 'vehicle.vendor', 'amounts'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->paginate(10, ['*'], 'page')
            ->through(function ($booking) use ($adminCurrency) {
                $amounts = $booking->amounts;
                $adminTotal = $amounts ? $amounts->admin_total_amount : $booking->total_amount;

                return [
                    'id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'customer_name' => trim((optional($booking->customer)->first_name ?? '') . ' ' . (optional($booking->customer)->last_name ?? '')),
                    'vendor_name' => trim((optional(optional($booking->vehicle)->vendor)->first_name ?? '') . ' ' . (optional(optional($booking->vehicle)->vendor)->last_name ?? '')),
                    'vehicle' => trim((optional($booking->vehicle)->brand ?? '') . ' ' . (optional($booking->vehicle)->model ?? '')),
                    'total_amount' => round((float) $adminTotal, 2),
                    'currency' => $adminCurrency,
                    'payment_status' => $booking->payment_status ?? 'pending',
                    'booking_status' => $booking->booking_status ?? 'pending',
                    'booking_date' => $booking->created_at->format('M d, Y'),
                ];
            });
    }

    private function calculateGrowthPercentage($current, $previous)
    {
        if ($previous > 0) {
            return round((($current - $previous) / $previous) * 100, 1);
        }
        return $current > 0 ? 100 : 0;
    }
}
