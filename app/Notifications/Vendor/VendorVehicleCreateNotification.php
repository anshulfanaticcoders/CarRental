<?php

namespace App\Notifications\Vendor;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorVehicleCreateNotification extends Notification
{
    use Queueable;

    protected $vehicle;
    protected $user;

    /**
     * Create a new notification instance.
     */
    public function __construct($vehicle, $user)
    {
        $this->vehicle = $vehicle;
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database']; // Notify via email and save to database
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Vehicle Listing Submitted - ' . config('app.name'))
            ->greeting('Hello ' . $this->user->first_name . ',')
            ->line('Thank you for adding a new vehicle to ' . config('app.name') . '!')
            ->line('**Vehicle Details:**')
            ->line('**Vehicle:** ' . $this->vehicle->brand . ' ' . $this->vehicle->model)
            ->line('**Location:** ' . ($this->vehicle->location ?? 'N/A'))
            ->line('**Status:** ' . ucfirst($this->vehicle->status ?? 'Pending'))
            ->line('Your listing is now under review and will be available once approved.')
            ->action('View Your Vehicles', route('current-vendor-vehicles.index', ['locale' => app()->getLocale()]))
            ->line('Thank you for being a ' . config('app.name') . ' partner!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Vehicle Submitted: ' . $this->vehicle->brand . ' ' . $this->vehicle->model,
            'vehicle_id' => $this->vehicle->id,
            'brand' => $this->vehicle->brand,
            'model' => $this->vehicle->model,
            'status' => $this->vehicle->status,
            'role' => 'vendor',
            'message' => 'Your vehicle listing has been submitted for approval.',
        ];
    }
}
