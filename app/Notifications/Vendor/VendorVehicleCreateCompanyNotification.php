<?php

namespace App\Notifications\Vendor;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorVehicleCreateCompanyNotification extends Notification
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
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Vehicle Listing by Vendor - ' . config('app.name'))
            ->greeting('Hello,')
            ->line('A vendor associated with your company has added a new vehicle on ' . config('app.name') . '.')
            ->line('**Vehicle Details:**')
            ->line('**Vehicle:** ' . $this->vehicle->brand . ' ' . $this->vehicle->model)
            ->line('**Location:** ' . ($this->vehicle->location ?? 'N/A'))
            ->line('**Vendor:** ' . $this->user->first_name . ' ' . $this->user->last_name)
            ->action('View Vehicles', route('current-vendor-vehicles.index', ['locale' => app()->getLocale()]))
            ->line('Please review the new vehicle listing.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New Vehicle Listed',
            'role' => 'vendor',
            'vehicle_id' => $this->vehicle->id,
            'brand' => $this->vehicle->brand,
            'model' => $this->vehicle->model,
            'status' => $this->vehicle->status,
            'vendor_name' => $this->user->first_name . ' ' . $this->user->last_name,
            'message' => 'A new vehicle listing has been submitted by a vendor.',
        ];
    }
}