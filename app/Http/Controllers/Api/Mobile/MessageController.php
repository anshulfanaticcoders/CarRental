<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Events\MessageRead;
use App\Events\NewMessage;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function inbox(Request $request): JsonResponse
    {
        $user = $request->user();

        // Find all booking IDs the user participates in (as customer)
        $customerBookings = Booking::with(['vehicle.vendor', 'customer'])
            ->whereHas('customer', fn ($q) => $q->where('user_id', $user->id))
            ->whereHas('vehicle')
            ->get();

        $threads = [];
        foreach ($customerBookings as $b) {
            $otherUserId = $b->vehicle?->vendor_id;
            if (! $otherUserId) continue;

            $lastMessage = Message::where('booking_id', $b->id)
                ->orderByDesc('created_at')
                ->first();

            $unread = Message::where('booking_id', $b->id)
                ->where('receiver_id', $user->id)
                ->whereNull('read_at')
                ->count();

            // Only show threads with at least one message OR confirmed bookings (so user can start a chat)
            if (! $lastMessage && ! in_array($b->booking_status, ['confirmed', 'completed'], true)) continue;

            $other = User::with('profile')->find($otherUserId);
            $threads[] = [
                'booking_id' => $b->id,
                'booking_number' => $b->booking_number,
                'vehicle_name' => $b->vehicle_name ?? trim(($b->vehicle?->brand ?? '').' '.($b->vehicle?->model ?? '')),
                'other_user' => $other ? [
                    'id' => $other->id,
                    'name' => trim(($other->first_name ?? '').' '.($other->last_name ?? '')) ?: 'Vendor',
                    'avatar' => $other->profile?->avatar,
                ] : null,
                'last_message' => $lastMessage ? [
                    'message' => $lastMessage->message,
                    'sender_id' => $lastMessage->sender_id,
                    'is_mine' => $lastMessage->sender_id === $user->id,
                    'created_at' => $lastMessage->created_at?->toIso8601String(),
                    'has_attachment' => ! empty($lastMessage->file_path) || ! empty($lastMessage->voice_note_path),
                ] : null,
                'unread_count' => $unread,
                'updated_at' => $lastMessage?->created_at?->toIso8601String() ?? $b->created_at?->toIso8601String(),
            ];
        }

        usort($threads, fn ($a, $b) => strcmp($b['updated_at'] ?? '', $a['updated_at'] ?? ''));

        return response()->json(['threads' => $threads]);
    }

    public function thread(Request $request, int $bookingId): JsonResponse
    {
        $user = $request->user();
        $booking = Booking::with(['vehicle.vendor', 'customer'])->find($bookingId);
        if (! $booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        $customerUserId = $booking->customer?->user_id;
        $vendorUserId = $booking->vehicle?->vendor_id;
        if ($user->id !== $customerUserId && $user->id !== $vendorUserId) {
            return response()->json(['message' => 'Not authorized for this thread.'], 403);
        }

        $otherUserId = $user->id === $customerUserId ? $vendorUserId : $customerUserId;
        $otherUser = User::with('profile')->find($otherUserId);

        $messages = Message::where('booking_id', $bookingId)
            ->where(function ($q) use ($user, $otherUserId) {
                $q->where(function ($q2) use ($user, $otherUserId) {
                    $q2->where('sender_id', $user->id)->where('receiver_id', $otherUserId);
                })->orWhere(function ($q2) use ($user, $otherUserId) {
                    $q2->where('sender_id', $otherUserId)->where('receiver_id', $user->id);
                });
            })
            ->orderBy('created_at', 'asc')
            ->limit(200)
            ->get();

        return response()->json([
            'booking' => [
                'id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'vehicle_name' => $booking->vehicle_name ?? trim(($booking->vehicle?->brand ?? '').' '.($booking->vehicle?->model ?? '')),
                'pickup_date' => $booking->pickup_date,
                'return_date' => $booking->return_date,
            ],
            'other_user' => $otherUser ? [
                'id' => $otherUser->id,
                'name' => trim(($otherUser->first_name ?? '').' '.($otherUser->last_name ?? '')) ?: 'Vendor',
                'avatar' => $otherUser->profile?->avatar,
            ] : null,
            'messages' => $messages->map(fn ($m) => $this->transformMessage($m, $user->id))->values(),
            'other_user_id' => $otherUserId,
        ]);
    }

    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'booking_id' => ['required', 'integer'],
            'receiver_id' => ['required', 'integer'],
            'message' => ['required', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        $user = $request->user();
        $booking = Booking::with(['vehicle', 'customer'])->find($data['booking_id']);
        if (! $booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }
        $customerUserId = $booking->customer?->user_id;
        $vendorUserId = $booking->vehicle?->vendor_id;
        if ($user->id !== $customerUserId && $user->id !== $vendorUserId) {
            return response()->json(['message' => 'Not authorized.'], 403);
        }
        if (! in_array($data['receiver_id'], [$customerUserId, $vendorUserId], true) || $data['receiver_id'] === $user->id) {
            return response()->json(['message' => 'Invalid receiver.'], 422);
        }

        $message = Message::create([
            'sender_id' => $user->id,
            'receiver_id' => $data['receiver_id'],
            'booking_id' => $data['booking_id'],
            'message' => $data['message'],
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        try {
            broadcast(new NewMessage($message))->toOthers();
        } catch (\Throwable $e) {
            // ignore broadcast failures (pusher not configured / network blip)
        }

        return response()->json([
            'message' => $this->transformMessage($message->fresh(), $user->id),
        ], 201);
    }

    public function markRead(Request $request, int $bookingId): JsonResponse
    {
        $user = $request->user();
        $booking = Booking::with(['vehicle', 'customer'])->find($bookingId);
        if (! $booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }
        $customerUserId = $booking->customer?->user_id;
        $vendorUserId = $booking->vehicle?->vendor_id;
        if ($user->id !== $customerUserId && $user->id !== $vendorUserId) {
            return response()->json(['message' => 'Not authorized.'], 403);
        }

        $now = now();
        Message::where('booking_id', $bookingId)
            ->where('receiver_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => $now]);

        try {
            $lastMessage = Message::where('booking_id', $bookingId)
                ->where('receiver_id', $user->id)
                ->orderByDesc('created_at')
                ->first();
            if ($lastMessage) {
                $other = User::find($lastMessage->sender_id);
                if ($other) {
                    broadcast(new MessageRead($lastMessage, $user, $other->id))->toOthers();
                }
            }
        } catch (\Throwable $e) {
            // ignore broadcast failures
        }

        return response()->json(['success' => true]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        $count = Message::where('receiver_id', $user->id)
            ->whereNull('read_at')
            ->count();
        return response()->json(['unread_count' => $count]);
    }

    private function transformMessage(Message $m, int $currentUserId): array
    {
        $host = rtrim(request()->getSchemeAndHttpHost(), '/');
        $fileUrl = $m->file_path
            ? (preg_match('#^https?://#i', $m->file_path) ? $m->file_path : $host.'/storage/'.ltrim($m->file_path, '/'))
            : null;
        $voiceUrl = $m->voice_note_path
            ? (preg_match('#^https?://#i', $m->voice_note_path) ? $m->voice_note_path : $host.'/storage/'.ltrim($m->voice_note_path, '/'))
            : null;

        return [
            'id' => $m->id,
            'booking_id' => $m->booking_id,
            'sender_id' => $m->sender_id,
            'receiver_id' => $m->receiver_id,
            'parent_id' => $m->parent_id,
            'message' => $m->message,
            'is_mine' => $m->sender_id === $currentUserId,
            'file_url' => $fileUrl,
            'file_name' => $m->file_name,
            'file_type' => $m->file_type,
            'voice_note_url' => $voiceUrl,
            'read_at' => $m->read_at?->toIso8601String(),
            'created_at' => $m->created_at?->toIso8601String(),
        ];
    }
}
