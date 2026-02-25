<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingAmount;
use App\Models\BookingPayment;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Booking;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
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

        // Get counts for Customers
        $totalCustomers = User::where('role', 'customer')->count();
        $activeCustomers = User::where('role', 'customer')->where('status', 'active')->count();
        $inactiveCustomers = User::where('role', 'customer')->where('status', 'inactive')->count();
        $suspendedCustomers = User::where('role', 'customer')->where('status', 'suspended')->count();
        $userStatusOverview = $this->getUserStatusOverview();
        $customerGrowthPercentage = $this->calculateGrowthPercentage(
            User::where('role', 'customer')->whereBetween('created_at', [$startDate, $endDate])->count(),
            User::where('role', 'customer')->where('created_at', '<', $startDate)->count()
        );

        // Get counts for Vendors
        $totalVendors = User::where('role', 'vendor')->count();
        $activeVendors = User::where('role', 'vendor')->where('status', 'active')->count();
        $inactiveVendors = User::where('role', 'vendor')->where('status', 'inactive')->count();
        $suspendedVendors = User::where('role', 'vendor')->where('status', 'suspended')->count();
        $vendorStatusOverview = $this->getVendorStatusOverview();
        $vendorGrowthPercentage = $this->calculateGrowthPercentage(
            User::where('role', 'vendor')->whereBetween('created_at', [$startDate, $endDate])->count(),
            User::where('role', 'vendor')->where('created_at', '<', $startDate)->count()
        );

        // Get counts for Vehicles
        $totalVehicles = Vehicle::count();
        $vehicleGrowthPercentage = $this->calculateGrowthPercentage(
            Vehicle::whereBetween('created_at', [$startDate, $endDate])->count(),
            Vehicle::where('created_at', '<', $startDate)->count()
        );
        $activeVehicles = Vehicle::where('status', 'available')->count();
        $rentedVehicles = Vehicle::where('status', 'rented')->count();
        $maintenanceVehicles = Vehicle::where('status', 'maintenance')->count();
        $vehicleStatusOverview = $this->getVehicleStatusOverview();

        // Get counts for Bookings
        $totalBookings = Booking::count();
        $activeBookings = Booking::where('booking_status', 'confirmed')->count();
        $bookingGrowthPercentage = $this->calculateGrowthPercentage(
            Booking::where('booking_status', 'confirmed')->whereBetween('created_at', [$startDate, $endDate])->count(),
            Booking::where('booking_status', 'confirmed')->where('created_at', '<', $startDate)->count()
        );
        $pendingBookings = Booking::where('booking_status', 'pending')->count();
        $completedBookings = Booking::where('booking_status', 'completed')->count();
        $cancelledBookings = Booking::where('booking_status', 'cancelled')->count();

        // Admin currency (always EUR from config)
        $adminCurrency = strtoupper(config('currency.base_currency', 'EUR'));

        // Total Revenue: Use admin_total_amount from booking_amounts (admin's actual earnings in EUR)
        $totalRevenue = BookingAmount::sum('admin_total_amount');
        $revenueGrowth = $this->calculateGrowthPercentage(
            BookingAmount::whereHas('booking', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            })->sum('admin_total_amount'),
            BookingAmount::whereHas('booking', function ($q) use ($startDate) {
                $q->where('created_at', '<', $startDate);
            })->sum('admin_total_amount')
        );

        $bookingOverview = $this->getBookingOverview($startDate, $endDate);
        $revenueData = $this->getRevenueData($startDate, $endDate);
        $recentSalesData = $this->getRecentSales($startDate, $endDate);
        $paymentOverview = $this->getPaymentOverview($startDate, $endDate);
        $totalCompletedPayments = $this->getTotalPayments(BookingPayment::STATUS_SUCCEEDED, $startDate, $endDate);
        $totalCancelledPayments = $this->getTotalPayments(BookingPayment::STATUS_FAILED, $startDate, $endDate);
        $paymentGrowthPercentage = $this->calculatePaymentGrowth($startDate, $endDate);

        $tableData = $this->getTableData($startDate, $endDate);
        $vehicleTableData = $this->getVehicleTableData($request);
        $userTableData = $this->getUserTableData($request);
        $vendorTableData = $this->getVendorTableData($request);

        return Inertia::render('AdminDashboardPages/Overview/Index', [
            'totalCustomers' => $totalCustomers,
            'activeCustomers' => $activeCustomers,
            'inactiveCustomers' => $inactiveCustomers,
            'suspendedCustomers' => $suspendedCustomers,
            'userStatusOverview' => $userStatusOverview,
            'customerGrowthPercentage' => $customerGrowthPercentage,
            'totalVendors' => $totalVendors,
            'activeVendors' => $activeVendors,
            'inactiveVendors' => $inactiveVendors,
            'suspendedVendors' => $suspendedVendors,
            'vendorStatusOverview' => $vendorStatusOverview,
            'vendorGrowthPercentage' => $vendorGrowthPercentage,
            'totalVehicles' => $totalVehicles,
            'vehicleGrowthPercentage' => $vehicleGrowthPercentage,
            'activeVehicles' => $activeVehicles,
            'rentedVehicles' => $rentedVehicles,
            'maintenanceVehicles' => $maintenanceVehicles,
            'vehicleStatusOverview' => $vehicleStatusOverview,
            'totalBookings' => $totalBookings,
            'activeBookings' => $activeBookings,
            'bookingGrowthPercentage' => $bookingGrowthPercentage,
            'pendingBookings' => $pendingBookings,
            'completedBookings' => $completedBookings,
            'cancelledBookings' => $cancelledBookings,
            'totalRevenue' => $totalRevenue,
            'revenueGrowth' => $revenueGrowth,
            'bookingOverview' => $bookingOverview,
            'revenueData' => $revenueData,
            'recentSales' => $recentSalesData['recentSales'],
            'currentMonthSales' => $recentSalesData['currentMonthSales'],
            'paymentOverview' => $paymentOverview,
            'totalCompletedPayments' => $totalCompletedPayments,
            'totalCancelledPayments' => $totalCancelledPayments,
            'paymentGrowthPercentage' => $paymentGrowthPercentage,
            'tableData' => $tableData,
            'vehicleTableData' => $vehicleTableData,
            'userTableData' => $userTableData,
            'vendorTableData' => $vendorTableData,
            'dateRange' => $dateRange,
            'adminCurrency' => $adminCurrency,
        ]);
    }

    private function calculateGrowthPercentage($current, $previous)
    {
        if ($previous > 0) {
            return round((($current - $previous) / $previous) * 100, 1);
        }
        return $current > 0 ? 100 : 0;
    }

    protected function getBookingOverview(Carbon $startDate, Carbon $endDate)
    {
        return collect(range(0, $startDate->diffInMonths($endDate)))->map(function ($month) use ($startDate) {
            $date = $startDate->copy()->addMonths($month);

            return [
                'name' => $date->format('M'),
                'completed' => Booking::where('booking_status', 'completed')->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->count(),
                'confirmed' => Booking::where('booking_status', 'confirmed')->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->count(),
                'pending' => Booking::where('booking_status', 'pending')->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->count(),
                'cancelled' => Booking::where('booking_status', 'cancelled')->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->count(),
            ];
        })->values()->all();
    }

    protected function getRevenueData(Carbon $startDate, Carbon $endDate)
    {
        return collect(range(0, $startDate->diffInMonths($endDate)))->map(function ($monthsAgo) use ($startDate) {
            $date = $startDate->copy()->addMonths($monthsAgo);

            return [
                'name' => $date->format('M'),
                'total' => BookingAmount::whereHas('booking', function ($q) use ($date) {
                    $q->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month);
                })->sum('admin_total_amount'),
                'bookings' => Booking::where('booking_status', 'completed')->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->count(),
            ];
        })->values()->all();
    }

    protected function getRecentSales(Carbon $startDate, Carbon $endDate)
    {
        $recentSales = Booking::with(['customer', 'vehicle', 'amounts'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $currentMonthSales = Booking::whereBetween('created_at', [$startDate, $endDate])->count();

        $formattedSales = $recentSales->map(function ($booking) {
            return [
                'booking_number' => $booking->booking_number,
                'customer_name' => optional($booking->customer)->first_name . ' ' . optional($booking->customer)->last_name,
                'vehicle' => optional($booking->vehicle)->name,
                'total_amount' => $booking->amounts->admin_total_amount ?? $booking->total_amount,
                'currency' => $booking->amounts->admin_currency ?? 'EUR',
                'payment_status' => optional($booking->payment)->status ?? 'Pending',
                'created_at' => $booking->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return [
            'recentSales' => $formattedSales,
            'currentMonthSales' => $currentMonthSales,
        ];
    }


    protected function getPaymentOverview(Carbon $startDate, Carbon $endDate)
    {
        return collect(range(0, $startDate->diffInMonths($endDate)))->map(function ($month) use ($startDate) {
            $date = $startDate->copy()->addMonths($month);

            return [
                'name' => $date->format('M'),
                'completed' => BookingAmount::whereHas('booking', function ($q) use ($date) {
                    $q->where('payment_status', 'succeeded')
                      ->whereYear('created_at', $date->year)
                      ->whereMonth('created_at', $date->month);
                })->sum('admin_paid_amount'),
                'pending' => BookingAmount::whereHas('booking', function ($q) use ($date) {
                    $q->whereIn('payment_status', ['pending', 'partial'])
                      ->whereYear('created_at', $date->year)
                      ->whereMonth('created_at', $date->month);
                })->sum('admin_pending_amount'),
                'failed' => BookingPayment::where('payment_status', BookingPayment::STATUS_FAILED)
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('amount'),
            ];
        })->values()->all();
    }


    protected function getTotalPayments($status, Carbon $startDate, Carbon $endDate)
    {
        if ($status === BookingPayment::STATUS_SUCCEEDED) {
            return BookingAmount::whereHas('booking', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            })->sum('admin_paid_amount');
        }

        // For failed payments, keep using BookingPayment (no booking_amounts record exists)
        return BookingPayment::where('payment_status', $status)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');
    }

    protected function calculatePaymentGrowth(Carbon $startDate, Carbon $endDate)
    {
        $currentPayments = BookingAmount::whereHas('booking', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('created_at', [$startDate, $endDate]);
        })->sum('admin_total_amount');

        $previousPayments = BookingAmount::whereHas('booking', function ($q) use ($startDate) {
            $q->where('created_at', '<', $startDate);
        })->sum('admin_total_amount');

        if ($previousPayments == 0) {
            return $currentPayments > 0 ? 100 : 0;
        }

        return round((($currentPayments - $previousPayments) / $previousPayments) * 100, 2);
    }

    public function getTableData(Carbon $startDate, Carbon $endDate)
    {
        return Booking::with(['customer', 'vehicle.vendor', 'amounts'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->paginate(10)
            ->through(function ($booking) {
                $vehicle = $booking->vehicle;
                $vendor = optional($vehicle)->vendor;
                $customer = $booking->customer;
                $adminCurrency = $booking->amounts->admin_currency ?? 'EUR';
                $adminAmount = $booking->amounts->admin_total_amount ?? $booking->total_amount;

                return [
                    'booking_id' => $booking->id,
                    'customer_name' => optional($customer)->first_name . ' ' . optional($customer)->last_name,
                    'vendor_name' => $vendor ? trim($vendor->first_name . ' ' . $vendor->last_name) : 'N/A',
                    'vehicle' => optional($vehicle)->brand . ' ' . optional($vehicle)->model,
                    'start_date' => $booking->pickup_date,
                    'end_date' => $booking->return_date,
                    'total_amount' => $adminCurrency . ' ' . number_format((float) $adminAmount, 2),
                    'status' => $booking->booking_status,
                ];
            });
    }

    public function getVehicleTableData(Request $request)
    {
        return Vehicle::with('vendor')
            ->paginate(10, ['*'], 'vehicle_page')
            ->through(function ($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'vendor_name' => $vehicle->vendor ? trim($vehicle->vendor->first_name . ' ' . $vehicle->vendor->last_name) : 'N/A',
                    'location' => $vehicle->location,
                    'country' => $vehicle->country,
                    'company_name' => optional($vehicle->vendorProfileData)->company_name,
                    'status' => $vehicle->status,
                ];
            });
    }

    public function getVehicleStatusOverview()
    {
        return [
            [
                'name' => 'Status',
                'available' => Vehicle::where('status', 'available')->count(),
                'rented' => Vehicle::where('status', 'rented')->count(),
                'maintenance' => Vehicle::where('status', 'maintenance')->count(),
            ]
        ];
    }

    public function getUserTableData(Request $request)
    {
        return User::where('role', 'customer')
            ->with('profile')
            ->paginate(10, ['*'], 'user_page')
            ->through(function ($user) {
                $profile = $user->profile;
                $locationParts = [];
                if ($profile) {
                    if ($profile->city) $locationParts[] = $profile->city;
                    if ($profile->country) $locationParts[] = $profile->country;
                }
                $location = !empty($locationParts) ? implode(', ', $locationParts) : 'N/A';

                return [
                    'id' => $user->id,
                    'name' => trim($user->first_name . ' ' . $user->last_name),
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'location' => $location,
                    'status' => $user->status,
                    'joined_at' => $user->created_at->format('Y-m-d'),
                ];
            });
    }

    protected function getUserStatusOverview()
    {
        return [
            [
                'name' => 'Status',
                'active' => User::where('role', 'customer')->where('status', 'active')->count(),
                'inactive' => User::where('role', 'customer')->where('status', 'inactive')->count(),
                'suspended' => User::where('role', 'customer')->where('status', 'suspended')->count(),
            ]
        ];
    }

    protected function getVendorStatusOverview()
    {
        return [
            [
                'name' => 'Status',
                'active' => User::where('role', 'vendor')->where('status', 'active')->count(),
                'inactive' => User::where('role', 'vendor')->where('status', 'inactive')->count(),
                'suspended' => User::where('role', 'vendor')->where('status', 'suspended')->count(),
            ]
        ];
    }

    public function getVendorTableData(Request $request)
    {
        return User::where('role', 'vendor')
            ->with('vendorProfile')
            ->paginate(10, ['*'], 'vendor_page')
            ->through(function ($vendor) {
                return [
                    'id' => $vendor->id,
                    'name' => trim($vendor->first_name . ' ' . $vendor->last_name),
                    'email' => $vendor->email,
                    'company_name' => optional($vendor->vendorProfile)->company_name ?? 'N/A',
                    'status' => $vendor->status,
                    'joined_at' => $vendor->created_at->format('Y-m-d'),
                ];
            });
    }
}
