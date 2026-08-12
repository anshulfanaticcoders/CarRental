<?php

namespace App\Notifications\Concerns;

use App\Models\Customer;
use Illuminate\Notifications\Notification as NotificationInstance;
use Illuminate\Support\Facades\Notification;

trait DeliversToCustomer
{
    /**
     * Reach the customer on their account when they have one, otherwise fall back
     * to a plain mail route. Guest placeholder addresses are never deliverable.
     */
    protected function deliverToCustomer(?Customer $customer, NotificationInstance $notification): void
    {
        if (! $customer) {
            return;
        }

        if ($customer->user) {
            $customer->user->notify($notification);
        } elseif (! empty($customer->email) && ! str_starts_with($customer->email, 'guest_')) {
            Notification::route('mail', $customer->email)->notify($notification);
        }
    }
}
