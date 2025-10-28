<?php

namespace App\Notifications\Affiliate;

use App\Models\Affiliate\AffiliateBusiness;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BusinessStatusChangedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private AffiliateBusiness $business,
        private string $oldStatus,
        private string $newStatus
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $dashboardUrl = route('affiliate.business.dashboard', [
            'token' => $this->business->dashboard_access_token,
            'locale' => 'en',
        ]);

        $statusInfo = $this->getStatusInfo($this->newStatus);

        return (new MailMessage)
            ->subject($statusInfo['subject'])
            ->greeting('Hello ' . $this->business->name . ' Team!')
            ->line($statusInfo['message'])
            ->line('Your business status has been updated from **' . ucfirst($this->oldStatus) . '** to **' . ucfirst($this->newStatus) . '**.')
            ->line($statusInfo['description'])
            ->when($this->newStatus === 'active', function ($message) use ($dashboardUrl) {
                $message->action('Access Your Dashboard', $dashboardUrl)
                       ->line('You can now access your affiliate dashboard and start generating QR codes for your business locations.');
            })
            ->when($this->newStatus !== 'active', function ($message) {
                $message->line('If you have any questions about this status change, please don\'t hesitate to contact our support team.');
            })
            ->line('**Business Details:**')
            ->line('• **Business Name:** ' . $this->business->name)
            ->line('• **Business Type:** ' . ucfirst(str_replace('_', ' ', $this->business->business_type)))
            ->line('• **Status Change Date:** ' . now()->format('M j, Y \a\t g:i A'))
            ->line('')
            ->salutation('Best regards,')
            ->salutation('The Vrooem Team');
    }

    /**
     * Get status-specific information
     */
    private function getStatusInfo(string $status): array
    {
        switch ($status) {
            case 'active':
                return [
                    'subject' => '🎉 Your Business Has Been Approved!',
                    'message' => 'Congratulations! Your business has been approved and is now active in the Vrooem Affiliate Program.',
                    'description' => 'You can now access all features of your affiliate dashboard, generate QR codes, and start earning commissions.'
                ];
            case 'pending':
                return [
                    'subject' => '⏳ Your Business Status is Pending Review',
                    'message' => 'Your business status has been set to pending.',
                    'description' => 'Your business is currently under review. We will notify you once there are any updates to your status.'
                ];
            case 'inactive':
                return [
                    'subject' => '⚠️ Your Business Account is Now Inactive',
                    'message' => 'Your business account has been deactivated.',
                    'description' => 'Your account is currently inactive. This may be due to account maintenance or policy updates. Please contact support for assistance.'
                ];
            case 'suspended':
                return [
                    'subject' => '🚨 Your Business Account Has Been Suspended',
                    'message' => 'Your business account has been temporarily suspended.',
                    'description' => 'Your account has been suspended due to policy violations or other concerns. Please contact our support team to resolve this issue.'
                ];
            default:
                return [
                    'subject' => 'Your Business Status Has Been Updated',
                    'message' => 'Your business status has been updated.',
                    'description' => 'Your account status has been changed. Please check your dashboard for more information.'
                ];
        }
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'business_id' => $this->business->id,
            'business_name' => $this->business->name,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'status_change_date' => now(),
        ];
    }
}