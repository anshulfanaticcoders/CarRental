<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UsersReportController extends Controller
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
        $totalCustomers = User::where('role', 'customer')->whereBetween('created_at', [$startDate, $endDate])->count();
        $previousStartDate = $startDate->copy()->sub(1, $period);
        $previousEndDate = $endDate->copy()->sub(1, $period);
        $previousPeriodCustomers = User::where('role', 'customer')
            ->whereBetween('created_at', [$previousStartDate, $previousEndDate])
            ->count();
        $totalCustomersGrowth = $this->calculateGrowthPercentage($totalCustomers, $previousPeriodCustomers);

        // Active customers metrics
        $activeCustomers = User::where('role', 'customer')
            ->whereBetween('last_login_at', [$startDate, $endDate])
            ->count();
        $previousMonthActiveCustomers = User::where('role', 'customer')
            ->whereBetween('last_login_at', [$previousStartDate, $previousEndDate])
            ->count();
        $activeCustomersGrowth = $this->calculateGrowthPercentage($activeCustomers, $previousMonthActiveCustomers);

        // New customers metrics
        $newCustomers = User::where('role', 'customer')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        $previousPeriodNewCustomers = User::where('role', 'customer')
            ->whereBetween('created_at', [$previousStartDate, $previousEndDate])
            ->count();
        $newCustomersGrowthPercentage = $this->calculateGrowthPercentage($newCustomers, $previousPeriodNewCustomers);

        // Get data for charts
        $monthlyData = $this->getMonthlyData($startDate, $endDate);
        $weeklyData = $this->getWeeklyData($startDate, $endDate);
        $dailyData = $this->getDailyData($startDate, $endDate);
        
        // Recent activities
        $recentActivities = ActivityLog::with('user:id,first_name,last_name,email')
            ->whereHas('user', function ($query) {
                $query->where('role', 'customer');
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

        $customerReportTableData = $this->getCustomerReportTableData($request, $startDate, $endDate);

        return Inertia::render('AdminDashboardPages/UsersReports/Index', [
            'totalCustomers' => $totalCustomers,
            'totalCustomersGrowth' => $totalCustomersGrowth,
            'activeCustomers' => $activeCustomers,
            'activeCustomersGrowth' => $activeCustomersGrowth,
            'newCustomers' => $newCustomers,
            'newCustomersGrowth' => $newCustomersGrowthPercentage,
            'monthlyData' => $monthlyData,
            'weeklyData' => $weeklyData,
            'dailyData' => $dailyData,
            'recentActivities' => $recentActivities,
            'customerReportTableData' => $customerReportTableData,
            'dateRange' => $dateRange,
        ]);
    }

    private function getMonthlyData(Carbon $startDate, Carbon $endDate)
    {
        $data = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $monthEndDate = $currentDate->copy()->endOfMonth();
            $totalUsers = User::where('role', 'customer')->where('created_at', '<=', $monthEndDate)->count();
            $activeUsers = User::where('role', 'customer')->whereBetween('last_login_at', [$currentDate->copy()->startOfMonth(), $monthEndDate])->count();
            $newUsers = User::where('role', 'customer')->whereBetween('created_at', [$currentDate->copy()->startOfMonth(), $monthEndDate])->count();

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
            $totalUsers = User::where('role', 'customer')->where('created_at', '<=', $weekEndDate)->count();
            $activeUsers = User::where('role', 'customer')->whereBetween('last_login_at', [$currentDate->copy()->startOfWeek(), $weekEndDate])->count();
            $newUsers = User::where('role', 'customer')->whereBetween('created_at', [$currentDate->copy()->startOfWeek(), $weekEndDate])->count();

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
            $totalUsers = User::where('role', 'customer')->whereDate('created_at', '<=', $currentDate)->count();
            $activeUsers = User::where('role', 'customer')->whereDate('last_login_at', $currentDate)->count();
            $newUsers = User::where('role', 'customer')->whereDate('created_at', $currentDate)->count();

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

    private function getCustomerReportTableData(Request $request, Carbon $startDate, Carbon $endDate)
    {
        return User::where('role', 'customer')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('profile')
            ->paginate(10, ['*'], 'page')
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
