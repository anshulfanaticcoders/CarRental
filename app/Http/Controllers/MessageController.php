<?php

namespace App\Http\Controllers;

use App\Events\NewMessage;
use App\Events\TypingIndicator;
use App\Events\MessageRead;
use App\Models\Message;
use App\Models\User;
use App\Models\Booking;
use App\Models\ChatTypingStatus;
use App\Models\MessageReadReceipt;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; // Added import for Storage
use App\Events\MessageDeleted; // Added import
use App\Events\MessageRestored; // Added import

class MessageController extends Controller
{
    /**
     * Validate if a booking is eligible for chat functionality
     *
     * @param Booking $booking
     * @param User $user
     * @return array
     */
    private function validateBookingChatEligibility($booking, $user)
    {
        $allowedStatuses = ['pending', 'confirmed'];

        if (!in_array($booking->booking_status, $allowedStatuses)) {
            return [
                'allowed' => false,
                'reason' => 'Chat is not available for ' . $booking->booking_status . ' bookings',
                'readOnly' => true
            ];
        }

        if ($user->id !== $booking->customer->user_id && $user->id !== $booking->vehicle->vendor_id) {
            return [
                'allowed' => false,
                'reason' => 'Unauthorized access',
                'readOnly' => false
            ];
        }

        return ['allowed' => true, 'readOnly' => false];
    }

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
        ->with(['vehicle.vendor.profile', 'vehicle.vendor.chatStatus', 'vehicle', 'vehicle.category', 'vehicle.images']) // Eager load more vehicle data
        ->orderBy('created_at', 'desc') // Get latest bookings first for a vendor
        ->get();

        // Group bookings by vendor to get unique vendors
        // vehicle.vendor_id is the user_id of the vendor
        $vendorsData = $bookings->groupBy('vehicle.vendor_id')->map(function ($vendorBookings) use ($customerId) {
            $latestBooking = $vendorBookings->first(); // The first one due to orderBy desc
            $vendorUser = $latestBooking->vehicle->vendor; // vehicle->vendor is the User model of the vendor

            // Separate active and completed bookings
            $activeBookings = $vendorBookings->filter(function ($booking) {
                return in_array($booking->booking_status, ['pending', 'confirmed']);
            });
            $completedBookings = $vendorBookings->filter(function ($booking) {
                return in_array($booking->booking_status, ['completed', 'cancelled']);
            });

            // Get the most recent active booking
            $activeBooking = $activeBookings->first();

            // Get the most recent completed booking (for Recent tab context)
            $latestCompletedBooking = $completedBookings->first();

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

            // Prepare vehicle image for active booking
            $vehicleImage = null;
            if ($activeBooking->vehicle->images && $activeBooking->vehicle->images->isNotEmpty()) {
                $vehicleImage = $activeBooking->vehicle->images->first()->image_url;
            }

            // NEW: Build array of only ACTIVE bookings for this vendor (for booking selection dropdown)
            $allBookings = $activeBookings->map(function ($booking) {
                $bookingVehicleImage = null;
                if ($booking->vehicle->images && $booking->vehicle->images->isNotEmpty()) {
                    $bookingVehicleImage = $booking->vehicle->images->first()->image_url;
                }

                return [
                    'id' => $booking->id,
                    'vehicle' => [
                        'id' => $booking->vehicle->id,
                        'name' => $booking->vehicle->name,
                        'brand' => $booking->vehicle->brand,
                        'model' => $booking->vehicle->model,
                        'category' => $booking->vehicle->category->name ?? 'Unknown',
                        'image' => $bookingVehicleImage,
                    ],
                    'booking_status' => $booking->booking_status,
                    'pickup_date' => $booking->pickup_date,
                    'return_date' => $booking->return_date,
                    'pickup_time' => $booking->pickup_time,
                    'return_time' => $booking->return_time,
                    'total_amount' => $booking->total_amount,
                    'amount_paid' => $booking->amount_paid,
                ];
            })->toArray();

            return [
                // $vendorUser already has profile and chatStatus loaded due to eager loading: 'vehicle.vendor.profile', 'vehicle.vendor.chatStatus'
                'user' => $vendorUser,
                'latest_booking_id' => $latestBooking->id, // ID of the latest booking for linking
                'active_booking_id' => $activeBooking ? $activeBooking->id : null, // ID of the active booking for context
                'latest_completed_booking_id' => $latestCompletedBooking ? $latestCompletedBooking->id : null, // ID of the latest completed booking
                'vehicle' => [
                    'id' => $activeBooking ? $activeBooking->vehicle->id : ($latestCompletedBooking ? $latestCompletedBooking->vehicle->id : null),
                    'name' => $activeBooking ? $activeBooking->vehicle->name : ($latestCompletedBooking ? $latestCompletedBooking->vehicle->name : null),
                    'brand' => $activeBooking ? $activeBooking->vehicle->brand : ($latestCompletedBooking ? $latestCompletedBooking->vehicle->brand : null),
                    'model' => $activeBooking ? $activeBooking->vehicle->model : ($latestCompletedBooking ? $latestCompletedBooking->vehicle->model : null),
                    'category' => ($activeBooking ? $activeBooking->vehicle->category : $latestCompletedBooking->vehicle->category)->name ?? 'Unknown',
                    'image' => $vehicleImage,
                ],
                'booking' => [
                    'id' => $activeBooking ? $activeBooking->id : ($latestCompletedBooking ? $latestCompletedBooking->id : null),
                    'status' => $activeBooking ? $activeBooking->booking_status : ($latestCompletedBooking ? $latestCompletedBooking->booking_status : null),
                    'pickup_date' => $activeBooking ? $activeBooking->pickup_date : ($latestCompletedBooking ? $latestCompletedBooking->pickup_date : null),
                    'return_date' => $activeBooking ? $activeBooking->return_date : ($latestCompletedBooking ? $latestCompletedBooking->return_date : null),
                    'pickup_time' => $activeBooking ? $activeBooking->pickup_time : ($latestCompletedBooking ? $latestCompletedBooking->pickup_time : null),
                    'return_time' => $activeBooking ? $activeBooking->return_time : ($latestCompletedBooking ? $latestCompletedBooking->return_time : null),
                    'total_amount' => $activeBooking ? $activeBooking->total_amount : ($latestCompletedBooking ? $latestCompletedBooking->total_amount : null),
                    'amount_paid' => $activeBooking ? $activeBooking->amount_paid : ($latestCompletedBooking ? $latestCompletedBooking->amount_paid : null),
                    'chat_allowed' => $activeBooking ? in_array($activeBooking->booking_status, ['pending', 'confirmed']) : false,
                    'chat_restrictions' => [
                        'can_send_messages' => $activeBooking ? in_array($activeBooking->booking_status, ['pending', 'confirmed']) : false,
                        'reason' => ($activeBooking && in_array($activeBooking->booking_status, ['pending', 'confirmed']))
                            ? null
                            : 'Chat is not available for ' . ($activeBooking ? $activeBooking->booking_status : 'completed') . ' bookings',
                        'read_only' => $activeBooking ? !in_array($activeBooking->booking_status, ['pending', 'confirmed']) : true
                    ]
                ],
                // NEW: Include all bookings for booking selection modal
                'bookings' => $allBookings,
                'has_multiple_active_bookings' => $activeBookings->count() > 1, // NEW: Flag for multiple active bookings
                'has_active_bookings' => $activeBookings->count() > 0,
                'active_bookings_count' => $activeBookings->count(),
                'completed_bookings_count' => $completedBookings->count(),
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
    ->with(['customer.user.profile', 'customer.user.chatStatus', 'vehicle', 'vehicle.category', 'vehicle.images']) // Eager load more vehicle data
    ->orderBy('created_at', 'desc') // Get latest bookings first for a customer
    ->get();

    // Group bookings by customer to get unique customers
    $customersData = $bookings->groupBy('customer.user_id')->map(function ($customerBookings) use ($vendorId) {
        $latestBooking = $customerBookings->first(); // The first one due to orderBy desc
        $customerUser = $latestBooking->customer->user;

        // Separate active and completed bookings
        $activeBookings = $customerBookings->filter(function ($booking) {
            return in_array($booking->booking_status, ['pending', 'confirmed']);
        });
        $completedBookings = $customerBookings->filter(function ($booking) {
            return in_array($booking->booking_status, ['completed', 'cancelled']);
        });

        // Get the most recent active booking
        $activeBooking = $activeBookings->first();

        // Get the most recent completed booking (for Recent tab context)
        $latestCompletedBooking = $completedBookings->first();

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

        // Prepare vehicle image for active booking
        $vehicleImage = null;
        if ($activeBooking->vehicle->images && $activeBooking->vehicle->images->isNotEmpty()) {
            $vehicleImage = $activeBooking->vehicle->images->first()->image_url;
        }

        // NEW: Build array of only ACTIVE bookings for this customer (for booking selection dropdown)
        $allBookings = $activeBookings->map(function ($booking) {
            $bookingVehicleImage = null;
            if ($booking->vehicle->images && $booking->vehicle->images->isNotEmpty()) {
                $bookingVehicleImage = $booking->vehicle->images->first()->image_url;
            }

            return [
                'id' => $booking->id,
                'vehicle' => [
                    'id' => $booking->vehicle->id,
                    'name' => $booking->vehicle->name,
                    'brand' => $booking->vehicle->brand,
                    'model' => $booking->vehicle->model,
                    'category' => $booking->vehicle->category->name ?? 'Unknown',
                    'image' => $bookingVehicleImage,
                ],
                'booking_status' => $booking->booking_status,
                'pickup_date' => $booking->pickup_date,
                'return_date' => $booking->return_date,
                'pickup_time' => $booking->pickup_time,
                'return_time' => $booking->return_time,
                'total_amount' => $booking->total_amount,
                'amount_paid' => $booking->amount_paid,
            ];
        })->toArray();

        return [
            'user' => $customerUser->load(['profile', 'chatStatus']), // Pass the customer's user model with profile and chatStatus
            'latest_booking_id' => $latestBooking->id, // ID of the latest booking for linking
            'active_booking_id' => $activeBooking ? $activeBooking->id : null, // ID of the active booking for context
            'latest_completed_booking_id' => $latestCompletedBooking ? $latestCompletedBooking->id : null, // ID of the latest completed booking
            'vehicle' => [
                'id' => $activeBooking ? $activeBooking->vehicle->id : ($latestCompletedBooking ? $latestCompletedBooking->vehicle->id : null),
                'name' => $activeBooking ? $activeBooking->vehicle->name : ($latestCompletedBooking ? $latestCompletedBooking->vehicle->name : null),
                'brand' => $activeBooking ? $activeBooking->vehicle->brand : ($latestCompletedBooking ? $latestCompletedBooking->vehicle->brand : null),
                'model' => $activeBooking ? $activeBooking->vehicle->model : ($latestCompletedBooking ? $latestCompletedBooking->vehicle->model : null),
                'category' => ($activeBooking ? $activeBooking->vehicle->category : $latestCompletedBooking->vehicle->category)->name ?? 'Unknown',
                'image' => $vehicleImage,
            ],
            'booking' => [
                'id' => $activeBooking ? $activeBooking->id : ($latestCompletedBooking ? $latestCompletedBooking->id : null),
                'status' => $activeBooking ? $activeBooking->booking_status : ($latestCompletedBooking ? $latestCompletedBooking->booking_status : null),
                'pickup_date' => $activeBooking ? $activeBooking->pickup_date : ($latestCompletedBooking ? $latestCompletedBooking->pickup_date : null),
                'return_date' => $activeBooking ? $activeBooking->return_date : ($latestCompletedBooking ? $latestCompletedBooking->return_date : null),
                'pickup_time' => $activeBooking ? $activeBooking->pickup_time : ($latestCompletedBooking ? $latestCompletedBooking->pickup_time : null),
                'return_time' => $activeBooking ? $activeBooking->return_time : ($latestCompletedBooking ? $latestCompletedBooking->return_time : null),
                'total_amount' => $activeBooking ? $activeBooking->total_amount : ($latestCompletedBooking ? $latestCompletedBooking->total_amount : null),
                'amount_paid' => $activeBooking ? $activeBooking->amount_paid : ($latestCompletedBooking ? $latestCompletedBooking->amount_paid : null),
                'chat_allowed' => $activeBooking ? in_array($activeBooking->booking_status, ['pending', 'confirmed']) : false,
                'chat_restrictions' => [
                    'can_send_messages' => $activeBooking ? in_array($activeBooking->booking_status, ['pending', 'confirmed']) : false,
                    'reason' => ($activeBooking && in_array($activeBooking->booking_status, ['pending', 'confirmed']))
                        ? null
                        : 'Chat is not available for ' . ($activeBooking ? $activeBooking->booking_status : 'completed') . ' bookings',
                    'read_only' => $activeBooking ? !in_array($activeBooking->booking_status, ['pending', 'confirmed']) : true
                ]
            ],
            // NEW: Include all bookings for booking selection modal
            'bookings' => $allBookings,
            'has_multiple_active_bookings' => $activeBookings->count() > 1, // NEW: Flag for multiple active bookings
            'has_active_bookings' => $activeBookings->count() > 0,
            'active_bookings_count' => $activeBookings->count(),
            'completed_bookings_count' => $completedBookings->count(),
            'last_message_at' => $lastMessage ? $lastMessage->created_at : $latestBooking->created_at,
            'last_message_preview' => $lastMessage ? \Illuminate\Support\Str::limit($lastMessage->message, 30) : 'No messages yet.',
            'unread_count' => $unreadCount,
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

    $messages = Message::where('booking_id', $bookingId)
        ->where(function ($query) use ($user, $otherUserId) {
            $query->where(function ($subQuery) use ($user, $otherUserId) {
                $subQuery->where('sender_id', $user->id)
                       ->where('receiver_id', $otherUserId);
            })
            ->orWhere(function ($subQuery) use ($user, $otherUserId) {
                $subQuery->where('sender_id', $otherUserId)
                       ->where('receiver_id', $user->id);
            });
        })
        ->with(['sender', 'receiver'])
        ->orderBy('created_at', 'asc')
        ->get();

  
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

    // Check chat eligibility before allowing message
    $eligibility = $this->validateBookingChatEligibility($booking, $user);
    if (!$eligibility['allowed']) {
        return response()->json([
            'error' => $eligibility['reason'],
            'chat_allowed' => false,
            'read_only' => $eligibility['readOnly']
        ], 403);
    }

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

    // Get unread messages for this user in this booking
    $unreadMessages = Message::where('receiver_id', $user->id)
        ->where('booking_id', $bookingId)
        ->whereNull('read_at')
        ->get();

    if ($unreadMessages->isEmpty()) {
        return response()->json(['success' => 'No unread messages to mark.']);
    }

    // Mark messages as read using both the legacy method and the new read receipts system
    $now = now();

    // Update legacy read_at field
    Message::where('receiver_id', $user->id)
        ->where('booking_id', $bookingId)
        ->whereNull('read_at')
        ->update(['read_at' => $now]);

    // Create detailed read receipts for each message with error handling
    // Only try to use MessageReadReceipt if the table exists
    if (Schema::hasTable('message_read_receipts')) {
        foreach ($unreadMessages as $message) {
            try {
                MessageReadReceipt::markAsRead($message->id, $user->id);
            } catch (\Exception $e) {
                \Log::error('Failed to create read receipt for message ' . $message->id . ': ' . $e->getMessage());
                // Continue even if individual read receipt fails
            }
        }
    }

    // Broadcast read status update for other participants
    $otherUserId = ($user->id === $customerId) ? $vendorId : $customerId;

    // Temporarily disable broadcasting to isolate the issue
    // TODO: Re-enable broadcasting once Pusher/queue is configured properly
    /*
    foreach ($unreadMessages as $message) {
        try {
            // Broadcast message read event
            broadcast(new MessageRead($message, $user, $otherUserId))->toOthers();
        } catch (\Exception $e) {
            \Log::error('Failed to broadcast MessageRead event: ' . $e->getMessage());
            // Continue even if broadcasting fails
        }
    }
    */

    return response()->json([
        'success' => 'Messages marked as read.',
        'messages_marked' => $unreadMessages->count(),
        'marked_at' => $now->toISOString()
    ]);
}

/**
 * Start typing indicator for a booking chat
 */
public function startTyping(Request $request, $locale)
{
    try {
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
        ]);

        $user = Auth::user();
        $bookingId = $validated['booking_id'];

        // Verify user is a participant in this booking
        $booking = Booking::with(['vehicle.vendor', 'customer'])->findOrFail($bookingId);
        $customerId = $booking->customer->user_id;
        $vendorId = $booking->vehicle->vendor_id;

        if ($user->id !== $customerId && $user->id !== $vendorId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Update typing status with error handling
        try {
            ChatTypingStatus::startTyping($user->id, $bookingId);
        } catch (\Exception $e) {
            \Log::error('Failed to start typing status: ' . $e->getMessage());
            // Continue without typing status if table doesn't exist
        }

        // Broadcast typing event with error handling
        try {
            broadcast(new TypingIndicator($user, $bookingId, true))->toOthers();
        } catch (\Exception $e) {
            \Log::error('Failed to broadcast typing indicator: ' . $e->getMessage());
            // Continue without broadcasting if broadcasting fails
        }

        return response()->json(['success' => 'Typing indicator started']);
    } catch (\Exception $e) {
        \Log::error('Start typing error: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to start typing: ' . $e->getMessage()], 500);
    }
}

/**
 * Stop typing indicator for a booking chat
 */
public function stopTyping(Request $request, $locale)
{
    try {
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
        ]);

        $user = Auth::user();
        $bookingId = $validated['booking_id'];

        // Verify user is a participant in this booking
        $booking = Booking::with(['vehicle.vendor', 'customer'])->findOrFail($bookingId);
        $customerId = $booking->customer->user_id;
        $vendorId = $booking->vehicle->vendor_id;

        if ($user->id !== $customerId && $user->id !== $vendorId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Update typing status with error handling
        try {
            ChatTypingStatus::stopTyping($user->id, $bookingId);
        } catch (\Exception $e) {
            \Log::error('Failed to stop typing status: ' . $e->getMessage());
            // Continue without typing status if table doesn't exist
        }

        // Broadcast stop typing event with error handling
        try {
            broadcast(new TypingIndicator($user, $bookingId, false))->toOthers();
        } catch (\Exception $e) {
            \Log::error('Failed to broadcast stop typing indicator: ' . $e->getMessage());
            // Continue without broadcasting if broadcasting fails
        }

        return response()->json(['success' => 'Typing indicator stopped']);
    } catch (\Exception $e) {
        \Log::error('Stop typing error: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to stop typing: ' . $e->getMessage()], 500);
    }
}

/**
 * Get current typing users for a booking
 */
public function getTypingUsers(Request $request, $locale, $bookingId)
{
    try {
        $user = Auth::user();

        // Verify user is a participant in this booking
        $booking = Booking::with(['vehicle.vendor', 'customer'])->findOrFail($bookingId);
        $customerId = $booking->customer->user_id;
        $vendorId = $booking->vehicle->vendor_id;

        if ($user->id !== $customerId && $user->id !== $vendorId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Get currently typing users (excluding current user) with error handling
        try {
            $typingUsers = ChatTypingStatus::getTypingUsers($bookingId, $user->id);
        } catch (\Exception $e) {
            \Log::error('Failed to get typing users: ' . $e->getMessage());
            // Return empty typing users if table doesn't exist
            $typingUsers = collect([]);
        }

        return response()->json([
            'typing_users' => $typingUsers,
            'is_anyone_typing' => $typingUsers->count() > 0
        ]);
    } catch (\Exception $e) {
        \Log::error('Get typing users error: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to get typing users: ' . $e->getMessage()], 500);
    }
}

    /**
     * DEBUG: Get raw chat partners data with detailed counts
     */
    public function debugChatPartners($locale)
    {
        $user = Auth::user();

        if ($user->hasRole('vendor')) {
            // Get vendor chat data
            $vendorId = auth()->id();
            $bookings = Booking::whereHas('vehicle', function ($query) use ($vendorId) {
                $query->where('vendor_id', $vendorId);
            })
            ->with(['customer.user.profile', 'customer.user.chatStatus', 'vehicle', 'vehicle.category', 'vehicle.images'])
            ->orderBy('created_at', 'desc')
            ->get();

            $customersData = $bookings->groupBy('customer.user_id')->map(function ($customerBookings) use ($vendorId) {
                $latestBooking = $customerBookings->first();
                $customerUser = $latestBooking->customer->user;

                $activeBookings = $customerBookings->filter(function ($booking) {
                    return in_array($booking->booking_status, ['pending', 'confirmed']);
                });
                $completedBookings = $customerBookings->filter(function ($booking) {
                    return in_array($booking->booking_status, ['completed', 'cancelled']);
                });

                return [
                    'debug_info' => [
                        'customer_name' => $customerUser->first_name . ' ' . $customerUser->last_name,
                        'total_bookings' => $customerBookings->count(),
                        'active_bookings_count' => $activeBookings->count(),
                        'completed_bookings_count' => $completedBookings->count(),
                        'active_bookings' => $activeBookings->pluck('id')->toArray(),
                        'completed_bookings' => $completedBookings->pluck('id')->toArray(),
                        'booking_statuses' => $customerBookings->pluck('booking_status')->toArray(),
                    ],
                    'active_bookings_count' => $activeBookings->count(),
                    'completed_bookings_count' => $completedBookings->count(),
                ];
            })->values();

            return response()->json([
                'user_type' => 'vendor',
                'chat_partners' => $customersData,
            ]);
        } else {
            // Get customer chat data
            $customerId = auth()->id();
            $bookings = Booking::where('customer_id', function ($query) use ($customerId) {
                $query->select('id')->from('customers')->where('user_id', $customerId)->latest()->limit(1);
            })
            ->with(['vehicle.vendor.profile', 'vehicle.vendor.chatStatus', 'vehicle', 'vehicle.category', 'vehicle.images'])
            ->orderBy('created_at', 'desc')
            ->get();

            $vendorsData = $bookings->groupBy('vehicle.vendor_id')->map(function ($vendorBookings) use ($customerId) {
                $latestBooking = $vendorBookings->first();
                $vendorUser = $latestBooking->vehicle->vendor;

                $activeBookings = $vendorBookings->filter(function ($booking) {
                    return in_array($booking->booking_status, ['pending', 'confirmed']);
                });
                $completedBookings = $vendorBookings->filter(function ($booking) {
                    return in_array($booking->booking_status, ['completed', 'cancelled']);
                });

                return [
                    'debug_info' => [
                        'vendor_name' => $vendorUser->first_name . ' ' . $vendorUser->last_name,
                        'total_bookings' => $vendorBookings->count(),
                        'active_bookings_count' => $activeBookings->count(),
                        'completed_bookings_count' => $completedBookings->count(),
                        'active_bookings' => $activeBookings->pluck('id')->toArray(),
                        'completed_bookings' => $completedBookings->pluck('id')->toArray(),
                        'booking_statuses' => $vendorBookings->pluck('booking_status')->toArray(),
                    ],
                    'active_bookings_count' => $activeBookings->count(),
                    'completed_bookings_count' => $completedBookings->count(),
                ];
            })->values();

            return response()->json([
                'user_type' => 'customer',
                'chat_partners' => $vendorsData,
            ]);
        }
    }
}
