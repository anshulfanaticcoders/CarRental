<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingAmount;
use App\Models\BookingPayment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentDashboardController extends Controller
{
    public function index(Request $request)
    {
        $currencyFilter = $request->currency ?: config('currency.default', 'EUR');

        $paymentsQuery = BookingPayment::with(['booking.amounts'])
            ->when($request->search, function ($query, $search) {
                $query->where('transaction_id', 'like', "%{$search}%")
                    ->orWhere('payment_method', 'like', "%{$search}%")
                    ->orWhere('amount', 'like', "%{$search}%")
                    ->orWhere('payment_status', 'like', "%{$search}%");
            })
            ->when($request->status, function ($query, $status) {
                $query->where('payment_status', $status);
            })
            ->when($currencyFilter && $currencyFilter !== 'all', function ($query) use ($currencyFilter) {
                $query->whereHas('booking.amounts', function ($subQuery) use ($currencyFilter) {
                    $subQuery->where('admin_currency', $currencyFilter);
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
            $payment->currency = $payment->booking?->amounts?->admin_currency
                ?? $payment->booking?->booking_currency
                ?? 'USD';
            return $payment;
        });

        // Get all available currencies from booking_currency field
        $availableCurrencies = BookingAmount::query()
            ->pluck('admin_currency')
            ->unique()
            ->filter()
            ->sort()
            ->values();

        // Calculate stats based on selected currency
        $selectedCurrency = $currencyFilter ?? 'all';

        $statsBaseQuery = BookingPayment::query();
        if ($selectedCurrency !== 'all') {
            $statsBaseQuery->whereHas('booking.amounts', function ($query) use ($selectedCurrency) {
                $query->where('admin_currency', $selectedCurrency);
            });
        }

        // For total_amount, use booking totals instead of payment amounts
        $bookingTotalQuery = BookingAmount::query();
        if ($selectedCurrency !== 'all') {
            $bookingTotalQuery->where('admin_currency', $selectedCurrency);
        }

        $stats = [
            'total_payments' => (clone $statsBaseQuery)->count(),
            'successful_payments' => (clone $statsBaseQuery)->where('payment_status', 'succeeded')->count(),
            'pending_payments' => (clone $statsBaseQuery)->where('payment_status', 'pending')->count(),
            'failed_payments' => (clone $statsBaseQuery)->where('payment_status', 'failed')->count(),
            'total_amount' => (clone $bookingTotalQuery)->sum('admin_total_amount'),
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
            'statusCounts' => [
                'total' => $stats['total_payments'],
                'successful' => $stats['successful_payments'],
                'pending' => $stats['pending_payments'],
                'failed' => $stats['failed_payments'],
            ],
            'availableCurrencies' => $availableCurrencies,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'sort' => $request->sort,
                'order' => $request->order,
                'currency' => $selectedCurrency,
            ],
            'flash' => session()->only(['success', 'error'])
        ]);
    }
}
