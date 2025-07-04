<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class BulkVehicleUploadAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $vehicles;
    public $user;
    public $errorMessages;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Collection $vehicles, $user, array $errorMessages = [])
    {
        $this->vehicles = $vehicles;
        $this->user = $user;
        $this->errorMessages = $errorMessages;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail','database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $mailMessage = (new MailMessage)
            ->subject('Bulk Vehicle Upload Summary')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A bulk upload of vehicles has been completed by vendor:');
        
        $mailMessage->line('**Vendor Name:** ' . $this->user->name);
        $mailMessage->line('**Vendor Email:** ' . $this->user->email);

        if ($this->vehicles->isNotEmpty()) {
            $mailMessage->line('Successfully created ' . $this->vehicles->count() . ' vehicle(s).');
            $mailMessage->action('View Uploaded Vehicles', route('current-vendor-vehicles.index', ['locale' => app()->getLocale()]));
        } else {
            $mailMessage->line('A bulk upload of vehicles was attempted, but no vehicles were successfully created.');
        }

        if (!empty($this->errorMessages)) {
            $mailMessage->line('The following errors occurred during the upload:');
            foreach ($this->errorMessages as $error) {
                $mailMessage->line('- ' . $error);
            }
        }

        $mailMessage->line('Thank you for using our application!');

        return $mailMessage;
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
            'vehicles_count' => $this->vehicles->count(),
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'error_messages' => $this->errorMessages,
            'message' => 'Bulk vehicle upload completed by ' . $this->user->name . '. Successfully created ' . $this->vehicles->count() . ' vehicle(s).',
        ];
    }
}
