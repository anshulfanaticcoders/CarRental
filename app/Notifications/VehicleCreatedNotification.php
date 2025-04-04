<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VehicleCreatedNotification extends Notification
{
    use Queueable;

    protected $vehicle;

    /**
     * Create a new notification instance.
     */
    public function __construct($vehicle)
    {
        $this->vehicle = $vehicle;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database']; // Notify via email and store in database
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Vehicle Listed')
            ->greeting('Hello Admin,')
            ->line('A new vehicle has been listed by a vendor.')
            ->line('**Brand:** ' . $this->vehicle->brand)
            ->line('**Model:** ' . $this->vehicle->model)
            ->line('**Vendor ID:** ' . $this->vehicle->vendor_id)
            ->line('**Location:** ' . $this->vehicle->location)
            ->line('**Price per Day:** ' . ($this->vehicle->price_per_day ? '$' . number_format($this->vehicle->price_per_day, 2) : 'Not set'))
            // ->action('View Vehicle', url('/vehicles/' . $this->vehicle->id)) // Optional: Add a link to view the vehicle
            ->line('Please review the listing if necessary.');
    }

    /**
     * Get the array representation of the notification for the database.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'vehicle_id' => $this->vehicle->id,
            'brand' => $this->vehicle->brand,
            'model' => $this->vehicle->model,
            'vendor_id' => $this->vehicle->vendor_id,
            'location' => $this->vehicle->location,
            'price_per_day' => $this->vehicle->price_per_day,
            'message' => 'A new vehicle has been listed.',
        ];
    }
}