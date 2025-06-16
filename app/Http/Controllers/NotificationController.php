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
        
        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    
        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $user->unreadNotifications()->count()
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
        
        $unreadCount = $user->unreadNotifications()->count();
            
        return response()->json([
            'unread_count' => $unreadCount
        ]);
    }
}
