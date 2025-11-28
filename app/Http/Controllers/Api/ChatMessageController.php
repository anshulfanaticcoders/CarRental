<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BookingChat;
use App\Models\Message;
use App\Models\ChatAttachment;
use App\Models\ChatLocation;
use App\Models\ChatMessageReaction;
use App\Events\MessageEdited;
use App\Events\MessageUndo;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ChatMessageController extends Controller
{
    /**
     * Get messages for a specific chat.
     */
    public function index(Request $request, $chatId): JsonResponse
    {
        $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $user = Auth::user();
        $chat = BookingChat::findOrFail($chatId);

        // Check if user is a participant
        if (!$chat->hasParticipant($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view messages in this chat',
            ], 403);
        }

        $perPage = $request->get('per_page', 50);
        $page = $request->get('page', 1);

        $messages = $chat->messages()
            ->with(['sender.profile', 'chatAttachment', 'chatLocation', 'messageReactions.user.profile'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Reverse the order for chronological display
        $messagesCollection = collect($messages->items())->reverse();

        return response()->json([
            'success' => true,
            'data' => [
                'messages' => $messagesCollection,
                'pagination' => [
                    'current_page' => $messages->currentPage(),
                    'per_page' => $messages->perPage(),
                    'total' => $messages->total(),
                    'has_more' => $messages->hasMorePages(),
                ],
            ],
        ]);
    }

    /**
     * Send a new message to a chat.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'booking_chat_id' => ['required', 'exists:booking_chats,id'],
            'message' => ['nullable', 'string', 'max:10000'],
            'message_type' => ['required', 'string', 'in:text,emoji,image,video,audio,document,location,system'],
            'attachment_id' => ['nullable', 'exists:chat_attachments,id'],
            'location_id' => ['nullable', 'exists:chat_locations,id'],
            'parent_id' => ['nullable', 'exists:messages,id'],
            'metadata' => ['nullable', 'array'],
        ]);

        $user = Auth::user();
        $chat = BookingChat::findOrFail($request->booking_chat_id);

        // Check if user is a participant
        if (!$chat->hasParticipant($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to send messages in this chat',
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Determine receiver
            $receiverId = $chat->customer_id == $user->id ? $chat->vendor_id : $chat->customer_id;

            // Create message
            $message = Message::create([
                'booking_chat_id' => $chat->id,
                'sender_id' => $user->id,
                'receiver_id' => $receiverId,
                'booking_id' => $chat->booking_id,
                'message' => $request->message,
                'message_type' => $request->message_type,
                'chat_attachment_id' => $request->attachment_id,
                'chat_location_id' => $request->location_id,
                'parent_id' => $request->parent_id,
                'message_metadata' => $request->metadata,
                'undo_deadline' => now()->addSeconds(30), // 30 seconds to undo
            ]);

            // Update chat with new message
            $chat->updateWithNewMessage($message);

            DB::commit();

            // Load relationships for response
            $message->load(['sender.profile', 'chatAttachment', 'chatLocation']);

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => $message,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific message.
     */
    public function show($id): JsonResponse
    {
        $user = Auth::user();
        $message = Message::with([
            'sender.profile',
            'chatAttachment',
            'chatLocation',
            'messageReactions.user.profile',
            'bookingChat'
        ])->findOrFail($id);

        // Check if user is a participant in the chat
        if (!$message->bookingChat->hasParticipant($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view this message',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $message,
        ]);
    }

    /**
     * Edit a message.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:10000'],
        ]);

        $user = Auth::user();
        $message = Message::findOrFail($id);

        // Check if user owns this message
        if ($message->sender_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only edit your own messages',
            ], 403);
        }

        // Check if message can still be edited (15 minutes)
        if ($message->created_at->diffInMinutes(now()) > 15) {
            return response()->json([
                'success' => false,
                'message' => 'Message can only be edited within 15 minutes of sending',
            ], 422);
        }

        // Check if message is already being undone
        if ($message->is_undoing) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot edit a message that is being undone',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Store original message if not already stored
            if (!$message->original_message) {
                $message->original_message = $message->message;
            }

            // Update message
            $message->update([
                'message' => $request->message,
                'edited_at' => now(),
            ]);

            DB::commit();

            // Broadcast edit event
            broadcast(new MessageEdited($message))->toOthers();

            return response()->json([
                'success' => true,
                'message' => 'Message updated successfully',
                'data' => $message->load(['sender.profile']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update message',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a message.
     */
    public function destroy($id): JsonResponse
    {
        $user = Auth::user();
        $message = Message::findOrFail($id);

        // Check if user owns this message
        if ($message->sender_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only delete your own messages',
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Soft delete the message
            $message->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Message deleted successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete message',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Undo a recently sent message.
     */
    public function undo($id): JsonResponse
    {
        $user = Auth::user();
        $message = Message::findOrFail($id);

        // Check if user owns this message
        if ($message->sender_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only undo your own messages',
            ], 403);
        }

        // Check if message can still be undone
        if (!$message->undo_deadline || now()->isAfter($message->undo_deadline)) {
            return response()->json([
                'success' => false,
                'message' => 'Message can only be undone within 30 seconds of sending',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Mark message as undoing
            $message->update([
                'is_undoing' => true,
            ]);

            DB::commit();

            // Broadcast undo event
            broadcast(new MessageUndo($message))->toOthers();

            // Delete the message after broadcasting
            $message->delete();

            return response()->json([
                'success' => true,
                'message' => 'Message undone successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to undo message',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restore a deleted message.
     */
    public function restore($id): JsonResponse
    {
        $user = Auth::user();
        $message = Message::onlyTrashed()->findOrFail($id);

        // Check if user owns this message
        if ($message->sender_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only restore your own messages',
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Restore the message
            $message->restore();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Message restored successfully',
                'data' => $message->load(['sender.profile', 'chatAttachment', 'chatLocation']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to restore message',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark messages as read.
     */
    public function markAsRead(Request $request, $chatId): JsonResponse
    {
        $user = Auth::user();
        $chat = BookingChat::findOrFail($chatId);

        // Check if user is a participant
        if (!$chat->hasParticipant($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to mark messages in this chat as read',
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Mark unread messages as read
            $updatedCount = $chat->messages()
                ->where('receiver_id', $user->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            // Reset unread count for the user
            $chat->resetUnreadCount($user->id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Messages marked as read',
                'data' => [
                    'messages_marked' => $updatedCount,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark messages as read',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search messages within a chat.
     */
    public function search(Request $request, $chatId): JsonResponse
    {
        $request->validate([
            'query' => ['required', 'string', 'min:2'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $user = Auth::user();
        $chat = BookingChat::findOrFail($chatId);

        // Check if user is a participant
        if (!$chat->hasParticipant($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to search messages in this chat',
            ], 403);
        }

        $query = $request->get('query');
        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);

        $messages = $chat->messages()
            ->where('message', 'like', "%{$query}%")
            ->with(['sender.profile', 'chatAttachment', 'chatLocation'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    /**
     * Get typing users in a chat.
     */
    public function getTypingUsers($chatId): JsonResponse
    {
        $user = Auth::user();
        $chat = BookingChat::findOrFail($chatId);

        // Check if user is a participant
        if (!$chat->hasParticipant($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view typing status in this chat',
            ], 403);
        }

        // This would typically be handled by real-time events
        // For now, return empty array
        return response()->json([
            'success' => true,
            'data' => [
                'typing_users' => [],
            ],
        ]);
    }
}
