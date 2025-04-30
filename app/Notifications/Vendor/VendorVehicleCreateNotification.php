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
        return ['mail']; // Notify via email only
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Vehicle Listing Submitted')
            ->greeting('Hello ' . $this->user->first_name . ',')
            ->line('Thank you for adding a new vehicle to our platform!')
            ->line('**Vehicle Details:**')
            ->line('**Brand:** ' . $this->vehicle->brand)
            ->line('**Model:** ' . $this->vehicle->model)
            ->line('Thank you for contributing to our platform!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'vehicle_id' => $this->vehicle->id,
            'brand' => $this->vehicle->brand,
            'model' => $this->vehicle->model,
            'status' => $this->vehicle->status,
            'message' => 'Your vehicle listing has been submitted for approval.',
        ];
    }
}