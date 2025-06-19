<?php

namespace App\Notifications\Review;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewSubmittedAdminNotification extends Notification
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
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Review Submitted')
            ->greeting('Hello Admin,')
            ->line('A new review has been submitted for a vehicle.')
            ->line('**Review Details:**')
            ->line('**Vehicle:** ' . $this->vehicle->brand . ' ' . $this->vehicle->model)
            ->line('**Rating:** ' . $this->review->rating . '/5')
            ->line('**Review Text:** ' . $this->review->review_text)
            ->action('View Review', url('/admin/reviews/' . $this->review->id))
            ->line('Please review and approve or reject the submission.');
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