<?php

namespace App\Helpers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogHelper
{
    public static function logActivity($activityType, $activityDescription, $logable, Request $request = null)
    {
        ActivityLog::create([
            'user_id' => auth()->id(), // Log the authenticated user
            'activity_type' => $activityType,
            'activity_description' => $activityDescription,
            'logable_id' => $logable->id,
            'logable_type' => get_class($logable),
            'ip_address' => $request ? $request->ip() : null,
            'user_agent' => $request ? $request->header('User-Agent') : null,
        ]);
    }
}