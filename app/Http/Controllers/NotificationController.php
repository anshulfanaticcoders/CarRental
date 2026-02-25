<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $userRole = $user->role ?? 'customer';

        // Map user roles to notification roles they should see
        $allowedRoles = match ($userRole) {
            'admin' => ['admin'],
            'vendor' => ['vendor'],
            default => ['customer'], // customer, user, etc.
        };

        $notifications = $user->notifications()
            ->where(function ($query) use ($allowedRoles) {
                // Show notifications matching the user's role, or those without a role field (legacy)
                $query->whereIn('data->role', $allowedRoles)
                      ->orWhereNull('data->role');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $unreadCount = $user->unreadNotifications()
            ->where(function ($query) use ($allowedRoles) {
                $query->whereIn('data->role', $allowedRoles)
                      ->orWhereNull('data->role');
            })
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }
    
    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        
        $notification->markAsRead();
        
        return response()->json([
            'success' => true
        ]);
    }
    
    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        
        return response()->json([
            'success' => true
        ]);
    }

    public function clearAll()
    {
        Auth::user()->notifications()->delete();

        return response()->json([
            'success' => true
        ]);
    }
    
    public function getUnreadCount()
    {
        $user = Auth::user();
        $userRole = $user->role ?? 'customer';

        $allowedRoles = match ($userRole) {
            'admin' => ['admin'],
            'vendor' => ['vendor'],
            default => ['customer'],
        };

        $unreadCount = $user->unreadNotifications()
            ->where(function ($query) use ($allowedRoles) {
                $query->whereIn('data->role', $allowedRoles)
                      ->orWhereNull('data->role');
            })
            ->count();

        return response()->json([
            'unread_count' => $unreadCount,
        ]);
    }
}
