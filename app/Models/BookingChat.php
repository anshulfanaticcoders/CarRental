<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingChat extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_id',
        'customer_id',
        'vendor_id',
        'status',
        'last_message_at',
        'last_message_preview',
        'customer_unread_count',
        'vendor_unread_count',
        'customer_muted',
        'vendor_muted',
        'metadata',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'customer_muted' => 'boolean',
        'vendor_muted' => 'boolean',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'customer_unread_count' => 0,
        'vendor_unread_count' => 0,
        'customer_muted' => false,
        'vendor_muted' => false,
        'status' => 'active',
    ];

    /**
     * Get the booking that owns this chat.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the customer (user) that owns this chat.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Get the vendor (user) that owns this chat.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * Get the messages for this chat.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'booking_chat_id')->orderBy('created_at', 'asc');
    }

    /**
     * Get the attachments for this chat.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ChatAttachment::class);
    }

    /**
     * Get the locations for this chat.
     */
    public function locations(): HasMany
    {
        return $this->hasMany(ChatLocation::class);
    }

    /**
     * Get the message reactions for this chat.
     */
    public function messageReactions(): HasMany
    {
        return $this->hasMany(ChatMessageReaction::class);
    }

    /**
     * Scope a query to only include active chats.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include archived chats.
     */
    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    /**
     * Scope a query to only include chats for a specific user (either customer or vendor).
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('customer_id', $userId)
              ->orWhere('vendor_id', $userId);
        });
    }

    /**
     * Check if a user is a participant in this chat.
     */
    public function hasParticipant($userId)
    {
        return $this->customer_id == $userId || $this->vendor_id == $userId;
    }

    /**
     * Get the other participant in the chat (not the specified user).
     */
    public function getOtherParticipant($userId)
    {
        if ($this->customer_id == $userId) {
            return $this->vendor;
        } elseif ($this->vendor_id == $userId) {
            return $this->customer;
        }

        return null;
    }

    /**
     * Increment unread count for a specific user.
     */
    public function incrementUnreadCount($userId)
    {
        if ($this->customer_id == $userId) {
            $this->increment('customer_unread_count');
        } elseif ($this->vendor_id == $userId) {
            $this->increment('vendor_unread_count');
        }

        $this->touch();
    }

    /**
     * Reset unread count for a specific user.
     */
    public function resetUnreadCount($userId)
    {
        if ($this->customer_id == $userId) {
            $this->update(['customer_unread_count' => 0]);
        } elseif ($this->vendor_id == $userId) {
            $this->update(['vendor_unread_count' => 0]);
        }
    }

    /**
     * Update chat with new message information.
     */
    public function updateWithNewMessage($message)
    {
        $this->update([
            'last_message_at' => $message->created_at,
            'last_message_preview' => $this->generateMessagePreview($message),
        ]);

        // Increment unread count for the receiver
        $receiverId = $message->receiver_id;
        $this->incrementUnreadCount($receiverId);
    }

    /**
     * Generate a preview of the message for the chat list.
     */
    private function generateMessagePreview($message)
    {
        switch ($message->message_type) {
            case 'image':
                return '📷 Image';
            case 'video':
                return '🎥 Video';
            case 'audio':
                return '🎵 Voice message';
            case 'document':
                return '📄 Document';
            case 'location':
                return '📍 Location shared';
            case 'emoji':
                return $message->message;
            default:
                return $message->message ? substr($message->message, 0, 50) : '';
        }
    }

    /**
     * Get the total unread count for a user across all their chats.
     */
    public static function getTotalUnreadCount($userId)
    {
        return static::where(function ($query) use ($userId) {
                $query->where('customer_id', $userId)
                      ->orWhere('vendor_id', $userId);
            })
            ->when($userId, function ($query) use ($userId) {
                if ($query->where('customer_id', $userId)) {
                    $query->sum('customer_unread_count');
                } else {
                    $query->sum('vendor_unread_count');
                }
            })
            ->get()
            ->sum(function ($chat) use ($userId) {
                return $chat->customer_id == $userId ? $chat->customer_unread_count : $chat->vendor_unread_count;
            });
    }

    /**
     * Create a new chat session for a booking.
     */
    public static function createForBooking($booking)
    {
        return static::create([
            'booking_id' => $booking->id,
            'customer_id' => $booking->customer->user_id,
            'vendor_id' => $booking->vehicle->vendor_id,
            'status' => 'active',
        ]);
    }
}
