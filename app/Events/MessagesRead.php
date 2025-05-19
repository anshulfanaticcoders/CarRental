<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessagesRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $bookingId;
    public $readerId; // The ID of the user who read the messages
    public $readAtTimestamp; // The timestamp when messages were read

    /**
     * Create a new event instance.
     *
     * @param int $bookingId
     * @param int $readerId
     * @param string $readAtTimestamp
     */
    public function __construct($bookingId, $readerId, $readAtTimestamp)
    {
        $this->bookingId = $bookingId;
        $this->readerId = $readerId;
        $this->readAtTimestamp = $readAtTimestamp;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // Broadcast on the private channel for this specific chat/booking
        return new PrivateChannel('chat.' . $this->bookingId);
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'messages.read'; // Custom event name for the client
    }
}
