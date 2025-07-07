<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingPayment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentDashboardController extends Controller
{
    public function index(Request $request)
    {
        $paymentsQuery = BookingPayment::with('booking.vehicle.vendorProfile')
            ->when($request->search, function ($query, $search) {
                $query->where('transaction_id', 'like', "%{$search}%")
                    ->orWhere('payment_method', 'like', "%{$search}%")
                    ->orWhere('amount', 'like', "%{$search}%")
                    ->orWhere('payment_status', 'like', "%{$search}%");
            })
            ->when($request->status, function ($query, $status) {
                $query->where('payment_status', $status);
            })
            ->when($request->currency && $request->currency !== 'all', function ($query) use ($request) {
                $query->whereHas('booking.vehicle.vendorProfile', function ($subQuery) use ($request) {
                    $subQuery->where('currency', $request->currency);
                });
            })
            ->when($request->sort, function ($query, $sort) {
                $direction = $request->order ?? 'desc';
                $query->orderBy($sort, $direction);
            }, function ($query) {
                $query->latest();
            });

        $payments = $paymentsQuery->paginate(7)->withQueryString();

        $payments->getCollection()->transform(function ($payment) {
            $payment->currency = optional($payment->booking?->vehicle?->vendorProfile)->currency ?? '€'; // Default to EUR
            return $payment;
        });

        // Get all available currencies
        $availableCurrencies = BookingPayment::with('booking.vehicle.vendorProfile')
            ->get()
            ->map(function ($payment) {
                return optional($payment->booking?->vehicle?->vendorProfile)->currency ?? '€';
            })
            ->unique()
            ->filter()
            ->values();

        // Calculate stats based on selected currency
        $selectedCurrency = $request->currency ?? 'all';
        
        $statsBaseQuery = BookingPayment::query();
        if ($selectedCurrency !== 'all') {
            $statsBaseQuery->whereHas('booking.vehicle.vendorProfile', function ($query) use ($selectedCurrency) {
                $query->where('currency', $selectedCurrency);
            });
        }

        $stats = [
            'total_payments' => (clone $statsBaseQuery)->count(),
            'successful_payments' => (clone $statsBaseQuery)->where('payment_status', 'succeeded')->count(),
            'pending_payments' => (clone $statsBaseQuery)->where('payment_status', 'pending')->count(),
            'failed_payments' => (clone $statsBaseQuery)->where('payment_status', 'failed')->count(),
            'total_amount' => (clone $statsBaseQuery)->where('payment_status', 'succeeded')->sum('amount'),
            'currency_symbol' => $selectedCurrency === 'all' ? 'Mixed' : $selectedCurrency,
        ];

        return Inertia::render('AdminDashboardPages/Payments/Index', [
            'payments' => [
                'data' => $payments->items(),
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
            ],
            'stats' => $stats,
            'availableCurrencies' => $availableCurrencies,
            'filters' => $request->only(['search', 'status', 'sort', 'order', 'currency']),
        ]);
    }
}
