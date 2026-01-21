<?php

namespace App\Notifications\Booking;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use App\Notifications\Concerns\FormatsBookingAmounts;

class GuestBookingCreatedNotification extends Notification
{
    use Queueable;
    use FormatsBookingAmounts;

    protected $booking;
    protected $customer;
    protected $vehicle;
    protected $tempPassword;

    public function __construct($booking, $customer, $vehicle, string $tempPassword)
    {
        $this->booking = $booking;
        $this->customer = $customer;
        $this->vehicle = $vehicle;
        $this->tempPassword = $tempPassword;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amounts = $this->getCustomerAmounts($this->booking);
        $loginUrl = route('login', ['locale' => app()->getLocale()]);

        return (new MailMessage)
            ->subject('Your Booking Confirmation + Account Access')
            ->greeting('Hello ' . $this->customer->first_name . ',')
            ->line('Thank you for your booking! Your reservation is confirmed.')
            ->line('**Booking Details:**')
            ->line('**Booking Number:** ' . $this->booking->booking_number)
            ->line('**Vehicle:** ' . ($this->vehicle->brand ?? '') . ' ' . ($this->vehicle->model ?? ''))
            ->line('**Pickup Date:** ' . $this->formatDate($this->booking->pickup_date))
            ->line('**Pickup Time:** ' . $this->booking->pickup_time)
            ->line('**Return Date:** ' . $this->formatDate($this->booking->return_date))
            ->line('**Return Time:** ' . $this->booking->return_time)
            ->line('**Total Amount:** ' . $this->formatCurrencyAmount($amounts['total'], $amounts['currency']))
            ->line('**Amount Paid:** ' . $this->formatCurrencyAmount($amounts['paid'], $amounts['currency']))
            ->line('**Pending Amount:** ' . $this->formatCurrencyAmount($amounts['pending'], $amounts['currency']))
            ->line('---')
            ->line('**Account Access (Guest Checkout):**')
            ->line('We created an account for you so you can manage your booking anytime.')
            ->line('**Login Email:** ' . $this->customer->email)
            ->line('**Temporary Password:** ' . $this->tempPassword)
            ->action('Login and Change Password', $loginUrl)
            ->line('Please log in and change your password after signing in.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'message' => 'Your booking is confirmed. Account access details were sent to your email.'
        ];
    }

    private function formatDate($value): string
    {
        if ($value instanceof Carbon) {
            return $value->format('Y-m-d');
        }

        return (string) $value;
    }
}
