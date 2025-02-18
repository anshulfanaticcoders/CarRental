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
    public function index()
    {
        // Basic metrics calculations
        $totalVendors = User::where('role', 'vendor')->count();
        $previousMonthVendors = User::where('role', 'vendor')
            ->whereBetween('created_at', [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()])
            ->count();
        $totalVendorsGrowth = $this->calculateGrowthPercentage($totalVendors, $previousMonthVendors);

        // Active vendors metrics
        $activeVendors = User::where('role', 'vendor')
            ->where('last_login_at', '>=', Carbon::now()->subDays(30))
            ->count();
        $previousMonthActiveVendors = User::where('role', 'vendor')
            ->whereBetween('last_login_at', [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()])
            ->count();
        $activeVendorsGrowth = $this->calculateGrowthPercentage($activeVendors, $previousMonthActiveVendors);

        // New vendors metrics
        $currentWeekVendors = User::where('role', 'vendor')
            ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->count();
        $previousWeekVendors = User::where('role', 'vendor')
            ->whereBetween('created_at', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()])
            ->count();
        $newVendorsGrowthPercentage = $this->calculateGrowthPercentage($currentWeekVendors, $previousWeekVendors);

        // Vehicle status metrics
        $vehicleStatusData = $this->getVehicleStatusData();
        
        // Monthly data for charts
        $monthlyData = $this->getMonthlyData();
        
        // Recent activities
        $recentActivities = ActivityLog::with('user')
            ->whereHas('user', function ($query) { 
                $query->where('role', 'vendor');
            })
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return Inertia::render('AdminDashboardPages/VendorsReports/Index', [
            'totalVendors' => $totalVendors,
            'totalVendorsGrowth' => $totalVendorsGrowth,
            'activeVendors' => $activeVendors,
            'activeVendorsGrowth' => $activeVendorsGrowth,
            'newVendors' => $currentWeekVendors,
            'newVendorsGrowth' => $newVendorsGrowthPercentage,
            'monthlyData' => $monthlyData,
            'recentActivities' => $recentActivities,
            'vehicleStatusData' => $vehicleStatusData
        ]);
    }

    private function getVehicleStatusData()
    {
        $totalVehicles = Vehicle::count();
        
        // Get vehicles by status
        $activeVehicles = Vehicle::where('status', 'available')
            ->with(['category', 'user', 'images'])
            ->get();
            
        $rentedVehicles = Vehicle::where('status', 'rented')
            ->with(['category', 'user', 'images'])
            ->get();
            
        $maintenanceVehicles = Vehicle::where('status', 'maintenance')
            ->with(['category', 'user', 'images'])
            ->get();

        // Calculate percentages
        $activePercentage = $totalVehicles > 0 ? ($activeVehicles->count() / $totalVehicles) * 100 : 0;
        $rentedPercentage = $totalVehicles > 0 ? ($rentedVehicles->count() / $totalVehicles) * 100 : 0;
        $maintenancePercentage = $totalVehicles > 0 ? ($maintenanceVehicles->count() / $totalVehicles) * 100 : 0;

        // Get monthly vehicle status data
        $monthlyVehicleData = $this->getMonthlyVehicleData();

        return [
            'total' => $totalVehicles,
            'active' => [
                'count' => $activeVehicles->count(),
                'percentage' => round($activePercentage, 1),
                'vehicles' => $activeVehicles
            ],
            'rented' => [
                'count' => $rentedVehicles->count(),
                'percentage' => round($rentedPercentage, 1),
                'vehicles' => $rentedVehicles
            ],
            'maintenance' => [
                'count' => $maintenanceVehicles->count(),
                'percentage' => round($maintenancePercentage, 1),
                'vehicles' => $maintenanceVehicles
            ],
            'monthlyData' => $monthlyVehicleData
        ];
    }

    private function getMonthlyVehicleData()
    {
        return collect(range(0, 11))->map(function($month) {
            $date = Carbon::now()->subMonths($month);
            
            $activeCount = Vehicle::where('status', 'available')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
                
            $rentedCount = Vehicle::where('status', 'rented')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
                
            $maintenanceCount = Vehicle::where('status', 'maintenance')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();

            return [
                'name' => $date->format('M Y'),
                'active' => $activeCount,
                'rented' => $rentedCount,
                'maintenance' => $maintenanceCount
            ];
        })->reverse()->values();
    }

    private function getMonthlyData()
    {
        $months = collect(range(0, 11))->map(function($month) {
            $date = Carbon::now()->subMonths($month);
            
            $totalUsers = User::where('role', 'vendor')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
                
            $activeUsers = User::where('role', 'vendor')
                ->whereYear('last_login_at', $date->year)
                ->whereMonth('last_login_at', $date->month)
                ->whereDay('last_login_at', $date->day)
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