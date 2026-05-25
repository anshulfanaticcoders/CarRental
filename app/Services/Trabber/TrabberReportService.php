<?php

namespace App\Services\Trabber;

use App\Models\Booking;
use Carbon\CarbonInterface;

class TrabberReportService
{
    public function rowsSince(CarbonInterface $since): array
    {
        return Booking::query()
            ->where('created_at', '>=', $since)
            ->orderBy('created_at')
            ->get()
            ->filter(fn (Booking $booking) => ($booking->provider_metadata['partner_source'] ?? null) === 'trabber')
            ->map(fn (Booking $booking) => $this->rowForBooking($booking))
            ->values()
            ->all();
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
        $metadata = $booking->provider_metadata ?? [];
        $rate = isset($metadata['trabber_commission_rate'])
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
