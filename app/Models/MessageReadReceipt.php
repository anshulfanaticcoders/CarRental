<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageReadReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'user_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * Get the message that owns the read receipt.
     */
    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Get the user that read the message.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark a message as read by a user.
     */
    public static function markAsRead(int $messageId, int $userId)
    {
        return self::updateOrCreate(
            ['message_id' => $messageId, 'user_id' => $userId],
            ['read_at' => now()]
        );
    }

    /**
     * Check if a message has been read by a user.
     */
    public static function isReadByUser(int $messageId, int $userId): bool
    {
        return self::where('message_id', $messageId)
            ->where('user_id', $userId)
            ->whereNotNull('read_at')
            ->exists();
    }

    /**
     * Get read status for a message.
     */
    public static function getReadStatus(int $messageId, int $senderId): array
    {
        // Get all participants in the conversation (excluding sender)
        $message = Message::with(['sender', 'receiver'])->find($messageId);

        if (!$message) {
            return ['read_by' => [], 'unread_by' => []];
        }

        $participants = [
            $message->receiver_id,
            // Add more participants if it's a group chat in the future
        ];

        $participants = array_filter($participants, function ($userId) use ($senderId) {
            return $userId != $senderId;
        });

        $readReceipts = self::where('message_id', $messageId)
            ->whereIn('user_id', $participants)
            ->whereNotNull('read_at')
            ->pluck('user_id')
            ->toArray();

        return [
            'read_by' => $readReceipts,
            'unread_by' => array_diff($participants, $readReceipts),
            'is_read_by_all' => count($readReceipts) === count($participants),
        ];
    }

    /**
     * Get detailed read information for a message.
     */
    public static function getReadDetails(int $messageId): \Illuminate\Support\Collection
    {
        return self::with('user')
            ->where('message_id', $messageId)
            ->whereNotNull('read_at')
            ->orderBy('read_at', 'asc')
            ->get()
            ->map(function ($receipt) {
                return [
                    'user_id' => $receipt->user_id,
                    'user_name' => $receipt->user->first_name . ' ' . $receipt->user->last_name,
                    'read_at' => $receipt->read_at,
                    'read_at_formatted' => $receipt->read_at->format('M j, Y g:i A'),
                ];
            });
    }
}
