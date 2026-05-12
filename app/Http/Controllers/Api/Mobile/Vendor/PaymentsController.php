<?php

namespace App\Http\Controllers\Api\Mobile\Vendor;

use App\Http\Controllers\Controller;
use App\Models\BookingPayment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $vendorId = $request->user()->id;
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);
        $limit = $validated['limit'] ?? 100;

        $rows = BookingPayment::with(['booking.vehicle', 'booking.customer', 'booking.amounts'])
            ->join('bookings', 'booking_payments.booking_id', '=', 'bookings.id')
            ->join('vehicles', 'bookings.vehicle_id', '=', 'vehicles.id')
            ->where('vehicles.vendor_id', $vendorId)
            ->select('booking_payments.*')
            ->orderByDesc('booking_payments.created_at')
            ->limit($limit)
            ->get();

        $items = $rows->map(function (BookingPayment $p) {
            $b = $p->booking;
            $amount = $b?->amounts?->first();
            $customer = $b?->customer;
            return [
                'id' => $p->id,
                'transaction_id' => $p->transaction_id,
                'method' => $p->payment_method,
                'status' => $p->payment_status,
                'amount' => $p->amount_paid !== null ? (float) $p->amount_paid : null,
                'currency' => $p->currency ?? $b?->booking_currency ?? 'EUR',
                'vendor_total' => $amount?->vendor_total_amount !== null ? (float) $amount->vendor_total_amount : null,
                'vendor_currency' => $amount?->vendor_currency,
                'platform_commission' => $amount?->platform_commission !== null ? (float) $amount->platform_commission : null,
                'created_at' => $p->created_at?->toIso8601String(),
                'booking' => $b ? [
                    'id' => $b->id,
                    'booking_number' => $b->booking_number,
                    'booking_status' => $b->booking_status,
                    'pickup_date' => $b->pickup_date,
                ] : null,
                'vehicle' => $b?->vehicle ? [
                    'id' => $b->vehicle->id,
                    'brand' => $b->vehicle->brand,
                    'model' => $b->vehicle->model,
                ] : null,
                'customer_name' => $customer ? trim(($customer->first_name ?? '').' '.($customer->last_name ?? '')) : null,
            ];
        });

        $totalAll = (float) $rows->sum(fn ($p) => (float) ($p->booking?->amounts?->first()?->vendor_total_amount ?? 0));
        $thisMonthStart = Carbon::now()->startOfMonth();
        $thisMonth = (float) $rows
            ->filter(fn ($p) => $p->created_at && $p->created_at->greaterThanOrEqualTo($thisMonthStart))
            ->sum(fn ($p) => (float) ($p->booking?->amounts?->first()?->vendor_total_amount ?? 0));

        $vendorCurrency = $rows->first()?->booking?->amounts?->first()?->vendor_currency
            ?? $request->user()->profile?->currency
            ?? 'EUR';

        return response()->json([
            'payments' => $items->values(),
            'summary' => [
                'total_earnings' => $totalAll,
                'month_earnings' => $thisMonth,
                'total_count' => $rows->count(),
                'currency' => $vendorCurrency,
            ],
        ]);
    }
}
