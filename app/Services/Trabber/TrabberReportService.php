<?php

namespace App\Services\Trabber;

use App\Models\Booking;
use Carbon\CarbonInterface;

class TrabberReportService
{
    public function rowsSince(CarbonInterface $since): array
    {
        // Filter in SQL and chunk: loading a year of ALL bookings into memory
        // to reject non-Trabber rows in PHP was a scheduled-command OOM
        // waiting for real volume.
        $rows = [];

        Booking::query()
            ->where('created_at', '>=', $since)
            ->where('provider_metadata->partner_source', 'trabber')
            ->orderBy('id')
            ->chunkById(500, function ($bookings) use (&$rows) {
                foreach ($bookings as $booking) {
                    $rows[] = $this->rowForBooking($booking);
                }
            });

        return $rows;
    }

    public function csvSince(CarbonInterface $since): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, [
            'booking_reference',
            'clickid',
            'commission',
            'status',
            'total_amount',
            'currency',
            'booking_date',
            'pickup_date',
            'dropoff_date',
        ]);

        foreach ($this->rowsSince($since) as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv === false ? '' : $csv;
    }

    public function rowForBooking(Booking $booking): array
    {
        $metadata = $booking->provider_metadata ?? [];

        return [
            'booking_reference' => $booking->booking_reference ?: $booking->booking_number,
            'clickid' => $metadata['trabber_clickid'] ?? '',
            'commission' => number_format($this->commissionForBooking($booking), 2, '.', ''),
            'status' => $this->mapStatus((string) $booking->booking_status, (string) $booking->payment_status),
            'total_amount' => number_format((float) $booking->total_amount, 2, '.', ''),
            'currency' => $booking->booking_currency ?: ($metadata['booking_currency'] ?? $metadata['currency'] ?? 'EUR'),
            'booking_date' => optional($booking->created_at)->toDateString(),
            'pickup_date' => optional($booking->pickup_date)->toDateString(),
            'dropoff_date' => optional($booking->return_date)->toDateString(),
        ];
    }

    public function commissionForBooking(Booking $booking): float
    {
        // No commission on dead bookings — we used to invoice Trabber full
        // commission on rows the same report marked 'cancelled'.
        if ($this->mapStatus((string) $booking->booking_status, (string) $booking->payment_status) === 'cancelled') {
            return 0.0;
        }

        $metadata = $booking->provider_metadata ?? [];
        $rate = isset($metadata['trabber_commission_rate']) && is_numeric($metadata['trabber_commission_rate'])
            ? (float) $metadata['trabber_commission_rate']
            : (float) config('trabber.commission_rate', 0.05);

        return round((float) $booking->total_amount * $rate, 2);
    }

    public function mapStatus(string $bookingStatus, string $paymentStatus): string
    {
        $bookingStatus = strtolower($bookingStatus);
        $paymentStatus = strtolower($paymentStatus);

        if (in_array($bookingStatus, ['cancelled', 'refunded', 'rejected'], true) || $paymentStatus === 'refunded') {
            return 'cancelled';
        }

        if (in_array($bookingStatus, ['confirmed', 'completed'], true) || in_array($paymentStatus, ['paid', 'succeeded'], true)) {
            return 'confirmed';
        }

        return 'pending';
    }
}
