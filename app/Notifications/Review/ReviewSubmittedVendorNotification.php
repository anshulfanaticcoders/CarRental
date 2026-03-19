<?php

namespace App\Notifications\Review;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewSubmittedVendorNotification extends Notification
{
    use Queueable;

    protected $review;
    protected $vehicle;

    public function __construct($review, $vehicle)
    {
        $this->review = $review;
        $this->vehicle = $vehicle;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Review for Your Vehicle - ' . config('app.name'))
            ->greeting('Hello ' . $notifiable->first_name . ',')
            ->line('A customer has submitted a new review for your vehicle on ' . config('app.name') . '.')
            ->line('**Review Details:**')
            ->line('**Vehicle:** ' . $this->vehicle->brand . ' ' . $this->vehicle->model)
            ->line('**Rating:** ' . $this->review->rating . '/5')
            ->line('**Review:** ' . $this->review->review_text)
            ->action('View Your Reviews', url('/vendor/reviews'))
            ->line('Thank you for providing great service!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New Review: ' . $this->vehicle->brand . ' ' . $this->vehicle->model,
            'review_id' => $this->review->id,
            'vehicle' => $this->vehicle->brand . ' ' . $this->vehicle->model,
            'rating' => $this->review->rating,
            'review_text' => $this->review->review_text,
            'role' => 'vendor',
            'message' => 'A new review has been submitted for ' . $this->vehicle->brand . ' ' . $this->vehicle->model,
        ];
    }
}