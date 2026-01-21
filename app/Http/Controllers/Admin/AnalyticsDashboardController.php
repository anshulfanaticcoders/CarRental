<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingAmount;
use App\Models\BookingPayment;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnalyticsDashboardController extends Controller
{
    public function index(Request $request)
    {
        $currency = config('currency.default', 'EUR');
        $range = $request->get('range', 'week');
        [$start, $end] = $this->resolveDateRange($range, $request->get('start_date'), $request->get('end_date'));

        $periodDays = $start->diffInDays($end) + 1;
        $previousStart = (clone $start)->subDays($periodDays);
        $previousEnd = (clone $start)->subDay()->endOfDay();

        $bookingCounts = $this->getBookingsByDate($start, $end);
        $paymentCounts = $this->getPaymentsByDate($start, $end, $currency);
        $revenueByDate = $this->getRevenueByDate($start, $end, $currency);

        $trendSeries = $this->buildTrendSeries($start, $end, $bookingCounts, $paymentCounts, $revenueByDate);

        $currentBookingTotal = Booking::whereBetween('created_at', [$start, $end])->count();
        $previousBookingTotal = Booking::whereBetween('created_at', [$previousStart, $previousEnd])->count();

        $currentRevenueTotal = $this->sumRevenue($start, $end, $currency);
        $previousRevenueTotal = $this->sumRevenue($previousStart, $previousEnd, $currency);

        $bookingStatusCounts = $this->getBookingStatusCounts($start, $end);
        $paymentStatusCounts = $this->getPaymentStatusCounts($start, $end, $currency);

        $totalPayments = array_sum($paymentStatusCounts);
        $successRate = $totalPayments > 0
            ? round(($paymentStatusCounts['succeeded'] / $totalPayments) * 100, 1)
            : 0;

        return Inertia::render('AdminDashboardPages/Analytics/Index', [
            'filters' => [
                'range' => $range,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
            ],
            'currency' => $currency,
            'metrics' => [
                'total_bookings' => $currentBookingTotal,
                'booking_growth' => $this->calculateGrowth($currentBookingTotal, $previousBookingTotal),
                'total_revenue' => $currentRevenueTotal,
                'revenue_growth' => $this->calculateGrowth($currentRevenueTotal, $previousRevenueTotal),
                'paid_revenue' => $revenueByDate['totals']['paid'],
                'pending_revenue' => $revenueByDate['totals']['pending'],
                'total_payments' => $totalPayments,
                'success_rate' => $successRate,
            ],
            'trends' => [
                'series' => $trendSeries,
            ],
            'breakdowns' => [
                'bookings' => $bookingStatusCounts,
                'payments' => $paymentStatusCounts,
            ],
        ]);
    }

    private function resolveDateRange(string $range, ?string $startInput, ?string $endInput): array
    {
        $today = Carbon::now();

        if ($range === 'day') {
            return [$today->copy()->startOfDay(), $today->copy()->endOfDay()];
        }

        if ($range === 'month') {
            return [$today->copy()->subDays(29)->startOfDay(), $today->copy()->endOfDay()];
        }

        if ($range === 'custom' && $startInput && $endInput) {
            try {
                $start = Carbon::parse($startInput)->startOfDay();
                $end = Carbon::parse($endInput)->endOfDay();
                if ($start->greaterThan($end)) {
                    [$start, $end] = [$end, $start];
                }
                return [$start, $end];
            } catch (\Throwable $e) {
                return [$today->copy()->subDays(6)->startOfDay(), $today->copy()->endOfDay()];
            }
        }

        return [$today->copy()->subDays(6)->startOfDay(), $today->copy()->endOfDay()];
    }

    private function getBookingsByDate(Carbon $start, Carbon $end): array
    {
        return Booking::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();
    }

    private function getPaymentsByDate(Carbon $start, Carbon $end, string $currency): array
    {
        return BookingPayment::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('booking.amounts', function ($query) use ($currency) {
                $query->where('admin_currency', $currency);
            })
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();
    }

    private function getRevenueByDate(Carbon $start, Carbon $end, string $currency): array
    {
        $rows = BookingAmount::query()
            ->join('bookings', 'bookings.id', '=', 'booking_amounts.booking_id')
            ->whereBetween('bookings.created_at', [$start, $end])
            ->where('booking_amounts.admin_currency', $currency)
            ->selectRaw('DATE(bookings.created_at) as date')
            ->selectRaw('SUM(booking_amounts.admin_total_amount) as total')
            ->selectRaw('SUM(booking_amounts.admin_paid_amount) as paid')
            ->selectRaw('SUM(booking_amounts.admin_pending_amount) as pending')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $totals = [
            'total' => 0,
            'paid' => 0,
            'pending' => 0,
        ];

        $mapped = [];
        foreach ($rows as $row) {
            $mapped[$row->date] = [
                'total' => (float) $row->total,
                'paid' => (float) $row->paid,
                'pending' => (float) $row->pending,
            ];
            $totals['total'] += (float) $row->total;
            $totals['paid'] += (float) $row->paid;
            $totals['pending'] += (float) $row->pending;
        }

        return [
            'rows' => $mapped,
            'totals' => $totals,
        ];
    }

    private function buildTrendSeries(Carbon $start, Carbon $end, array $bookings, array $payments, array $revenue): array
    {
        $period = CarbonPeriod::create($start, $end);
        $series = [];
        foreach ($period as $date) {
            $key = $date->toDateString();
            $series[] = [
                'date' => $date->format('M d'),
                'bookings' => (int) ($bookings[$key] ?? 0),
                'payments' => (int) ($payments[$key] ?? 0),
                'revenue_total' => (float) ($revenue['rows'][$key]['total'] ?? 0),
                'revenue_paid' => (float) ($revenue['rows'][$key]['paid'] ?? 0),
                'revenue_pending' => (float) ($revenue['rows'][$key]['pending'] ?? 0),
            ];
        }

        return $series;
    }

    private function getBookingStatusCounts(Carbon $start, Carbon $end): array
    {
        $counts = Booking::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('booking_status, COUNT(*) as total')
            ->groupBy('booking_status')
            ->pluck('total', 'booking_status')
            ->toArray();

        return [
            'pending' => (int) ($counts['pending'] ?? 0),
            'confirmed' => (int) ($counts['confirmed'] ?? 0),
            'completed' => (int) ($counts['completed'] ?? 0),
            'cancelled' => (int) ($counts['cancelled'] ?? 0),
        ];
    }

    private function getPaymentStatusCounts(Carbon $start, Carbon $end, string $currency): array
    {
        $counts = BookingPayment::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('booking.amounts', function ($query) use ($currency) {
                $query->where('admin_currency', $currency);
            })
            ->selectRaw('payment_status, COUNT(*) as total')
            ->groupBy('payment_status')
            ->pluck('total', 'payment_status')
            ->toArray();

        return [
            'succeeded' => (int) ($counts['succeeded'] ?? 0),
            'pending' => (int) ($counts['pending'] ?? 0),
            'failed' => (int) ($counts['failed'] ?? 0),
        ];
    }

    private function sumRevenue(Carbon $start, Carbon $end, string $currency): float
    {
        return (float) BookingAmount::query()
            ->join('bookings', 'bookings.id', '=', 'booking_amounts.booking_id')
            ->whereBetween('bookings.created_at', [$start, $end])
            ->where('booking_amounts.admin_currency', $currency)
            ->sum('booking_amounts.admin_total_amount');
    }

    private function calculateGrowth(float $current, float $previous): float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
