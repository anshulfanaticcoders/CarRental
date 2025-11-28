<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewChatMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('booking-chat.' . $this->message->booking_chat_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.new';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->message->id,
            'message' => $this->message->message,
            'message_type' => $this->message->message_type,
            'sender_id' => $this->message->sender_id,
            'receiver_id' => $this->message->receiver_id,
            'chat_id' => $this->message->booking_chat_id,
            'booking_id' => $this->message->booking_id,
            'attachment_id' => $this->message->chat_attachment_id,
            'location_id' => $this->message->chat_location_id,
            'parent_id' => $this->message->parent_id,
            'created_at' => $this->message->created_at,
            'undo_deadline' => $this->message->undo_deadline,
            'sender' => [
                'id' => $this->message->sender->id,
                'first_name' => $this->message->sender->first_name,
                'last_name' => $this->message->sender->last_name,
                'profile_image' => $this->message->sender->profile_image,
            ],
        ];
    }
}
