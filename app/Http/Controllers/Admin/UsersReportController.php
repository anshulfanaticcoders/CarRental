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

        // Monthly data for charts
        $monthlyData = $this->getMonthlyData();
        
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
           'recentActivities' => $recentActivities,
        ]);
    }

    private function getMonthlyData()
    {
        $months = collect(range(0, 11))->map(function($month) {
            $date = Carbon::now()->subMonths($month);
            
            $totalUsers = User::where('role', 'customer')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
                
            $activeUsers = User::where('role', 'customer')
                ->whereYear('last_login_at', $date->year)
                ->whereMonth('last_login_at', $date->month)
                ->count();

            return [
                'name' => $date->format('M Y'),
                'total' => $totalUsers,
                'active' => $activeUsers
            ];
        })->reverse()->values();

        return $months;
    }

    private function calculateGrowthPercentage($current, $previous)
    {
        if ($previous > 0) {
            return round((($current - $previous) / $previous) * 100, 1);
        }
        return $current > 0 ? 100 : 0;
    }
}