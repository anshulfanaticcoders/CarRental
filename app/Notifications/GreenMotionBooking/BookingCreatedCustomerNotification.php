<?php

namespace App\Notifications\GreenMotionBooking;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\GreenMotionBooking;
use App\Models\User;

class BookingCreatedCustomerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $booking;
    public $customer;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(GreenMotionBooking $booking, User $customer)
    {
        $this->booking = $booking;
        $this->customer = $customer;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your Green Motion Booking Confirmation')
            ->line('Dear ' . $this->customer->name . ',')
            ->line('Thank you for your booking. Here are your booking details:')
            ->line('Booking Reference: ' . $this->booking->greenmotion_booking_ref)
            ->line('Vehicle ID: ' . $this->booking->vehicle_id)
            ->line('Pickup Date: ' . $this->booking->start_date)
            ->line('Return Date: ' . $this->booking->end_date)
            ->line('Total Amount: ' . $this->booking->grand_total . ' ' . $this->booking->currency)
            ->line('Thank you for choosing our service!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
