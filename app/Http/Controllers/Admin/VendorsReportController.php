<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VendorsReportController extends Controller
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

        // Basic metrics calculations
        $totalVendors = User::where('role', 'vendor')->whereBetween('created_at', [$startDate, $endDate])->count();
        $previousStartDate = $startDate->copy()->sub(1, $period);
        $previousEndDate = $endDate->copy()->sub(1, $period);
        $previousPeriodVendors = User::where('role', 'vendor')
            ->whereBetween('created_at', [$previousStartDate, $previousEndDate])
            ->count();
        $totalVendorsGrowth = $this->calculateGrowthPercentage($totalVendors, $previousPeriodVendors);

        // Active vendors metrics
        $activeVendors = User::where('role', 'vendor')
            ->whereBetween('last_login_at', [$startDate, $endDate])
            ->count();
        $previousMonthActiveVendors = User::where('role', 'vendor')
            ->whereBetween('last_login_at', [$previousStartDate, $previousEndDate])
            ->count();
        $activeVendorsGrowth = $this->calculateGrowthPercentage($activeVendors, $previousMonthActiveVendors);

        // New vendors metrics
        $newVendors = User::where('role', 'vendor')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        $previousPeriodNewVendors = User::where('role', 'vendor')
            ->whereBetween('created_at', [$previousStartDate, $previousEndDate])
            ->count();
        $newVendorsGrowthPercentage = $this->calculateGrowthPercentage($newVendors, $previousPeriodNewVendors);

        // Get data for charts
        $monthlyData = $this->getMonthlyData($startDate, $endDate);
        $weeklyData = $this->getWeeklyData($startDate, $endDate);
        $dailyData = $this->getDailyData($startDate, $endDate);
        
        // Recent activities
        $recentActivities = ActivityLog::with('user:id,first_name,last_name,email')
            ->whereHas('user', function ($query) { 
                $query->where('role', 'vendor');
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'user' => $activity->user,
                    'activity_description' => $activity->activity_description,
                    'created_at_formatted' => $activity->created_at->diffForHumans(),
                ];
            });

        $vendorReportTableData = $this->getVendorReportTableData($request, $startDate, $endDate);

        return Inertia::render('AdminDashboardPages/VendorsReports/Index', [
            'totalVendors' => $totalVendors,
            'totalVendorsGrowth' => $totalVendorsGrowth,
            'activeVendors' => $activeVendors,
            'activeVendorsGrowth' => $activeVendorsGrowth,
            'newVendors' => $newVendors,
            'newVendorsGrowth' => $newVendorsGrowthPercentage,
            'monthlyData' => $monthlyData,
            'weeklyData' => $weeklyData,
            'dailyData' => $dailyData,
            'recentActivities' => $recentActivities,
            'vendorReportTableData' => $vendorReportTableData,
            'dateRange' => $dateRange,
        ]);
    }

    private function getMonthlyData(Carbon $startDate, Carbon $endDate)
    {
        $data = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $monthEndDate = $currentDate->copy()->endOfMonth();
            $totalUsers = User::where('role', 'vendor')->where('created_at', '<=', $monthEndDate)->count();
            $activeUsers = User::where('role', 'vendor')->whereBetween('last_login_at', [$currentDate->copy()->startOfMonth(), $monthEndDate])->count();
            $newUsers = User::where('role', 'vendor')->whereBetween('created_at', [$currentDate->copy()->startOfMonth(), $monthEndDate])->count();

            $data[] = [
                'name' => $currentDate->format('M Y'),
                'total' => $totalUsers,
                'active' => $activeUsers,
                'new' => $newUsers,
            ];
            $currentDate->addMonth();
        }
        return $data;
    }
    
    private function getWeeklyData(Carbon $startDate, Carbon $endDate)
    {
        $data = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $weekEndDate = $currentDate->copy()->endOfWeek();
            $totalUsers = User::where('role', 'vendor')->where('created_at', '<=', $weekEndDate)->count();
            $activeUsers = User::where('role', 'vendor')->whereBetween('last_login_at', [$currentDate->copy()->startOfWeek(), $weekEndDate])->count();
            $newUsers = User::where('role', 'vendor')->whereBetween('created_at', [$currentDate->copy()->startOfWeek(), $weekEndDate])->count();

            $data[] = [
                'name' => 'Week ' . $currentDate->weekOfYear,
                'total' => $totalUsers,
                'active' => $activeUsers,
                'new' => $newUsers,
            ];
            $currentDate->addWeek();
        }
        return $data;
    }
    
    private function getDailyData(Carbon $startDate, Carbon $endDate)
    {
        $data = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $totalUsers = User::where('role', 'vendor')->whereDate('created_at', '<=', $currentDate)->count();
            $activeUsers = User::where('role', 'vendor')->whereDate('last_login_at', $currentDate)->count();
            $newUsers = User::where('role', 'vendor')->whereDate('created_at', $currentDate)->count();

            $data[] = [
                'name' => $currentDate->format('M d, Y'),
                'total' => $totalUsers,
                'active' => $activeUsers,
                'new' => $newUsers,
            ];
            $currentDate->addDay();
        }
        return $data;
    }

    private function getVendorReportTableData(Request $request, Carbon $startDate, Carbon $endDate)
    {
        return User::where('role', 'vendor')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('vendorProfile')
            ->paginate(10, ['*'], 'page')
            ->through(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => trim($user->first_name . ' ' . $user->last_name),
                    'email' => $user->email,
                    'company_name' => optional($user->vendorProfile)->company_name ?? 'N/A',
                    'status' => $user->status,
                    'joined_at' => $user->created_at->format('Y-m-d H:i:s'),
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
