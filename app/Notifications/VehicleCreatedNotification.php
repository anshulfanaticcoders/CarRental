<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
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
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $addressParts = array_filter([
            $this->vehicle->city,
            $this->vehicle->state,
            $this->vehicle->country,
        ]);

        return (new MailMessage)
            ->subject('New Vehicle Listed - '.config('app.name'))
            ->greeting('Hello Admin,')
            ->line('A new vehicle has been listed on '.config('app.name').'.')
            ->line('**Vehicle Details:**')
            ->line('**Vehicle:** '.$this->vehicle->brand.' '.$this->vehicle->model)
            ->line('**Location:** '.($this->vehicle->location ?? 'N/A'))
            ->line('**Address:** '.(! empty($addressParts) ? implode(', ', $addressParts) : 'N/A'))
            ->line('**Price per Day:** '.($this->vehicle->price_per_day ? number_format($this->vehicle->price_per_day, 2).' EUR' : 'Not set'))
            ->action('Review Vehicles', url('/vendor-vehicles'))
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
            'title' => 'New Vehicle Listed',
            'role' => 'admin',
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
