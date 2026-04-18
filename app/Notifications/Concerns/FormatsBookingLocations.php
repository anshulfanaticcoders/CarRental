<?php

namespace App\Notifications\Concerns;

use App\Models\Booking;

/**
 * Shared helpers for rendering pickup/dropoff blocks in notifications.
 *
 * Every booking notification should surface the dropoff separately when the
 * booking is one-way so customers, vendors, admins, and API drivers all see
 * the same data. Reads from the canonical `provider_metadata` shape produced
 * by LocationDetailsNormalizer.
 */
trait FormatsBookingLocations
{
    protected function pickupDetails(Booking $booking): array
    {
        return (array) ($booking->provider_metadata['pickup_location_details'] ?? []);
    }

    protected function dropoffDetails(Booking $booking): array
    {
        return (array) ($booking->provider_metadata['dropoff_location_details'] ?? []);
    }

    protected function isOneWayBooking(Booking $booking): bool
    {
        $pickup = $this->pickupDetails($booking);
        $dropoff = $this->dropoffDetails($booking);

        if ($pickup && $dropoff) {
            $pLat = $pickup['latitude'] ?? null;
            $pLng = $pickup['longitude'] ?? null;
            $dLat = $dropoff['latitude'] ?? null;
            $dLng = $dropoff['longitude'] ?? null;
            if ($pLat !== null && $pLng !== null && $dLat !== null && $dLng !== null) {
                return abs((float) $pLat - (float) $dLat) > 0.0001
                    || abs((float) $pLng - (float) $dLng) > 0.0001;
            }

            $pName = strtolower(trim((string) ($pickup['name'] ?? '')));
            $dName = strtolower(trim((string) ($dropoff['name'] ?? '')));
            if ($pName !== '' && $dName !== '') {
                return $pName !== $dName;
            }
        }

        $pickupStr = strtolower(trim((string) $booking->pickup_location));
        $returnStr = strtolower(trim((string) $booking->return_location));

        return $pickupStr !== '' && $returnStr !== '' && $pickupStr !== $returnStr;
    }

    /**
     * Format a single location block as human-readable lines suitable for email bodies.
     * Includes address, phone, email, and instructions when present.
     *
     * @return list<string>
     */
    protected function formatLocationBlock(array $details, string $fallback = ''): array
    {
        if (empty($details) && $fallback === '') {
            return [];
        }

        $lines = [];
        $name = trim((string) ($details['name'] ?? $details['location_name'] ?? $fallback));
        if ($name !== '') {
            $lines[] = $name;
        }

        $addressLine = implode(', ', array_filter([
            $details['address_1'] ?? null,
            $details['address_2'] ?? null,
            $details['address_city'] ?? null,
            $details['address_postcode'] ?? null,
            $details['address_country'] ?? null,
        ], fn ($v) => $v !== null && $v !== ''));
        if ($addressLine !== '') {
            $lines[] = $addressLine;
        }

        if (! empty($details['telephone'])) {
            $lines[] = 'Tel: '.$details['telephone'];
        }
        if (! empty($details['email'])) {
            $lines[] = $details['email'];
        }

        return $lines;
    }

    protected function pickupInstructions(Booking $booking): ?string
    {
        $value = $this->pickupDetails($booking)['pickup_instructions']
            ?? ($booking->provider_metadata['pickup_instructions'] ?? null);
        $trimmed = is_string($value) ? trim($value) : null;

        return $trimmed !== '' ? $trimmed : null;
    }

    protected function dropoffInstructions(Booking $booking): ?string
    {
        $value = $this->dropoffDetails($booking)['dropoff_instructions']
            ?? ($booking->provider_metadata['dropoff_instructions'] ?? null);
        $trimmed = is_string($value) ? trim($value) : null;

        return $trimmed !== '' ? $trimmed : null;
    }
}
