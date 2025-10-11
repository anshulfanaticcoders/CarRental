<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatTypingStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'booking_id',
        'is_typing',
        'last_activity_at',
    ];

    protected $casts = [
        'is_typing' => 'boolean',
        'last_activity_at' => 'datetime',
    ];

    /**
     * Get the user that owns the typing status.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the booking that owns the typing status.
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Scope a query to only include active typing statuses.
     */
    public function scopeTyping($query)
    {
        return $query->where('is_typing', true);
    }

    /**
     * Scope a query to only include recent activity (within last 5 seconds).
     */
    public function scopeRecent($query)
    {
        return $query->where('last_activity_at', '>', now()->subSeconds(5));
    }

    /**
     * Mark user as typing in a booking.
     */
    public static function startTyping(int $userId, int $bookingId)
    {
        return self::updateOrCreate(
            ['user_id' => $userId, 'booking_id' => $bookingId],
            [
                'is_typing' => true,
                'last_activity_at' => now(),
            ]
        );
    }

    /**
     * Mark user as stopped typing in a booking.
     */
    public static function stopTyping(int $userId, int $bookingId)
    {
        return self::where('user_id', $userId)
            ->where('booking_id', $bookingId)
            ->update([
                'is_typing' => false,
                'last_activity_at' => now(),
            ]);
    }

    /**
     * Clean up old typing statuses (older than 10 seconds).
     */
    public static function cleanupOldTypingStatuses()
    {
        return self::where('last_activity_at', '<', now()->subSeconds(10))
            ->where('is_typing', true)
            ->update(['is_typing' => false]);
    }

    /**
     * Get currently typing users for a booking.
     */
    public static function getTypingUsers(int $bookingId, int $excludeUserId = null)
    {
        $query = self::with('user')
            ->where('booking_id', $bookingId)
            ->typing()
            ->recent();

        if ($excludeUserId) {
            $query->where('user_id', '!=', $excludeUserId);
        }

        return $query->get()->map(function ($status) {
            return [
                'user_id' => $status->user_id,
                'user_name' => $status->user->first_name . ' ' . $status->user->last_name,
                'last_activity_at' => $status->last_activity_at,
            ];
        });
    }
}
