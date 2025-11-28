<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessageReaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_chat_id',
        'message_id',
        'user_id',
        'emoji',
        'emoji_unicode',
    ];

    /**
     * Get the booking chat that owns the reaction.
     */
    public function bookingChat(): BelongsTo
    {
        return $this->belongsTo(BookingChat::class);
    }

    /**
     * Get the message that owns the reaction.
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Get the user that owns the reaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to get reactions for a specific emoji.
     */
    public function scopeForEmoji($query, $emoji)
    {
        return $query->where('emoji', $emoji);
    }

    /**
     * Scope a query to get reactions for a specific message.
     */
    public function scopeForMessage($query, $messageId)
    {
        return $query->where('message_id', $messageId);
    }

    /**
     * Scope a query to get reactions from a specific user.
     */
    public function scopeFromUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get grouped reactions for a message with user counts.
     */
    public static function getGroupedReactionsForMessage($messageId)
    {
        return static::where('message_id', $messageId)
            ->select('emoji', 'emoji_unicode')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('GROUP_CONCAT(user_id) as user_ids')
            ->groupBy('emoji', 'emoji_unicode')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($reaction) {
                return [
                    'emoji' => $reaction->emoji,
                    'emoji_unicode' => $reaction->emoji_unicode,
                    'count' => $reaction->count,
                    'user_ids' => explode(',', $reaction->user_ids),
                ];
            });
    }

    /**
     * Check if a user has already reacted to a message with a specific emoji.
     */
    public static function hasUserReacted($messageId, $userId, $emoji)
    {
        return static::where('message_id', $messageId)
            ->where('user_id', $userId)
            ->where('emoji', $emoji)
            ->exists();
    }

    /**
     * Get all emojis that a user has reacted with for a specific message.
     */
    public static function getUserReactionsForMessage($messageId, $userId)
    {
        return static::where('message_id', $messageId)
            ->where('user_id', $userId)
            ->pluck('emoji')
            ->toArray();
    }

    /**
     * Toggle a user's reaction to a message.
     * If the user has already reacted with the same emoji, remove it.
     * Otherwise, add the new reaction.
     */
    public static function toggleReaction($messageId, $userId, $emoji, $bookingChatId)
    {
        $existingReaction = static::where('message_id', $messageId)
            ->where('user_id', $userId)
            ->where('emoji', $emoji)
            ->first();

        if ($existingReaction) {
            // Remove the reaction
            $existingReaction->delete();
            return ['action' => 'removed', 'reaction' => $existingReaction];
        } else {
            // Add the new reaction
            $reaction = static::create([
                'booking_chat_id' => $bookingChatId,
                'message_id' => $messageId,
                'user_id' => $userId,
                'emoji' => $emoji,
                'emoji_unicode' => self::getEmojiUnicode($emoji),
            ]);
            return ['action' => 'added', 'reaction' => $reaction];
        }
    }

    /**
     * Get the Unicode representation of an emoji.
     */
    public static function getEmojiUnicode($emoji)
    {
        // Convert emoji to its Unicode representation
        return json_encode($emoji);
    }

    /**
     * Get popular emojis for quick reactions.
     */
    public static function getPopularEmojis()
    {
        return [
            ['emoji' => '👍', 'unicode' => '👍', 'name' => 'thumbs_up'],
            ['emoji' => '❤️', 'unicode' => '❤️', 'name' => 'heart'],
            ['emoji' => '😊', 'unicode' => '😊', 'name' => 'smile'],
            ['emoji' => '😂', 'unicode' => '😂', 'name' => 'laugh'],
            ['emoji' => '🎉', 'unicode' => '🎉', 'name' => 'party'],
            ['emoji' => '😮', 'unicode' => '😮', 'name' => 'wow'],
            ['emoji' => '😢', 'unicode' => '😢', 'name' => 'sad'],
            ['emoji' => '👎', 'unicode' => '👎', 'name' => 'thumbs_down'],
            ['emoji' => '🙏', 'unicode' => '🙏', 'name' => 'pray'],
            ['emoji' => '🔥', 'unicode' => '🔥', 'name' => 'fire'],
        ];
    }

    /**
     * Get recently used emojis for a user across all chats.
     */
    public static function getUserRecentEmojis($userId, $limit = 10)
    {
        return static::where('user_id', $userId)
            ->select('emoji', 'emoji_unicode')
            ->selectRaw('MAX(created_at) as last_used')
            ->groupBy('emoji', 'emoji_unicode')
            ->orderBy('last_used', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($reaction) {
                return [
                    'emoji' => $reaction->emoji,
                    'unicode' => $reaction->emoji_unicode,
                ];
            });
    }

    /**
     * Get reaction statistics for a chat.
     */
    public static function getChatReactionStats($bookingChatId)
    {
        return static::where('booking_chat_id', $bookingChatId)
            ->select('emoji')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('emoji')
            ->orderBy('count', 'desc')
            ->get();
    }

    /**
     * Remove all reactions from a user for a specific message.
     */
    public static function removeUserReactionsFromMessage($messageId, $userId)
    {
        return static::where('message_id', $messageId)
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * Remove all reactions for a message.
     */
    public static function removeAllReactionsFromMessage($messageId)
    {
        return static::where('message_id', $messageId)->delete();
    }

    /**
     * Check if a reaction belongs to a specific user.
     */
    public function belongsToUser($userId)
    {
        return $this->user_id == $userId;
    }

    /**
     * Format the reaction for API response.
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'emoji' => $this->emoji,
            'emoji_unicode' => $this->emoji_unicode,
            'user' => $this->user->only(['id', 'first_name', 'last_name', 'profile_image']),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
