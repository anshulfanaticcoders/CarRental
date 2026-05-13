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
            'range' => ['nullable', 'string', 'in:today,week,month,quarter,custom'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $limit = $validated['limit'] ?? 100;
        $range = $validated['range'] ?? 'month';

        [$rangeStart, $rangeEnd] = $this->resolveRange($range, $validated['from'] ?? null, $validated['to'] ?? null);

        $rows = BookingPayment::with(['booking.vehicle', 'booking.customer', 'booking.amounts'])
            ->join('bookings', 'booking_payments.booking_id', '=', 'bookings.id')
            ->join('vehicles', 'bookings.vehicle_id', '=', 'vehicles.id')
            ->where('vehicles.vendor_id', $vendorId)
            ->whereBetween('booking_payments.created_at', [$rangeStart, $rangeEnd])
            ->select('booking_payments.*')
            ->orderByDesc('booking_payments.created_at')
            ->limit($limit)
            ->get();

        $items = $rows->map(function (BookingPayment $p) {
            $b = $p->booking;
            $amount = $b?->amounts;
            $customer = $b?->customer;
            return [
                'id' => $p->id,
                'transaction_id' => $p->transaction_id,
                'method' => $p->payment_method,
                'status' => $p->payment_status,
                'amount' => $p->amount !== null ? (float) $p->amount : null,
                'currency' => $p->currency ?? $b?->booking_currency ?? 'EUR',
                'vendor_total' => $amount?->vendor_total_amount !== null ? (float) $amount->vendor_total_amount : null,
                'vendor_currency' => $amount?->vendor_currency,
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

        $rangeTotal = (float) $rows->sum(fn ($p) => (float) ($p->booking?->amounts?->vendor_total_amount ?? 0));

        // Lifetime total (no range filter)
        $lifetimeTotal = (float) BookingPayment::query()
            ->join('bookings', 'booking_payments.booking_id', '=', 'bookings.id')
            ->join('vehicles', 'bookings.vehicle_id', '=', 'vehicles.id')
            ->join('booking_amounts', 'booking_amounts.booking_id', '=', 'bookings.id')
            ->where('vehicles.vendor_id', $vendorId)
            ->sum('booking_amounts.vendor_total_amount');

        // Daily series for chart (bucketed by created_at date)
        $series = [];
        $cursor = $rangeStart->copy();
        $bucketed = $rows->groupBy(fn ($p) => $p->created_at?->toDateString());
        while ($cursor->lte($rangeEnd)) {
            $key = $cursor->toDateString();
            $dayTotal = (float) ($bucketed[$key] ?? collect())
                ->sum(fn ($p) => (float) ($p->booking?->amounts?->vendor_total_amount ?? 0));
            $series[] = ['date' => $key, 'total' => round($dayTotal, 2)];
            $cursor->addDay();
        }

        $vendorCurrency = $rows->first()?->booking?->amounts?->vendor_currency
            ?? $request->user()->profile?->currency
            ?? 'EUR';

        return response()->json([
            'payments' => $items->values(),
            'summary' => [
                'total_earnings' => $lifetimeTotal,
                'range_earnings' => $rangeTotal,
                'range_count' => $rows->count(),
                'range_from' => $rangeStart->toDateString(),
                'range_to' => $rangeEnd->toDateString(),
                'range' => $range,
                'currency' => $vendorCurrency,
            ],
            'series' => $series,
        ]);
    }

    private function resolveRange(string $range, ?string $from, ?string $to): array
    {
        $now = Carbon::now();
        switch ($range) {
            case 'today':
                return [$now->copy()->startOfDay(), $now->copy()->endOfDay()];
            case 'week':
                return [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()];
            case 'quarter':
                return [$now->copy()->subDays(89)->startOfDay(), $now->copy()->endOfDay()];
            case 'custom':
                $start = $from ? Carbon::createFromFormat('Y-m-d', $from)->startOfDay() : $now->copy()->subDays(29)->startOfDay();
                $end = $to ? Carbon::createFromFormat('Y-m-d', $to)->endOfDay() : $now->copy()->endOfDay();
                if ($end->lt($start)) {
                    [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
                }
                return [$start, $end];
            case 'month':
            default:
                return [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()];
        }
    }
}
