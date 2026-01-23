<?php

namespace App\Events;

use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $user;
    public $recipientId;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message, User $user, int $recipientId)
    {
        $this->message = $message;
        $this->user = $user;
        $this->recipientId = $recipientId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->message->booking_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.read';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $readAt = $this->message->read_at;
        if ($readAt && !$readAt instanceof \Carbon\Carbon) {
            $readAt = \Carbon\Carbon::parse($readAt);
        }

        return [
            'message_id' => $this->message->id,
            'user_id' => $this->user->id,
            'user_name' => $this->user->first_name . ' ' . $this->user->last_name,
            'recipient_id' => $this->recipientId,
            'booking_id' => $this->message->booking_id,
            'read_at' => $readAt ? $readAt->toISOString() : now()->toISOString(),
        ];
    }
}
