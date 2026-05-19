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
use Illuminate\Support\Str;

class MessageController extends Controller
{
    public function inbox(Request $request): JsonResponse
    {
        return $this->buildInbox($request->user(), 'customer');
    }

    public function vendorInbox(Request $request): JsonResponse
    {
        return $this->buildInbox($request->user(), 'vendor');
    }

    private function buildInbox(User $user, string $role): JsonResponse
    {
        $query = Booking::with(['vehicle.vendor', 'customer'])->whereHas('vehicle');

        if ($role === 'vendor') {
            $query->whereHas('vehicle', fn ($q) => $q->where('vendor_id', $user->id));
        } else {
            $query->whereHas('customer', fn ($q) => $q->where('user_id', $user->id));
        }

        $bookings = $query->get();

        $threads = [];
        foreach ($bookings as $b) {
            $otherUserId = $role === 'vendor' ? $b->customer?->user_id : $b->vehicle?->vendor_id;
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
            $defaultName = $role === 'vendor' ? 'Customer' : 'Vendor';
            $threads[] = [
                'booking_id' => $b->id,
                'booking_number' => $b->booking_number,
                'vehicle_name' => $b->vehicle_name ?? trim(($b->vehicle?->brand ?? '').' '.($b->vehicle?->model ?? '')),
                'other_user' => $other ? [
                    'id' => $other->id,
                    'name' => trim(($other->first_name ?? '').' '.($other->last_name ?? '')) ?: $defaultName,
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
            // Text becomes optional when a voice note or file attachment is sent.
            'message' => ['nullable', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'integer'],
            'voice_note' => ['nullable', 'file', 'max:10240', 'mimes:mp3,wav,ogg,webm,m4a,aac,mp4'],
            'file' => ['nullable', 'file', 'max:10240', 'mimes:jpeg,png,jpg,gif,pdf,doc,docx,xls,xlsx,txt'],
        ]);

        $hasText = ! empty(trim((string) ($data['message'] ?? '')));
        $hasVoice = $request->hasFile('voice_note');
        $hasFile = $request->hasFile('file');
        if (! $hasText && ! $hasVoice && ! $hasFile) {
            return response()->json(['message' => 'Message body, voice note, or attachment is required.'], 422);
        }

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
        // Coerce receiver_id to int — multipart FormData sends it as a string,
        // and the strict in_array check rejected it against the DB integer ids.
        $receiverId = (int) $data['receiver_id'];
        if (! in_array($receiverId, [$customerUserId, $vendorUserId], true) || $receiverId === $user->id) {
            return response()->json(['message' => 'Invalid receiver.'], 422);
        }

        $payload = [
            'sender_id' => $user->id,
            'receiver_id' => $receiverId,
            'booking_id' => (int) $data['booking_id'],
            'message' => $hasText ? $data['message'] : null,
            'parent_id' => isset($data['parent_id']) ? (int) $data['parent_id'] : null,
        ];

        if ($hasVoice) {
            $voice = $request->file('voice_note');
            $name = 'voice_' . now()->timestamp . '_' . \Illuminate\Support\Str::random(6) . '.' . $voice->getClientOriginalExtension();
            // Match the web MessageController + Message model accessor — both
            // use the 'upcloud' disk, so the resulting URL is reachable from
            // both web and mobile clients via Pusher broadcasts.
            $stored = $voice->storeAs('voice_notes', $name, 'upcloud');
            $payload['voice_note_path'] = $stored;
        }

        if ($hasFile) {
            $file = $request->file('file');
            $name = 'attachment_' . now()->timestamp . '_' . Str::random(12) . '.' . $file->getClientOriginalExtension();
            $stored = $file->storeAs('chat_attachments', $name, 'upcloud');
            $payload['file_path'] = $stored;
            $payload['file_name'] = $file->getClientOriginalName();
            $payload['file_type'] = $file->getMimeType();
            $payload['file_size'] = $file->getSize();
        }

        $message = Message::create($payload);

        try {
            $message->load(['sender', 'receiver']);
            broadcast(new NewMessage($message))->toOthers();
        } catch (\Throwable $e) {
            // ignore broadcast failures (pusher not configured / network blip)
        }

        return response()->json([
            'message' => $this->transformMessage($message->fresh(), $user->id),
        ], 201);
    }

    public function typing(Request $request, int $bookingId): JsonResponse
    {
        $isTyping = filter_var($request->input('is_typing', true), FILTER_VALIDATE_BOOLEAN);
        $user = $request->user();
        try {
            broadcast(new \App\Events\TypingIndicator($user, $bookingId, $isTyping))->toOthers();
        } catch (\Throwable $e) {
            // ignore broadcast failures
        }
        return response()->json(['success' => true]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $message = Message::find($id);
        if (! $message) {
            return response()->json(['message' => 'Message not found.'], 404);
        }
        if ($message->sender_id !== $user->id) {
            return response()->json(['message' => 'Only the sender can delete.'], 403);
        }
        $message->delete();

        try {
            broadcast(new \App\Events\MessageDeleted($message))->toOthers();
        } catch (\Throwable $e) {
            // ignore
        }

        return response()->json(['success' => true, 'message_id' => $id]);
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $message = Message::withTrashed()->find($id);
        if (! $message) {
            return response()->json(['message' => 'Message not found.'], 404);
        }
        if ($message->sender_id !== $user->id) {
            return response()->json(['message' => 'Only the sender can restore.'], 403);
        }
        if (! $message->trashed()) {
            return response()->json(['success' => true, 'message' => $this->transformMessage($message, $user->id)]);
        }
        $message->restore();

        try {
            $message->load(['sender', 'receiver']);
            broadcast(new \App\Events\MessageRestored($message))->toOthers();
        } catch (\Throwable $e) {
            // ignore
        }

        return response()->json([
            'success' => true,
            'message' => $this->transformMessage($message->fresh(), $user->id),
        ]);
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
        // Files + voice notes live on the 'upcloud' disk (S3-compatible). The
        // model's accessors already build the right URL; fall back to the raw
        // path only if the accessor returns nothing.
        $fileUrl = $m->file_path
            ? (preg_match('#^https?://#i', $m->file_path)
                ? $m->file_path
                : \Illuminate\Support\Facades\Storage::disk('upcloud')->url($m->file_path))
            : null;
        $voiceUrl = $m->voice_note_path
            ? (preg_match('#^https?://#i', $m->voice_note_path)
                ? $m->voice_note_path
                : \Illuminate\Support\Facades\Storage::disk('upcloud')->url($m->voice_note_path))
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
