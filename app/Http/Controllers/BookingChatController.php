<?php

namespace App\Http\Controllers;

use App\Models\BookingChat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BookingChatController extends Controller
{
    /**
     * Display the chat index page.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $perPage = 20;

        // Load chats for the user
        $chats = \App\Models\BookingChat::query()
            ->forUser($user->id)
            ->active()
            ->with(['booking.vehicle', 'customer.profile', 'vendor.profile', 'customer', 'vendor'])
            ->orderBy('last_message_at', 'desc')
            ->paginate($perPage);

        // Get unread count
        $unreadCount = \App\Models\BookingChat::getTotalUnreadCount($user->id);

        // For vendors, show vendor chat index
        if ($user->role === 'vendor') {
            return Inertia::render('BookingChat/Index', [
                'auth' => $request->user()->load('profile'),
                'isVendor' => true,
                'chats' => $chats,
                'unreadCount' => $unreadCount
            ]);
        }

        // For customers, show customer chat index
        return Inertia::render('BookingChat/Index', [
            'auth' => $request->user()->load('profile'),
            'isVendor' => false,
            'chats' => $chats,
            'unreadCount' => $unreadCount
        ]);
    }

    /**
     * Display a specific chat.
     */
    public function show(Request $request, $id)
    {
        $user = Auth::user();

        // Load the chat with relationships
        $chat = BookingChat::with([
            'booking',
            'customer.profile',
            'vendor.profile',
            'customer',
            'vendor',
            'messages' => function ($query) {
                $query->orderBy('created_at', 'asc')
                      ->with(['sender.profile', 'chatAttachment', 'chatLocation']);
            }
        ])->findOrFail($id);

        // Check if user is a participant
        if (!$chat->hasParticipant($user->id)) {
            abort(403, 'You are not authorized to view this chat');
        }

        // Mark messages as read for this user
        $this->markMessagesAsRead($chat, $user->id);

        // For vendors, show vendor chat view
        if ($user->role === 'vendor') {
            return Inertia::render('BookingChat/Show', [
                'auth' => $request->user()->load('profile'),
                'chat' => $chat,
                'isVendor' => true
            ]);
        }

        // For customers, show customer chat view
        return Inertia::render('BookingChat/Show', [
            'auth' => $request->user()->load('profile'),
            'chat' => $chat,
            'isVendor' => false
        ]);
    }

    /**
     * Mark all messages in a chat as read for the user.
     */
    private function markMessagesAsRead(BookingChat $chat, int $userId): void
    {
        // Mark unread messages as read
        $chat->messages()
             ->where('receiver_id', $userId)
             ->whereNull('read_at')
             ->update(['read_at' => now()]);

        // Reset unread count for the user
        $chat->resetUnreadCount($userId);
    }
}