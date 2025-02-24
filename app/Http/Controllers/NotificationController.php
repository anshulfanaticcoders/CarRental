<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Inertia\Inertia;

class NotificationController extends Controller
{
    // Fetch all notifications for the authenticated user
    public function index()
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    // Mark a notification as read
    public function markAsRead(Notification $notification)
    {
        $notification->update(['read_at' => now()]);
        return response()->json(['success' => true]);
    }
}