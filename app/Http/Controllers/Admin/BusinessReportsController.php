<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingPayment;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Vehicle;
use Carbon\Carbon;
use Inertia\Inertia;
use Stripe\Stripe;
use Stripe\PaymentIntent;

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

        // Total Revenue calculations
        $totalRevenue = BookingPayment::where('payment_status', BookingPayment::STATUS_SUCCEEDED)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');
        $previousPeriodRevenue = BookingPayment::where('payment_status', BookingPayment::STATUS_SUCCEEDED)
            ->whereBetween('created_at', [$previousStartDate, $previousEndDate])
            ->sum('amount');
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
        $businessReportTableData = $this->getBusinessReportTableData($request, $startDate, $endDate);

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
        ]);
    }

    private function getMonthlyData(Carbon $startDate, Carbon $endDate)
    {
        $data = [];
        $currentDate = $startDate->copy()->startOfMonth();
        while ($currentDate <= $endDate) {
            $monthEndDate = $currentDate->copy()->endOfMonth();
            $revenue = BookingPayment::where('payment_status', BookingPayment::STATUS_SUCCEEDED)->whereBetween('created_at', [$currentDate, $monthEndDate])->sum('amount');
            $bookings = Booking::whereBetween('created_at', [$currentDate, $monthEndDate])->count();
            $fleetUtilization = $this->calculateFleetUtilization($currentDate, 'month');
            $data[] = ['name' => $currentDate->format('M Y'), 'revenue' => $revenue, 'bookings' => $bookings, 'fleetUtilization' => $fleetUtilization];
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
            $revenue = BookingPayment::where('payment_status', BookingPayment::STATUS_SUCCEEDED)->whereBetween('created_at', [$currentDate, $weekEndDate])->sum('amount');
            $bookings = Booking::whereBetween('created_at', [$currentDate, $weekEndDate])->count();
            $fleetUtilization = $this->calculateFleetUtilization($currentDate, 'week');
            $data[] = ['name' => 'Week ' . $currentDate->weekOfYear, 'revenue' => $revenue, 'bookings' => $bookings, 'fleetUtilization' => $fleetUtilization];
            $currentDate->addWeek();
        }
        return $data;
    }

    private function getDailyData(Carbon $startDate, Carbon $endDate)
    {
        $data = [];
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $revenue = BookingPayment::where('payment_status', BookingPayment::STATUS_SUCCEEDED)->whereDate('created_at', $currentDate)->sum('amount');
            $bookings = Booking::whereDate('created_at', $currentDate)->count();
            $fleetUtilization = $this->calculateFleetUtilization($currentDate, 'day');
            $data[] = ['name' => $currentDate->format('M d, Y'), 'revenue' => $revenue, 'bookings' => $bookings, 'fleetUtilization' => $fleetUtilization];
            $currentDate->addDay();
        }
        return $data;
    }

    private function getDataLocationWise(Carbon $startDate, Carbon $endDate)
    {
        return Booking::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('pickup_location, COUNT(*) as bookings, SUM(total_amount) as revenue')
            ->groupBy('pickup_location')
            ->get()
            ->map(function ($data) use ($startDate, $endDate) {
                return [
                    'name' => $data->pickup_location,
                    'revenue' => $data->revenue ?? 0,
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
        } else { // day
            $query->whereDate('updated_at', $date);
        }
        $inUseVehicles = $query->count();
            
        return round(($inUseVehicles / $totalVehicles) * 100);
    }

    private function getBusinessReportTableData(Request $request, Carbon $startDate, Carbon $endDate)
    {
        return Booking::with(['customer', 'vehicle.vendor', 'payments'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->paginate(10, ['*'], 'page')
            ->through(function ($booking) {
                $payment = $booking->payments->first();
                $currencySymbol = '$'; // Default currency symbol

                if ($payment && $payment->payment_method === 'stripe' && $payment->transaction_id) {
                    try {
                        Stripe::setApiKey(config('services.stripe.secret'));
                        $paymentIntent = PaymentIntent::retrieve($payment->transaction_id);
                        $currencyCode = strtolower($paymentIntent->currency);
                        
                        $currencyMap = ['usd' => '$', 'eur' => '€', 'gbp' => '£', 'inr' => '₹'];
                        $currencySymbol = $currencyMap[$currencyCode] ?? strtoupper($currencyCode);

                    } catch (\Exception $e) {
                        // Log error or handle, for now, we use the default.
                    }
                }

                return [
                    'id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'customer_name' => optional($booking->customer)->first_name . ' ' . optional($booking->customer)->last_name,
                    'vendor_name' => optional(optional($booking->vehicle)->vendor)->first_name . ' ' . optional(optional($booking->vehicle)->vendor)->last_name,
                    'vehicle' => optional($booking->vehicle)->brand . ' ' . optional($booking->vehicle)->model,
                    'total_amount' => $currencySymbol . ' ' . number_format($booking->total_amount, 2),
                    'payment_status' => $booking->payment_status ?? 'pending',
                    'booking_date' => $booking->created_at->format('Y-m-d'),
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
