<?php

namespace App\Notifications\Affiliate;

use App\Models\Affiliate\AffiliateBusiness;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BusinessStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private AffiliateBusiness $business,
        private string $oldStatus,
        private string $newStatus
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $loginUrl = url('/login');
        $statusInfo = $this->getStatusInfo($this->newStatus);

        $mail = (new MailMessage)
            ->subject($statusInfo['subject'])
            ->greeting('Hello ' . ($notifiable->first_name ?? $this->business->name . ' Team') . '!')
            ->line($statusInfo['message'])
            ->line($statusInfo['description']);

        if (in_array($this->newStatus, ['active', 'verified'])) {
            $mail->action('Access Your Dashboard', $loginUrl)
                 ->line('Log in to start generating QR codes and earning commissions.');
        } else {
            $mail->line('If you have any questions about this status change, please don\'t hesitate to contact our support team.');
        }

        $mail->line('**Business Details:**')
            ->line('- **Business Name:** ' . $this->business->name)
            ->line('- **Business Type:** ' . ucfirst(str_replace('_', ' ', $this->business->business_type)))
            ->line('- **Date:** ' . now()->format('M j, Y \a\t g:i A'))
            ->salutation('Best regards,')
            ->salutation('The Vrooem Team');

        return $mail;
    }

    private function getStatusInfo(string $status): array
    {
        switch ($status) {
            case 'verified':
                return [
                    'subject' => 'Your Business Has Been Approved!',
                    'message' => 'Congratulations! Your business has been approved for the Vrooem Partner Program.',
                    'description' => 'You now have full access to your affiliate dashboard. You can create QR codes, track customer scans, and start earning commissions on every booking.'
                ];
            case 'rejected':
                return [
                    'subject' => 'Your Business Application Was Not Approved',
                    'message' => 'Unfortunately, your business application was not approved at this time.',
                    'description' => 'If you believe this was a mistake or would like more information, please contact our support team at support@vrooem.com.'
                ];
            case 'active':
                return [
                    'subject' => 'Your Business Has Been Activated!',
                    'message' => 'Your business is now active in the Vrooem Partner Program.',
                    'description' => 'You can now access all features of your affiliate dashboard, generate QR codes, and start earning commissions.'
                ];
            case 'pending':
                return [
                    'subject' => 'Your Business Status is Pending Review',
                    'message' => 'Your business status has been set to pending.',
                    'description' => 'Your business is currently under review. We will notify you once there are any updates to your status.'
                ];
            case 'inactive':
                return [
                    'subject' => 'Your Business Account is Now Inactive',
                    'message' => 'Your business account has been deactivated.',
                    'description' => 'Your account is currently inactive. This may be due to account maintenance or policy updates. Please contact support for assistance.'
                ];
            case 'suspended':
                return [
                    'subject' => 'Your Business Account Has Been Suspended',
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

    public function toArray(object $notifiable): array
    {
        $statusInfo = $this->getStatusInfo($this->newStatus);

        return [
            'title' => $statusInfo['subject'],
            'role' => 'affiliate',
            'business_id' => $this->business->id,
            'business_name' => $this->business->name,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'message' => $statusInfo['message'],
            'status_change_date' => now()->toISOString(),
        ];
    }
}
