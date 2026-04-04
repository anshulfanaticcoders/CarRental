<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiBooking;
use App\Models\ApiConsumer;
use App\Models\ApiLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApiAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        [$period, $from, $to, $prevFrom, $prevTo, $bucket] = $this->parseDateRange($request);

        $kpis = $this->computeKpis($from, $to, $prevFrom, $prevTo);
        $trafficSeries = $this->trafficSeries($from, $to, $bucket);
        $bookingsSeries = $this->bookingsSeries($from, $to, $bucket);
        $revenueSeries = $this->revenueSeries($from, $to, $bucket);
        $endpointPopularity = $this->endpointPopularity($from, $to);
        $errorBreakdown = $this->errorBreakdown($from, $to);
        $responseTimeByEndpoint = $this->responseTimeByEndpoint($from, $to);
        $planDistribution = $this->planDistribution();
        $topConsumers = $this->topConsumers($from, $to);

        return Inertia::render('AdminDashboardPages/ApiConsumers/Analytics', [
            'period' => $period,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'kpis' => $kpis,
            'trafficSeries' => $trafficSeries,
            'bookingsSeries' => $bookingsSeries,
            'revenueSeries' => $revenueSeries,
            'endpointPopularity' => $endpointPopularity,
            'errorBreakdown' => $errorBreakdown,
            'responseTimeByEndpoint' => $responseTimeByEndpoint,
            'planDistribution' => $planDistribution,
            'topConsumers' => $topConsumers,
        ]);
    }

    private function parseDateRange(Request $request): array
    {
        $period = $request->input('period', '30d');
        $now = Carbon::now();

        if ($request->filled('from') && $request->filled('to')) {
            $from = Carbon::parse($request->input('from'))->startOfDay();
            $to = Carbon::parse($request->input('to'))->endOfDay();
            $days = $from->diffInDays($to);
            $prevTo = $from->copy()->subSecond();
            $prevFrom = $prevTo->copy()->subDays($days)->startOfDay();
            $period = 'custom';
            $bucket = 'day';

            return [$period, $from, $to, $prevFrom, $prevTo, $bucket];
        }

        switch ($period) {
            case 'today':
                $from = $now->copy()->startOfDay();
                $to = $now->copy()->endOfDay();
                $prevFrom = $now->copy()->subDay()->startOfDay();
                $prevTo = $now->copy()->subDay()->endOfDay();
                $bucket = 'hour';
                break;
            case '7d':
                $from = $now->copy()->subDays(6)->startOfDay();
                $to = $now->copy()->endOfDay();
                $prevFrom = $from->copy()->subDays(7)->startOfDay();
                $prevTo = $from->copy()->subSecond();
                $bucket = 'day';
                break;
            case '90d':
                $from = $now->copy()->subDays(89)->startOfDay();
                $to = $now->copy()->endOfDay();
                $prevFrom = $from->copy()->subDays(90)->startOfDay();
                $prevTo = $from->copy()->subSecond();
                $bucket = 'day';
                break;
            default: // 30d
                $period = '30d';
                $from = $now->copy()->subDays(29)->startOfDay();
                $to = $now->copy()->endOfDay();
                $prevFrom = $from->copy()->subDays(30)->startOfDay();
                $prevTo = $from->copy()->subSecond();
                $bucket = 'day';
                break;
        }

        return [$period, $from, $to, $prevFrom, $prevTo, $bucket];
    }

    private function computeKpis(Carbon $from, Carbon $to, Carbon $prevFrom, Carbon $prevTo): array
    {
        $curLogs = ApiLog::whereBetween('created_at', [$from, $to]);
        $prevLogs = ApiLog::whereBetween('created_at', [$prevFrom, $prevTo]);

        $totalRequests = $curLogs->count();
        $prevTotalRequests = $prevLogs->count();

        $curErrors = ApiLog::whereBetween('created_at', [$from, $to])->where('response_status', '>=', 400)->count();
        $prevErrors = ApiLog::whereBetween('created_at', [$prevFrom, $prevTo])->where('response_status', '>=', 400)->count();

        $errorRate = $totalRequests > 0 ? round(($curErrors / $totalRequests) * 100, 2) : 0;
        $prevErrorRate = $prevTotalRequests > 0 ? round(($prevErrors / $prevTotalRequests) * 100, 2) : 0;

        $avgLatency = round((float) ApiLog::whereBetween('created_at', [$from, $to])->avg('processing_time_ms'), 1);
        $prevAvgLatency = round((float) ApiLog::whereBetween('created_at', [$prevFrom, $prevTo])->avg('processing_time_ms'), 1);

        $curBookings = ApiBooking::where('is_test', false)->whereBetween('created_at', [$from, $to]);
        $prevBookings = ApiBooking::where('is_test', false)->whereBetween('created_at', [$prevFrom, $prevTo]);

        $totalBookings = $curBookings->count();
        $prevTotalBookings = $prevBookings->count();

        $totalRevenue = round((float) ApiBooking::where('is_test', false)->whereBetween('created_at', [$from, $to])->sum('total_amount'), 2);
        $prevTotalRevenue = round((float) ApiBooking::where('is_test', false)->whereBetween('created_at', [$prevFrom, $prevTo])->sum('total_amount'), 2);

        return [
            'total_requests' => ['value' => $totalRequests, 'delta' => $this->delta($totalRequests, $prevTotalRequests)],
            'total_bookings' => ['value' => $totalBookings, 'delta' => $this->delta($totalBookings, $prevTotalBookings)],
            'total_revenue' => ['value' => $totalRevenue, 'delta' => $this->delta($totalRevenue, $prevTotalRevenue)],
            'error_rate' => ['value' => $errorRate, 'delta' => $this->delta($errorRate, $prevErrorRate)],
            'avg_latency' => ['value' => $avgLatency, 'delta' => $this->delta($avgLatency, $prevAvgLatency)],
        ];
    }

    private function delta(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function bucketExpression(string $bucket): string
    {
        if ($bucket === 'hour') {
            return "DATE_FORMAT(created_at, '%Y-%m-%d %H:00')";
        }

        return 'DATE(created_at)';
    }

    private function trafficSeries(Carbon $from, Carbon $to, string $bucket): array
    {
        $expr = $this->bucketExpression($bucket);

        $rows = ApiLog::selectRaw("{$expr} as bucket, COUNT(*) as cnt")
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        return $rows->map(fn ($r) => ['x' => $r->bucket, 'y' => (int) $r->cnt])->values()->all();
    }

    private function bookingsSeries(Carbon $from, Carbon $to, string $bucket): array
    {
        $expr = $this->bucketExpression($bucket);

        $rows = ApiBooking::selectRaw("{$expr} as bucket, status, COUNT(*) as cnt")
            ->where('is_test', false)
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('bucket', 'status')
            ->orderBy('bucket')
            ->get();

        $grouped = [];
        foreach ($rows as $r) {
            $grouped[$r->status][] = ['x' => $r->bucket, 'y' => (int) $r->cnt];
        }

        $series = [];
        foreach ($grouped as $status => $data) {
            $series[] = ['name' => ucfirst($status), 'data' => $data];
        }

        return $series;
    }

    private function revenueSeries(Carbon $from, Carbon $to, string $bucket): array
    {
        $expr = $this->bucketExpression($bucket);

        $rows = ApiBooking::selectRaw("{$expr} as bucket, SUM(total_amount) as total")
            ->where('is_test', false)
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        return $rows->map(fn ($r) => ['x' => $r->bucket, 'y' => round((float) $r->total, 2)])->values()->all();
    }

    private function endpointPopularity(Carbon $from, Carbon $to): array
    {
        return ApiLog::selectRaw('endpoint, COUNT(*) as count')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('endpoint')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function errorBreakdown(Carbon $from, Carbon $to): array
    {
        return ApiLog::selectRaw("endpoint, COUNT(*) as total_errors, SUM(CASE WHEN response_status BETWEEN 400 AND 499 THEN 1 ELSE 0 END) as client_errors, SUM(CASE WHEN response_status >= 500 THEN 1 ELSE 0 END) as server_errors")
            ->whereBetween('created_at', [$from, $to])
            ->where('response_status', '>=', 400)
            ->groupBy('endpoint')
            ->orderByDesc('total_errors')
            ->get()
            ->map(fn ($r) => [
                'endpoint' => $r->endpoint,
                'total_errors' => (int) $r->total_errors,
                'client_errors' => (int) $r->client_errors,
                'server_errors' => (int) $r->server_errors,
            ])
            ->all();
    }

    private function responseTimeByEndpoint(Carbon $from, Carbon $to): array
    {
        $endpoints = ApiLog::selectRaw('endpoint, AVG(processing_time_ms) as avg_ms, COUNT(*) as cnt')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('endpoint')
            ->orderByDesc('cnt')
            ->limit(10)
            ->get();

        return $endpoints->map(function ($row) use ($from, $to) {
            $avg = round((float) $row->avg_ms, 1);
            $p95 = $avg; // fallback

            if ($row->cnt >= 20) {
                $offset = (int) floor($row->cnt * 0.95) - 1;
                $p95Row = ApiLog::where('endpoint', $row->endpoint)
                    ->whereBetween('created_at', [$from, $to])
                    ->orderBy('processing_time_ms')
                    ->offset($offset)
                    ->limit(1)
                    ->value('processing_time_ms');

                if ($p95Row !== null) {
                    $p95 = round((float) $p95Row, 1);
                }
            }

            return [
                'endpoint' => $row->endpoint,
                'avg' => $avg,
                'p95' => $p95,
            ];
        })->values()->all();
    }

    private function planDistribution(): array
    {
        return ApiConsumer::where('status', 'active')
            ->selectRaw('plan, COUNT(*) as count')
            ->groupBy('plan')
            ->get()
            ->toArray();
    }

    private function topConsumers(Carbon $from, Carbon $to): array
    {
        $logStats = ApiLog::selectRaw('api_consumer_id, COUNT(*) as requests, AVG(processing_time_ms) as avg_latency, SUM(CASE WHEN response_status >= 400 THEN 1 ELSE 0 END) as errors, MAX(created_at) as last_active')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('api_consumer_id')
            ->get()
            ->keyBy('api_consumer_id');

        $bookingStats = ApiBooking::selectRaw('api_consumer_id, COUNT(*) as bookings, SUM(total_amount) as revenue')
            ->where('is_test', false)
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('api_consumer_id')
            ->get()
            ->keyBy('api_consumer_id');

        $consumerIds = $logStats->keys()->merge($bookingStats->keys())->unique();
        $consumers = ApiConsumer::whereIn('id', $consumerIds)->get()->keyBy('id');

        $result = [];
        foreach ($consumerIds as $id) {
            $consumer = $consumers->get($id);
            if (!$consumer) {
                continue;
            }

            $log = $logStats->get($id);
            $booking = $bookingStats->get($id);
            $requests = $log ? (int) $log->requests : 0;
            $errors = $log ? (int) $log->errors : 0;

            $result[] = [
                'id' => $id,
                'name' => $consumer->name,
                'plan' => $consumer->plan,
                'mode' => $consumer->mode,
                'requests' => $requests,
                'avg_latency' => $log ? round((float) $log->avg_latency, 1) : 0,
                'error_rate' => $requests > 0 ? round(($errors / $requests) * 100, 1) : 0,
                'bookings' => $booking ? (int) $booking->bookings : 0,
                'revenue' => $booking ? round((float) $booking->revenue, 2) : 0,
                'last_active' => $log ? $log->last_active : null,
            ];
        }

        usort($result, fn ($a, $b) => $b['requests'] <=> $a['requests']);

        return array_slice($result, 0, 10);
    }
}
