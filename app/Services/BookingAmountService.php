<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingAmount;
use Illuminate\Support\Facades\Log;

class BookingAmountService
{
    public function createForBooking(Booking $booking, array $amounts, ?string $bookingCurrency, ?string $vendorCurrency = null): ?BookingAmount
    {
        $existing = BookingAmount::where('booking_id', $booking->id)->first();
        if ($existing) {
            return $existing;
        }

        $bookingCurrency = $this->normalizeCurrency($bookingCurrency ?: config('currency.default', 'EUR'));
        $adminCurrency = $this->normalizeCurrency(config('currency.default', $bookingCurrency));
        $vendorCurrency = $vendorCurrency ? $this->normalizeCurrency($vendorCurrency) : null;

        $normalized = $this->normalizeAmounts($amounts);
        $conversionService = app(CurrencyConversionService::class);

        $adminAmounts = $this->convertAmounts(
            $conversionService,
            $normalized,
            $bookingCurrency,
            $adminCurrency,
            'admin',
            $booking->id
        );

        if (!$adminAmounts['success']) {
            return null;
        }

        $vendorAmounts = null;
        if ($vendorCurrency) {
            $vendorAmounts = $this->convertAmounts(
                $conversionService,
                $normalized,
                $bookingCurrency,
                $vendorCurrency,
                'vendor',
                $booking->id
            );

            if (!$vendorAmounts['success']) {
                return null;
            }
        }

        return BookingAmount::create([
            'booking_id' => $booking->id,
            'admin_currency' => $adminCurrency,
            'admin_total_amount' => $adminAmounts['values']['total_amount'],
            'admin_paid_amount' => $adminAmounts['values']['amount_paid'],
            'admin_pending_amount' => $adminAmounts['values']['pending_amount'],
            'admin_extra_amount' => $adminAmounts['values']['extra_amount'],
            'vendor_currency' => $vendorCurrency,
            'vendor_total_amount' => $vendorAmounts['values']['total_amount'] ?? null,
            'vendor_paid_amount' => $vendorAmounts['values']['amount_paid'] ?? null,
            'vendor_pending_amount' => $vendorAmounts['values']['pending_amount'] ?? null,
            'vendor_extra_amount' => $vendorAmounts['values']['extra_amount'] ?? null,
        ]);
    }

    private function normalizeAmounts(array $amounts): array
    {
        $totalAmount = $this->normalizeAmount($amounts['total_amount'] ?? 0);
        $extraAmount = $this->normalizeAmount($amounts['extra_amount'] ?? 0);
        $basePrice = $this->normalizeAmount($amounts['base_price'] ?? 0);
        $extrasTotal = $this->normalizeAmount($amounts['extras_total'] ?? 0);

        if ($extraAmount <= 0) {
            if ($basePrice > 0 || $extrasTotal > 0) {
                $candidate = $basePrice + $extrasTotal;
                $extraAmount = $candidate > ($totalAmount + 0.01) ? $totalAmount : $candidate;
            } else {
                $extraAmount = $totalAmount;
            }
        }

        return [
            'total_amount' => $totalAmount,
            'amount_paid' => $this->normalizeAmount($amounts['amount_paid'] ?? 0),
            'pending_amount' => $this->normalizeAmount($amounts['pending_amount'] ?? 0),
            'extra_amount' => $extraAmount,
        ];
    }

    private function normalizeAmount($value): float
    {
        return round((float) ($value ?? 0), 2);
    }

    private function convertAmounts(
        CurrencyConversionService $conversionService,
        array $amounts,
        string $fromCurrency,
        string $toCurrency,
        string $role,
        int $bookingId
    ): array {
        if ($fromCurrency === $toCurrency) {
            return [
                'success' => true,
                'values' => $amounts,
            ];
        }

        $converted = [];
        foreach ($amounts as $key => $amount) {
            $result = $conversionService->convert((float) $amount, $fromCurrency, $toCurrency);
            if (!($result['success'] ?? false)) {
                Log::warning('BookingAmountService: currency conversion failed', [
                    'booking_id' => $bookingId,
                    'role' => $role,
                    'from_currency' => $fromCurrency,
                    'to_currency' => $toCurrency,
                    'amount_key' => $key,
                    'error' => $result['error'] ?? null,
                ]);

                return [
                    'success' => false,
                    'values' => [],
                ];
            }

            $converted[$key] = round((float) ($result['converted_amount'] ?? $amount), 2);
        }

        return [
            'success' => true,
            'values' => $converted,
        ];
    }

    private function normalizeCurrency(string $currency): string
    {
        $map = [
            '€' => 'EUR',
            '$' => 'USD',
            '£' => 'GBP',
            'د.إ' => 'AED',
            '₹' => 'INR',
            '¥' => 'JPY',
        ];

        $currency = trim($currency);

        return strtoupper($map[$currency] ?? $currency);
    }
}
