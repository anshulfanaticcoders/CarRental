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
            ->subject('New Review Submitted for Your Vehicle')
            ->greeting('Hello ' . $notifiable->first_name . ',')
            ->line('A new review has been submitted for your vehicle.')
            ->line('**Review Details:**')
            ->line('**Vehicle:** ' . $this->vehicle->brand . ' ' . $this->vehicle->model)
            ->line('**Rating:** ' . $this->review->rating . '/5')
            ->line('**Review Text:** ' . $this->review->review_text)
            ->line('Please review the submission and approve or reject it.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'review_id' => $this->review->id,
            'vehicle' => $this->vehicle->brand . ' ' . $this->vehicle->model,
            'rating' => $this->review->rating,
            'review_text' => $this->review->review_text,
            'message' => 'A new review has been submitted for ' . $this->vehicle->brand . ' ' . $this->vehicle->model,
        ];
    }
}