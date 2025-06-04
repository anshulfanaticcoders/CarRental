<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityLogsController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        
        $logs = ActivityLog::with('user')
            ->when($search, function ($query, $search) {
                return $query->where(function ($query) use ($search) {
                    $query->where('activity_type', 'like', "%{$search}%")
                        ->orWhere('activity_description', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(8);

        return Inertia::render('AdminDashboardPages/ActivityLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['search']),
        ]);
    }
}