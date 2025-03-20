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
    $payments = BookingPayment::with('booking')
        ->when($request->search, function ($query, $search) {
            $query->where('transaction_id', 'like', "%{$search}%")
                ->orWhere('payment_method', 'like', "%{$search}%")
                ->orWhere('amount', 'like', "%{$search}%")
                ->orWhere('payment_status', 'like', "%{$search}%");
        })
        ->when($request->status, function ($query, $status) {
            $query->where('payment_status', $status);
        })
        ->when($request->sort, function ($query, $sort) {
            $direction = $request->order ?? 'desc';
            $query->orderBy($sort, $direction);
        }, function ($query) {
            $query->latest();
        })
        ->paginate(7)
        ->withQueryString();

    // Fix: Change STATUS_COMPLETED to 'succeeded' to match your database value
    $stats = [
        'total_payments' => BookingPayment::count(),
        'successful_payments' => BookingPayment::where('payment_status', 'succeeded')->count(),
        'pending_payments' => BookingPayment::where('payment_status', 'pending')->count(),
        'failed_payments' => BookingPayment::where('payment_status', 'failed')->count(),
        // Fix: Change STATUS_COMPLETED to 'succeeded' to match your database value
        'total_amount' => BookingPayment::where('payment_status', 'succeeded')
            ->sum('amount'),
    ];

    return Inertia::render('AdminDashboardPages/Payments/Index', [
        'payments' => [
            'data' => $payments->items(),
            'current_page' => $payments->currentPage(),
            'last_page' => $payments->lastPage(),
            'per_page' => $payments->perPage(),
        ],
        'stats' => $stats,
        'filters' => $request->only(['search', 'status', 'sort', 'order']),
    ]);
}
}
