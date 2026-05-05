<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ActivityLogsController extends Controller
{
    private const CATEGORIES = ['user', 'vendor', 'vehicle', 'booking', 'payment', 'affiliate', 'auth', 'content', 'system'];

    public function index(Request $request)
    {
        $search = $request->query('search');
        $category = $request->query('category');
        $type = $request->query('type');

        $logs = ActivityLog::with('user')
            ->when($category && in_array($category, self::CATEGORIES, true), fn ($q) => $q->where('category', $category))
            ->when($type, fn ($q) => $q->where('activity_type', $type))
            ->when($search, function ($query, $search) {
                return $query->where(function ($query) use ($search) {
                    $query->where('activity_type', 'like', "%{$search}%")
                        ->orWhere('activity_description', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $categoryCounts = ActivityLog::select('category', DB::raw('count(*) as total'))
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();

        $totalCount = array_sum($categoryCounts);
        $categoryCounts['all'] = $totalCount;

        $availableTypes = ActivityLog::select('activity_type')
            ->when($category && in_array($category, self::CATEGORIES, true), fn ($q) => $q->where('category', $category))
            ->distinct()
            ->orderBy('activity_type')
            ->pluck('activity_type')
            ->filter()
            ->values()
            ->toArray();

        return Inertia::render('AdminDashboardPages/ActivityLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'category', 'type']),
            'categoryCounts' => $categoryCounts,
            'availableTypes' => $availableTypes,
        ]);
    }
}
