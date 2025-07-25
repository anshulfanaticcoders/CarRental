<?php

namespace App\Http\Controllers;

use App\Events\NewMessage;
use App\Models\Message;
use App\Models\User;
use App\Models\Booking;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; // Added import for Storage
use App\Events\MessageDeleted; // Added import
use App\Events\MessageRestored; // Added import

class MessageController extends Controller
{
    public function index($locale)
    {
        // This will now also pass initial chat partners data
        // The Vue component can decide to use this or fetch fresh via API
        return Inertia::render('Messages/Index', [
            'chatPartners' => $this->getCustomerChatPartnersData(),
        ]);
    }

    // New method to fetch chat partners for the customer view
    public function getCustomerChatPartners()
    {
        return response()->json($this->getCustomerChatPartnersData());
    }

    private function getCustomerChatPartnersData()
    {
        $customerId = auth()->id();

        // Get all bookings made by the authenticated customer
        $bookings = Booking::where('customer_id', function ($query) use ($customerId) {
            // Assuming customer_id in bookings table refers to the id in customers table,
            // and customers table has a user_id.
            // If bookings.customer_id is directly users.id, this subquery is simpler.
            // Let's check Booking model structure. Assuming Booking has direct customer_user_id or similar.
            // For now, assuming Booking->customer is a relation to Customer model, which has user_id
            // Modified to handle cases where a user might have multiple customer profiles,
            // by selecting the latest created customer ID.
            $query->select('id')->from('customers')->where('user_id', $customerId)->latest()->limit(1);
        })
        ->with(['vehicle.vendor.profile', 'vehicle.vendor.chatStatus', 'vehicle']) // Eager load: vehicle.vendor is User, then profile and chatStatus
        ->orderBy('created_at', 'desc') // Get latest bookings first for a vendor
        ->get();

        // Group bookings by vendor to get unique vendors
        // vehicle.vendor_id is the user_id of the vendor
        $vendorsData = $bookings->groupBy('vehicle.vendor_id')->map(function ($vendorBookings) use ($customerId) {
            $latestBooking = $vendorBookings->first(); // The first one due to orderBy desc
            $vendorUser = $latestBooking->vehicle->vendor; // vehicle->vendor is the User model of the vendor

            // Calculate unread messages from this specific vendor to the customer
            $unreadCount = Message::where('sender_id', $vendorUser->id)
                ->where('receiver_id', $customerId)
                ->whereIn('booking_id', $vendorBookings->pluck('id'))
                ->whereNull('read_at')
                ->count();

            // Get the last message exchanged with this vendor across all their bookings
            $lastMessage = Message::where(function($q) use ($customerId, $vendorUser) {
                    $q->where('sender_id', $customerId)->where('receiver_id', $vendorUser->id);
                })->orWhere(function($q) use ($customerId, $vendorUser) {
                    $q->where('sender_id', $vendorUser->id)->where('receiver_id', $customerId);
                })
                ->whereIn('booking_id', $vendorBookings->pluck('id'))
                ->orderBy('created_at', 'desc')
                ->first();

            return [
                // $vendorUser already has profile and chatStatus loaded due to eager loading: 'vehicle.vendor.profile', 'vehicle.vendor.chatStatus'
                'user' => $vendorUser, 
                'latest_booking_id' => $latestBooking->id, // ID of the latest booking for linking
                'vehicle_name' => $latestBooking->vehicle->name, // Example: name of vehicle from latest booking
                'last_message_at' => $lastMessage ? $lastMessage->created_at : $latestBooking->created_at,
                'last_message_preview' => $lastMessage ? \Illuminate\Support\Str::limit($lastMessage->message, 30) : 'No messages yet.',
                'unread_count' => $unreadCount,
            ];
        })->sortByDesc('last_message_at')->values(); // Sort vendors by last message time and re-index

        return $vendorsData;
    }

    public function vendorIndex($locale)
{
    $vendorId = auth()->id();

    // Get all bookings where the authenticated user is the vendor
    $bookings = Booking::whereHas('vehicle', function ($query) use ($vendorId) {
        $query->where('vendor_id', $vendorId);
    })
    ->with(['customer.user.profile', 'customer.user.chatStatus', 'vehicle']) // Eager load necessary relations
    ->orderBy('created_at', 'desc') // Get latest bookings first for a customer
    ->get();

    // Group bookings by customer to get unique customers
    $customersData = $bookings->groupBy('customer.user_id')->map(function ($customerBookings) use ($vendorId) {
        $latestBooking = $customerBookings->first(); // The first one due to orderBy desc
        $customerUser = $latestBooking->customer->user;

        // Calculate unread messages from this specific customer to the vendor
        // across all bookings with this customer.
        $unreadCount = Message::where('sender_id', $customerUser->id)
            ->where('receiver_id', $vendorId)
            ->whereIn('booking_id', $customerBookings->pluck('id'))
            ->whereNull('read_at')
            ->count();
        
        // Get the last message exchanged with this customer across all their bookings
        $lastMessage = Message::where(function($q) use ($vendorId, $customerUser) {
                $q->where('sender_id', $vendorId)->where('receiver_id', $customerUser->id);
            })->orWhere(function($q) use ($vendorId, $customerUser) {
                $q->where('sender_id', $customerUser->id)->where('receiver_id', $vendorId);
            })
            ->whereIn('booking_id', $customerBookings->pluck('id'))
            ->orderBy('created_at', 'desc')
            ->first();

        return [
            'user' => $customerUser->load(['profile', 'chatStatus']), // Pass the customer's user model with profile and chatStatus
            'latest_booking_id' => $latestBooking->id, // ID of the latest booking for linking
            'vehicle_name' => $latestBooking->vehicle->name, // Example: name of vehicle from latest booking
            'last_message_at' => $lastMessage ? $lastMessage->created_at : $latestBooking->created_at,
            'last_message_preview' => $lastMessage ? \Illuminate\Support\Str::limit($lastMessage->message, 30) : 'No messages yet.',
            'unread_count' => $unreadCount,
            // We can add more details from $latestBooking or $customerUser if needed
        ];
    })->sortByDesc('last_message_at')->values(); // Sort customers by last message time and re-index

    return Inertia::render('Messages/VendorIndex', [
        'chatPartners' => $customersData // Changed from 'bookings' to 'chatPartners'
    ]);
}


public function show($locale, $bookingId)
{
    $booking = Booking::with(['vehicle.vendor', 'customer'])->findOrFail($bookingId);

    $user = Auth::user();
    $customerId = $booking->customer->user_id;
    $vendorId = $booking->vehicle->vendor_id;

    if ($user->id !== $customerId && $user->id !== $vendorId) {
        abort(403, 'Unauthorized access to this conversation');
    }

    $otherUserId = ($user->id === $customerId) ? $vendorId : $customerId;
    $otherUser = User::select('id', 'first_name', 'last_login_at')->find($otherUserId); // Use last_login_at

    $messages = Message::where(function ($query) use ($user, $otherUserId) {
        $query->where('sender_id', $user->id)
            ->where('receiver_id', $otherUserId);
    })
    ->orWhere(function ($query) use ($user, $otherUserId) {
        $query->where('sender_id', $otherUserId)
            ->where('receiver_id', $user->id);
    })
    ->where('booking_id', $bookingId)
    ->with(['sender', 'receiver'])
    ->orderBy('created_at', 'asc')
    ->get();

    Message::where('receiver_id', $user->id)
        ->where('booking_id', $bookingId)
        ->whereNull('read_at')
        ->update(['read_at' => now()]);

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
        'message' => 'nullable|string', // Message can be nullable if a file or voice note is sent
        'parent_id' => 'nullable|exists:messages,id',
        'file' => 'nullable|file|max:10240|mimes:jpeg,png,jpg,gif,svg,pdf,doc,docx,xls,xlsx,txt', // Max 10MB, common file types
        'voice_note' => 'nullable|file|max:10240|mimes:mp3,wav,ogg,webm', // Max 10MB, common audio types
    ]);

    // Ensure either message, file, or voice note is present
    if (empty($validated['message']) && !$request->hasFile('file') && !$request->hasFile('voice_note')) {
        return response()->json(['error' => 'Message content, a file, or a voice note is required.'], 422);
    }

    $booking = Booking::with(['vehicle.vendor', 'customer'])->findOrFail($validated['booking_id']);
    $user = Auth::user();

    if ($user->id !== $booking->customer->user_id && $user->id !== $booking->vehicle->vendor_id) {
        abort(403, 'Unauthorized access to this conversation');
    }

    $messageData = [
        'sender_id' => $user->id,
        'receiver_id' => $validated['receiver_id'],
        'booking_id' => $validated['booking_id'],
        'message' => $validated['message'] ?? null,
        'parent_id' => $validated['parent_id'] ?? null
    ];

    // Handle file upload if present
    if ($request->hasFile('file')) {
        $file = $request->file('file');
        $folderName = 'chat_attachments';
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs($folderName, $fileName, 'upcloud');

        $messageData['file_path'] = $filePath;
        $messageData['file_name'] = $file->getClientOriginalName();
        $messageData['file_type'] = $file->getMimeType();
        $messageData['file_size'] = $file->getSize();
    }

    // Handle voice note upload if present
    if ($request->hasFile('voice_note')) {
        $voiceNote = $request->file('voice_note');
        $folderName = 'voice_notes';
        $fileName = 'voice_note_' . time() . '.' . $voiceNote->getClientOriginalExtension();
        $filePath = $voiceNote->storeAs($folderName, $fileName, 'upcloud');

        $messageData['voice_note_path'] = $filePath;
        // You might want to store voice note specific metadata here if needed, e.g., duration
    }

    $message = Message::create($messageData);

    // Broadcast the new message
    // Load file_url and voice_note_url accessors for broadcasting
    $message->load(['sender', 'receiver']); // Ensure sender/receiver are loaded for the event
    broadcast(new NewMessage($message))->toOthers();


    return response()->json(['message' => $message]);
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



// ... (other methods)

    public function destroy($locale, $id)
{
    $message = Message::findOrFail($id);

    // Ensure only the sender can delete the message
    if (auth()->id() !== $message->sender_id) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $message->delete(); // This now performs a soft delete

    // Broadcast the event
    // Ensure the message has necessary relations loaded if broadcastWith needs them,
    // or that booking_id is directly available.
    // $message->loadMissing('booking'); // Example if booking relation is needed for channel name and not always loaded
    broadcast(new MessageDeleted($message->fresh()))->toOthers(); // fresh() to get the model with deleted_at

    return response()->json([
        'success' => 'Message deleted successfully',
        'message' => $message->fresh() // Return the soft-deleted message model
    ]);
}

    public function restore($locale, $id)
{
    $message = Message::withTrashed()->findOrFail($id);

    // Ensure only the sender can restore the message, or implement other authorization
    if (auth()->id() !== $message->sender_id) {
        return response()->json(['error' => 'Unauthorized to restore this message'], 403);
    }

    // Optional: Add a time limit for undo, e.g., within 10-15 seconds of deletion
    // if ($message->deleted_at && $message->deleted_at->diffInSeconds(now()) > 15) {
    //     return response()->json(['error' => 'Undo time limit exceeded'], 403);
    // }

    $message->restore();

    broadcast(new MessageRestored($message->fresh()->load(['sender', 'receiver'])))->toOthers();

    return response()->json([
        'success' => 'Message restored successfully',
        'message' => $message->fresh()->load(['sender', 'receiver']) // Return the restored message model
    ]);
}

public function markMessagesAsRead($locale, $bookingId)
{
    $user = Auth::user();

    // Find the booking to ensure the user is a participant
    $booking = Booking::with(['vehicle.vendor', 'customer'])->findOrFail($bookingId);

    $customerId = $booking->customer->user_id;
    $vendorId = $booking->vehicle->vendor_id;

    if ($user->id !== $customerId && $user->id !== $vendorId) {
        return response()->json(['error' => 'Unauthorized to mark messages as read for this booking'], 403);
    }

    // Mark messages received by the current user for this booking as read
    Message::where('receiver_id', $user->id)
        ->where('booking_id', $bookingId)
        ->whereNull('read_at')
        ->update(['read_at' => now()]);

    return response()->json(['success' => 'Messages marked as read.']);
}

}
