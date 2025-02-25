<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use App\Models\Booking;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function index()
    {
        return Inertia::render('Messages/Index');
    }

    public function vendorIndex()
    {
        $bookings = Booking::whereHas('vehicle', function($query) {
            $query->where('vendor_id', auth()->id());
        })->with(['customer.user.profile', 'vehicle.vendor'])->get();

        return Inertia::render('Messages/VendorIndex', [
            'bookings' => $bookings
        ]);
    }

    public function show($bookingId)
    {
        $booking = Booking::with(['vehicle.vendor', 'customer'])->findOrFail($bookingId);
        
        // Ensure the authenticated user is either the customer or the vendor
        $user = Auth::user();
        $customerId = $booking->customer->user_id;
        $vendorId = $booking->vehicle->vendor_id;
        
        if ($user->id !== $customerId && $user->id !== $vendorId) {
            abort(403, 'Unauthorized access to this conversation');
        }
        
        // Get the other participant in the conversation
        $otherUserId = ($user->id === $customerId) ? $vendorId : $customerId;
        $otherUser = User::find($otherUserId);
        
        // Get messages between these users for this booking
        $messages = Message::where(function($query) use ($user, $otherUserId) {
                $query->where('sender_id', $user->id)
                      ->where('receiver_id', $otherUserId);
            })
            ->orWhere(function($query) use ($user, $otherUserId) {
                $query->where('sender_id', $otherUserId)
                      ->where('receiver_id', $user->id);
            })
            ->where('booking_id', $bookingId)
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'asc')
            ->get();
            
        // Mark unread messages as read
        Message::where('receiver_id', $user->id)
            ->where('booking_id', $bookingId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
            
        // Check if this is an AJAX request (for embedding in Index)
        if (request()->ajax()) {
            return response()->json([
                'props' => [
                    'booking' => $booking,
                    'messages' => $messages,
                    'otherUser' => $otherUser
                ]
            ]);
        }
        
        return Inertia::render('Messages/Show', [
            'booking' => $booking,
            'messages' => $messages,
            'otherUser' => $otherUser
        ]);
    }

    public function getLastMessage($bookingId)
    {
        $booking = Booking::with(['vehicle.vendor', 'customer'])->findOrFail($bookingId);
        
        // Ensure the authenticated user is either the customer or the vendor
        $user = Auth::user();
        $customerId = $booking->customer->user_id;
        $vendorId = $booking->vehicle->vendor_id;
        
        if ($user->id !== $customerId && $user->id !== $vendorId) {
            abort(403, 'Unauthorized access to this conversation');
        }
        
        // Get the other participant in the conversation
        $otherUserId = ($user->id === $customerId) ? $vendorId : $customerId;
        
        // Get the last message for this booking
        $message = Message::where('booking_id', $bookingId)
            ->orderBy('created_at', 'desc')
            ->first();
            
        return response()->json([
            'message' => $message
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string',
            'parent_id' => 'nullable|exists:messages,id'
        ]);
        
        $booking = Booking::with(['vehicle.vendor', 'customer'])->findOrFail($validated['booking_id']);
        
        // Ensure the authenticated user is either the customer or the vendor
        $user = Auth::user();
        $customerId = $booking->customer->user_id;
        $vendorId = $booking->vehicle->vendor_id;
        
        if ($user->id !== $customerId && $user->id !== $vendorId) {
            abort(403, 'Unauthorized access to this conversation');
        }
        
        // Create the message
        $message = Message::create([
            'sender_id' => $user->id,
            'receiver_id' => $validated['receiver_id'],
            'booking_id' => $validated['booking_id'],
            'message' => $validated['message'],
            'parent_id' => $validated['parent_id'] ?? null
        ]);

         // Create a notification
        Notification::create([
            'user_id' => $validated['receiver_id'],
            'type' => 'message',
            'title' => 'New Message',
            'message' => 'You have received a new message from ' . $user->first_name
        ]);
        
        // Load relationships for the response
        $message->load(['sender', 'receiver']);
        
        return response()->json([
            'message' => $message
        ]);
    }
    
    public function getUnreadCount()
    {
        $user = Auth::user();
        
        $unreadCount = Message::where('receiver_id', $user->id)
            ->whereNull('read_at')
            ->count();
            
        return response()->json([
            'unread_count' => $unreadCount
        ]);
    }
}