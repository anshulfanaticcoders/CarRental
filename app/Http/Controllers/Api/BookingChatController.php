<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BookingChat;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BookingChatController extends Controller
{
    /**
     * Get all chats for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $status = $request->get('status', 'active');
        $perPage = $request->get('per_page', 20);

        $chats = BookingChat::query()
            ->forUser($user->id)
            ->when($status === 'active', fn($query) => $query->active())
            ->when($status === 'archived', fn($query) => $query->archived())
            ->with(['booking', 'customer.profile', 'vendor.profile'])
            ->orderBy('last_message_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $chats,
            'unread_count' => BookingChat::getTotalUnreadCount($user->id),
        ]);
    }

    /**
     * Create a new chat session for a booking.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'booking_id' => ['required', 'exists:bookings,id'],
        ]);

        $user = Auth::user();
        $booking = Booking::findOrFail($request->booking_id);

        // Check if user is part of the booking
        if ($booking->customer->user_id !== $user->id && $booking->vehicle->vendor_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to create a chat for this booking',
            ], 403);
        }

        // Check if chat already exists
        $existingChat = BookingChat::where('booking_id', $booking->id)->first();
        if ($existingChat) {
            return response()->json([
                'success' => false,
                'message' => 'Chat already exists for this booking',
                'data' => $existingChat,
            ], 422);
        }

        try {
            DB::beginTransaction();

            $chat = BookingChat::createForBooking($booking);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Chat created successfully',
                'data' => $chat->load(['booking', 'customer.profile', 'vendor.profile']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create chat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific chat with messages.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        $chat = BookingChat::with([
            'booking',
            'customer.profile',
            'vendor.profile',
            'messages' => function ($query) {
                $query->orderBy('created_at', 'asc')
                      ->with(['sender.profile', 'chatAttachment', 'chatLocation']);
            }
        ])->findOrFail($id);

        // Check if user is a participant
        if (!$chat->hasParticipant($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view this chat',
            ], 403);
        }

        // Mark messages as read for this user
        $this->markMessagesAsRead($chat, $user->id);

        return response()->json([
            'success' => true,
            'data' => $chat,
        ]);
    }

    /**
     * Archive or unarchive a chat.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'action' => ['required', 'string', Rule::in(['archive', 'unarchive', 'mute', 'unmute'])],
        ]);

        $user = Auth::user();
        $chat = BookingChat::findOrFail($id);

        // Check if user is a participant
        if (!$chat->hasParticipant($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to update this chat',
            ], 403);
        }

        try {
            switch ($request->action) {
                case 'archive':
                    $chat->update(['status' => 'archived']);
                    $message = 'Chat archived successfully';
                    break;

                case 'unarchive':
                    $chat->update(['status' => 'active']);
                    $message = 'Chat unarchived successfully';
                    break;

                case 'mute':
                    if ($chat->customer_id == $user->id) {
                        $chat->update(['customer_muted' => true]);
                    } else {
                        $chat->update(['vendor_muted' => true]);
                    }
                    $message = 'Chat muted successfully';
                    break;

                case 'unmute':
                    if ($chat->customer_id == $user->id) {
                        $chat->update(['customer_muted' => false]);
                    } else {
                        $chat->update(['vendor_muted' => false]);
                    }
                    $message = 'Chat unmuted successfully';
                    break;

                default:
                    throw new \InvalidArgumentException('Invalid action');
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $chat,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update chat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a chat and all its messages.
     */
    public function destroy($id): JsonResponse
    {
        $user = Auth::user();
        $chat = BookingChat::findOrFail($id);

        // Check if user is a participant
        if (!$chat->hasParticipant($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to delete this chat',
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Soft delete the chat (this will cascade to related records)
            $chat->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Chat deleted successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete chat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get unread message count for the authenticated user.
     */
    public function getUnreadCount(): JsonResponse
    {
        $user = Auth::user();
        $unreadCount = BookingChat::getTotalUnreadCount($user->id);

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $unreadCount,
            ],
        ]);
    }

    /**
     * Find or create a chat for a booking.
     */
    public function findOrCreateForBooking(Request $request): JsonResponse
    {
        $request->validate([
            'booking_id' => ['required', 'exists:bookings,id'],
        ]);

        $user = Auth::user();
        $booking = Booking::findOrFail($request->booking_id);

        // Check if user is part of the booking
        if ($booking->customer->user_id !== $user->id && $booking->vehicle->vendor_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not part of this booking',
            ], 403);
        }

        $chat = BookingChat::firstOrCreate(
            ['booking_id' => $booking->id],
            [
                'customer_id' => $booking->customer->user_id,
                'vendor_id' => $booking->vehicle->vendor_id,
                'status' => 'active',
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $chat->load(['booking', 'customer.profile', 'vendor.profile']),
        ]);
    }

    /**
     * Search chats by message content or participant name.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => ['required', 'string', 'min:2'],
        ]);

        $user = Auth::user();
        $query = $request->get('query');
        $perPage = $request->get('per_page', 20);

        $chats = BookingChat::query()
            ->forUser($user->id)
            ->where(function ($q) use ($query) {
                $q->where('last_message_preview', 'like', "%{$query}%")
                  ->orWhereHas('customer', function ($subQuery) use ($query) {
                      $subQuery->whereHas('profile', function ($profileQuery) use ($query) {
                          $profileQuery->where('first_name', 'like', "%{$query}%")
                                      ->orWhere('last_name', 'like', "%{$query}%");
                      });
                  })
                  ->orWhereHas('vendor', function ($subQuery) use ($query) {
                      $subQuery->whereHas('profile', function ($profileQuery) use ($query) {
                          $profileQuery->where('first_name', 'like', "%{$query}%")
                                      ->orWhere('last_name', 'like', "%{$query}%");
                      });
                  });
            })
            ->with(['booking', 'customer.profile', 'vendor.profile'])
            ->orderBy('last_message_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $chats,
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

    /**
     * Get chat statistics for the authenticated user.
     */
    public function getStats(): JsonResponse
    {
        $user = Auth::user();

        $stats = [
            'total_chats' => BookingChat::forUser($user->id)->count(),
            'active_chats' => BookingChat::forUser($user->id)->active()->count(),
            'archived_chats' => BookingChat::forUser($user->id)->archived()->count(),
            'unread_count' => BookingChat::getTotalUnreadCount($user->id),
            'muted_chats' => BookingChat::forUser($user->id)
                                ->where(function ($query) use ($user) {
                                    $query->where('customer_id', $user->id)
                                          ->where('customer_muted', true)
                                          ->orWhere('vendor_id', $user->id)
                                          ->where('vendor_muted', true);
                                })
                                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
