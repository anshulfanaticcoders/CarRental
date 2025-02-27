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
    public function index()
    {
        // Basic metrics calculations
        $totalCustomers = User::where('role', 'customer')->count();
        $previousMonthCustomers = User::where('role', 'customer')
            ->whereBetween('created_at', [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()])
            ->count();
        $totalCustomersGrowth = $this->calculateGrowthPercentage($totalCustomers, $previousMonthCustomers);

        // Active customers metrics
        $activeCustomers = User::where('role', 'customer')
            ->where('last_login_at', '>=', Carbon::now()->subDays(30))
            ->count();
        $previousMonthActiveCustomers = User::where('role', 'customer')
            ->whereBetween('last_login_at', [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()])
            ->count();
        $activeCustomersGrowth = $this->calculateGrowthPercentage($activeCustomers, $previousMonthActiveCustomers);

        // New customers metrics
        $currentWeekCustomers = User::where('role', 'customer')
            ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->count();
        $previousWeekCustomers = User::where('role', 'customer')
            ->whereBetween('created_at', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()])
            ->count();
        $newCustomersGrowthPercentage = $this->calculateGrowthPercentage($currentWeekCustomers, $previousWeekCustomers);

        // Get data for charts
        $monthlyData = $this->getMonthlyData();
        $weeklyData = $this->getWeeklyData();
        $dailyData = $this->getDailyData();
        
        // Recent activities
        $recentActivities = ActivityLog::with('user')
            ->whereHas('user', function ($query) { 
                $query->where('role', 'customer');
            })
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return Inertia::render('AdminDashboardPages/UsersReports/Index', [
            'totalCustomers' => $totalCustomers,
            'totalCustomersGrowth' => $totalCustomersGrowth,
            'activeCustomers' => $activeCustomers,
            'activeCustomersGrowth' => $activeCustomersGrowth,
            'newCustomers' => $currentWeekCustomers,
            'newCustomersGrowth' => $newCustomersGrowthPercentage,
            'monthlyData' => $monthlyData,
            'weeklyData' => $weeklyData,
            'dailyData' => $dailyData,
            'recentActivities' => $recentActivities,
        ]);
    }

    private function getMonthlyData()
    {
        return collect(range(0, 11))->map(function($month) {
            $date = Carbon::now()->subMonths($month);
            
            // Total users created before or during this month
            $totalUsers = User::where('role', 'customer')
                ->whereDate('created_at', '<=', $date->copy()->endOfMonth())
                ->count();
                
            // Active users who logged in during this month
            $activeUsers = User::where('role', 'customer')
                ->whereYear('last_login_at', $date->year)
                ->whereMonth('last_login_at', $date->month)
                ->count();
                
            // New users created during this month
            $newUsers = User::where('role', 'customer')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();

            return [
                'name' => $date->format('M Y'),
                'total' => $totalUsers,
                'active' => $activeUsers,
                'new' => $newUsers
            ];
        })->reverse()->values();
    }
    
    private function getWeeklyData()
    {
        return collect(range(0, 11))->map(function($week) {
            $date = Carbon::now()->subWeeks($week);
            
            // Total users created before or during this week
            $totalUsers = User::where('role', 'customer')
                ->whereDate('created_at', '<=', $date->copy()->endOfWeek())
                ->count();
                
            // Active users who logged in during this week
            $activeUsers = User::where('role', 'customer')
                ->whereYear('last_login_at', $date->year)
                ->whereRaw('WEEK(last_login_at, 1) = ?', [$date->weekOfYear])
                ->count();
                
            // New users created during this week
            $newUsers = User::where('role', 'customer')
                ->whereYear('created_at', $date->year)
                ->whereRaw('WEEK(created_at, 1) = ?', [$date->weekOfYear])
                ->count();

            return [
                'name' => 'Week ' . $date->weekOfYear . ' ' . $date->format('Y'),
                'total' => $totalUsers,
                'active' => $activeUsers,
                'new' => $newUsers
            ];
        })->reverse()->values();
    }
    
    private function getDailyData()
    {
        return collect(range(0, 11))->map(function($day) {
            $date = Carbon::now()->subDays($day);
            
            // Total users created before or during this day
            $totalUsers = User::where('role', 'customer')
                ->whereDate('created_at', '<=', $date)
                ->count();
                
            // Active users who logged in during this day
            $activeUsers = User::where('role', 'customer')
                ->whereYear('last_login_at', $date->year)
                ->whereMonth('last_login_at', $date->month)
                ->whereDay('last_login_at', $date->day)
                ->count();
                
            // New users created during this day
            $newUsers = User::where('role', 'customer')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->whereDay('created_at', $date->day)
                ->count();

            return [
                'name' => $date->format('M d, Y'),
                'total' => $totalUsers,
                'active' => $activeUsers,
                'new' => $newUsers
            ];
        })->reverse()->values();
    }

    private function calculateGrowthPercentage($current, $previous)
    {
        if ($previous > 0) {
            return round((($current - $previous) / $previous) * 100, 1);
        }
        return $current > 0 ? 100 : 0;
    }
}