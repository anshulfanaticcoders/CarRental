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
        
        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    
        return response()->json([
            'notifications' => $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'type' => $notification->type,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                    'booking_id' => $notification->booking_id // Ensure booking_id is included
                ];
            }),
            'unread_count' => Notification::where('user_id', $user->id)->whereNull('read_at')->count()
        ]);
    }
    
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string',
            'title' => 'required|string',
            'message' => 'required|string'
        ]);
        
        $notification = Notification::create($validated);
        
        return response()->json([
            'notification' => $notification
        ]);
    }
    
    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);
        
        // Ensure the user can only mark their own notifications as read
        if ($notification->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action');
        }
        
        $notification->update([
            'read_at' => now()
        ]);
        
        return response()->json([
            'success' => true
        ]);
    }
    
    public function markAllAsRead()
    {
        $user = Auth::user();
        
        Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        
        return response()->json([
            'success' => true
        ]);
    }
    
    public function getUnreadCount()
    {
        $user = Auth::user();
        
        $unreadCount = Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
            
        return response()->json([
            'unread_count' => $unreadCount
        ]);
    }
}