<?php

namespace App\Notifications\Concerns;

trait FormatsBookingAmounts
{
    protected function formatCurrencyAmount(?float $amount, ?string $currency): string
    {
        $value = $amount ?? 0.0;
        $code = $currency ?: 'USD';
        $symbol = $this->getCurrencySymbol($code);
        $separator = '';

        if (preg_match('/^[A-Za-z]{2,}$/', $symbol)) {
            $separator = ' ';
        }

        return $symbol . $separator . number_format($value, 2);
    }

    protected function getCurrencySymbol(string $currency): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'AUD' => 'A$',
            'CAD' => 'C$',
            'CHF' => 'Fr',
            'HKD' => 'HK$',
            'SGD' => 'S$',
            'SEK' => 'kr',
            'KRW' => '₩',
            'NOK' => 'kr',
            'NZD' => 'NZ$',
            'INR' => '₹',
            'MXN' => 'Mex$',
            'ZAR' => 'R',
            'AED' => 'AED',
        ];

        $currency = strtoupper($currency);

        return $symbols[$currency] ?? $currency;
    }

    protected function getAdminAmounts($booking): array
    {
        $amounts = $booking->amounts ?? null;
        if ($amounts && $amounts->admin_currency) {
            return [
                'currency' => $amounts->admin_currency,
                'total' => (float) $amounts->admin_total_amount,
                'paid' => (float) $amounts->admin_paid_amount,
                'pending' => (float) $amounts->admin_pending_amount,
            ];
        }

        return [
            'currency' => $booking->booking_currency ?? 'USD',
            'total' => (float) $booking->total_amount,
            'paid' => (float) $booking->amount_paid,
            'pending' => (float) $booking->pending_amount,
        ];
    }

    protected function getVendorAmounts($booking): array
    {
        $amounts = $booking->amounts ?? null;
        if ($amounts && $amounts->vendor_currency) {
            return [
                'currency' => $amounts->vendor_currency,
                'total' => (float) $amounts->vendor_total_amount,
                'paid' => (float) $amounts->vendor_paid_amount,
                'pending' => (float) $amounts->vendor_pending_amount,
            ];
        }

        return [
            'currency' => $booking->booking_currency ?? 'USD',
            'total' => (float) $booking->total_amount,
            'paid' => (float) $booking->amount_paid,
            'pending' => (float) $booking->pending_amount,
        ];
    }

    protected function getCustomerAmounts($booking): array
    {
        return [
            'currency' => $booking->booking_currency ?? ($booking->amounts->admin_currency ?? 'USD'),
            'total' => (float) $booking->total_amount,
            'paid' => (float) $booking->amount_paid,
            'pending' => (float) $booking->pending_amount,
        ];
    }
}
