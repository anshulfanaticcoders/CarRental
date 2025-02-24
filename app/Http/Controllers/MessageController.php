<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Notification;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MessageController extends Controller
{
    public function index()
    {
        return Inertia::render('Messages/Index', [
            'messages' => Message::where('receiver_id', auth()->id())
                ->orWhere('sender_id', auth()->id())
                ->with(['sender', 'receiver'])
                ->latest()
                ->get()
        ]);
    }

    public function show($receiverId)
    {
        $messages = Message::where(function ($query) use ($receiverId) {
            $query->where('sender_id', auth()->id())
                  ->where('receiver_id', $receiverId);
        })->orWhere(function ($query) use ($receiverId) {
            $query->where('sender_id', $receiverId)
                  ->where('receiver_id', auth()->id());
        })
        ->with(['sender', 'receiver', 'replies'])
        ->orderBy('created_at', 'asc')
        ->get();

        return Inertia::render('Messages/Show', [
            'messages' => $messages,
            'receiverId' => (int)$receiverId,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string',
            'parent_id' => 'nullable|exists:messages,id',
        ]);

        $message = Message::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $validated['receiver_id'],
            'message' => $validated['message'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        Notification::create([
            'user_id' => $validated['receiver_id'],
            'type' => 'message',
            'title' => 'New Message',
            'message' => 'You have a new message from ' . auth()->user()->name,
        ]);

        return back();
    }

    public function markAsRead(Message $message)
    {
        if ($message->receiver_id !== auth()->id()) {
            abort(403);
        }

        $message->update(['read_at' => now()]);
        return response()->json(['success' => true]);
    }
}