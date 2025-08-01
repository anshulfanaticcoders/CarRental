<?php

namespace App\Notifications\Booking;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\UserProfile; // Add this line

class PendingBookingReminderNotification extends Notification
{
    use Queueable;

    protected $booking;
    protected $customer;
    protected $vehicle;
    protected $currencySymbol;

    /**
     * Create a new notification instance.
     */
    public function __construct($booking, $customer, $vehicle)
    {
        $this->booking = $booking;
        $this->customer = $customer;
        $this->vehicle = $vehicle;
        $this->currencySymbol = $this->getCurrencySymbol($vehicle);
    }

    protected function getCurrencySymbol($vehicle)
    {
        $vendorUserProfile = $vehicle->vendorProfile;
        return $vendorUserProfile ? $vendorUserProfile->currency : '$';
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
            ->subject('Reminder: Pending Booking - #' . $this->booking->booking_number)
            ->greeting('Hello ' . $this->vehicle->vendor->first_name . ',') // Assuming vendor is accessible via vehicle->vendor
            ->line('This is a reminder that you have a pending booking that requires your attention.')
            ->line('**Booking Details:**')
            ->line('**Booking Number:** ' . $this->booking->booking_number)
            ->line('**Vehicle:** ' . $this->vehicle->brand . ' ' . $this->vehicle->model)
            ->line('**Location:** ' . $this->vehicle->location)
            ->line('**Address:** ' . $this->vehicle->city . ', ' . $this->vehicle->state . ', ' .$this->vehicle->country)
            ->line('**Pickup Date:** ' . $this->booking->pickup_date->format('Y-m-d'))
            ->line('**Pickup Time:** ' . $this->booking->pickup_time)
            ->line('**Return Date:** ' . $this->booking->return_date->format('Y-m-d'))
            ->line('**Return Time:** ' . $this->booking->return_time)
            ->line('**Total Amount:** ' . $this->currencySymbol . number_format($this->booking->total_amount, 2))
            ->line('**Amount Paid:** ' . $this->currencySymbol . number_format($this->booking->amount_paid, 2))
            ->line('**Pending Amount:** ' . $this->currencySymbol . number_format($this->booking->pending_amount, 2))
            ->line('**Customer Details:**')
            ->line('**Name:** ' . $this->customer->first_name . ' ' . $this->customer->last_name)
            ->line('**Email:** ' . $this->customer->email)
            ->line('**Phone:** ' . ($this->customer->phone ?: 'Not provided'))
            ->line('Please review the booking and update its status to confirmed as needed.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'vehicle' => $this->vehicle->brand . ' ' . $this->vehicle->model,
            'location' => $this->vehicle->location,
            'address' => $this->vehicle->city . ', ' . $this->vehicle->state . ', ' .$this->vehicle->country,
            'pickup_date' => $this->booking->pickup_date->format('Y-m-d'),
            'pickup_time' => $this->booking->pickup_time,
            'return_date' => $this->booking->return_date->format('Y-m-d'),
            'return_time' => $this->booking->return_time,
            'total_amount' => $this->booking->total_amount,
            'amount_paid' => $this->booking->amount_paid,
            'pending_amount' => $this->booking->pending_amount,
            'customer_name' => $this->customer->first_name . ' ' . $this->customer->last_name,
            'currency_symbol' => $this->currencySymbol,
            'message' => 'Reminder: You have a pending booking that requires your attention.',
        ];
    }
}
