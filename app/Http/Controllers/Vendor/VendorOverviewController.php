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

        // Get payments for this vendor's bookings (same as Vendor Payments page)
        $payments = BookingPayment::with(['booking.customer', 'booking.vehicle'])
            ->join('bookings', 'booking_payments.booking_id', '=', 'bookings.id')
            ->join('vehicles', 'bookings.vehicle_id', '=', 'vehicles.id')
            ->where('vehicles.vendor_id', $vendorId)
            ->select('booking_payments.*')
            ->orderBy('booking_payments.created_at', 'desc')
            ->get();

        // Debug log
        \Log::info('Vendor Overview Debug', [
            'vendor_id' => $vendorId,
            'payments_count' => $payments->count(),
            'payments_data' => $payments->take(3)->toArray() // Log first 3 payments
        ]);

        // Transform payments to booking details structure
        $bookings = $payments->map(function ($payment) {
            $booking = $payment->booking;

            // Use vendor profile currency as fallback if booking_currency is null
            $currency = $booking->booking_currency ?? $booking->vehicle?->vendorProfile?->currency ?? 'USD';

            return [
                'id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'customer' => [
                    'first_name' => $booking->customer?->first_name ?? 'N/A',
                    'last_name' => $booking->customer?->last_name ?? 'N/A',
                ],
                'vehicle' => [
                    'brand' => $booking->vehicle?->brand ?? 'N/A',
                    'model' => $booking->vehicle?->model ?? 'N/A',
                ],
                'booking_currency' => $currency,
                'amount_paid' => (float) ($booking->amount_paid ?? 0),
                'total_amount' => (float) ($booking->total_amount ?? 0),
                'pending_amount' => (float) ($booking->pending_amount ?? 0),
                'booking_status' => $booking->booking_status ?? 'unknown',
                'transaction_id' => $payment->transaction_id,
                'payment_method' => $payment->payment_method,
                'payment_status' => $payment->payment_status,
                'payment_date' => $payment->created_at,
            ];
        })->filter(function ($booking) {
            // Only include bookings that have some amount paid
            return $booking['amount_paid'] > 0;
        })->unique('id'); // Remove duplicates if multiple payments per booking

        // Calculate currency breakdown
        $currencyBreakdown = $bookings
            ->groupBy('booking_currency')
            ->map(function ($bookingsByCurrency, $currency) {
                return $bookingsByCurrency->sum('amount_paid');
            })
            ->toArray();

        return response()->json([
            'bookings' => $bookings->values()->toArray(), // Re-index array
            'currencyBreakdown' => $currencyBreakdown,
        ]);
    }

    /**
     * Debug method to check vendor's payment data
     */
    public function debugVendorPayments(Request $request)
    {
        $vendorId = auth()->id();

        // Check if vendor has any vehicles
        $vehicles = Vehicle::where('vendor_id', $vendorId)->get(['id', 'brand', 'model']);

        // Check if vendor has any bookings
        $bookings = Booking::whereHas('vehicle', function ($query) use ($vendorId) {
            $query->where('vendor_id', $vendorId);
        })->get(['id', 'booking_number', 'amount_paid', 'total_amount', 'booking_currency']);

        // Check if vendor has any payments
        $payments = BookingPayment::with(['booking'])
            ->join('bookings', 'booking_payments.booking_id', '=', 'bookings.id')
            ->join('vehicles', 'bookings.vehicle_id', '=', 'vehicles.id')
            ->where('vehicles.vendor_id', $vendorId)
            ->select('booking_payments.*')
            ->get(['booking_payments.id', 'booking_payments.amount', 'booking_payments.booking_id']);

        return response()->json([
            'vendor_id' => $vendorId,
            'vehicles_count' => $vehicles->count(),
            'bookings_count' => $bookings->count(),
            'payments_count' => $payments->count(),
            'vehicles' => $vehicles,
            'bookings' => $bookings,
            'payments' => $payments->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'booking_id' => $payment->booking_id,
                    'booking_number' => $payment->booking?->booking_number,
                    'booking_amount_paid' => $payment->booking?->amount_paid,
                ];
            }),
        ]);
    }
}
