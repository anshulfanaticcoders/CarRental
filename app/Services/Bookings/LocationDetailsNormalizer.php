<?php

namespace App\Services\Bookings;

/**
 * Canonical shape for booking location details (pickup AND dropoff).
 *
 * Every writer that sets `provider_metadata.pickup_location_details` or
 * `provider_metadata.dropoff_location_details` must first pass raw data
 * through `normalize()`. Every reader can rely on the keys being present
 * (possibly null) and typed consistently.
 *
 * Reason: historically each booking path (Stripe checkout, internal
 * provider API, Skyscanner adapter, internal vehicle snapshot) stored a
 * slightly different subset — which meant admin/vendor/customer pages
 * had to probe half a dozen fallback keys to render a location.
 */
class LocationDetailsNormalizer
{
    /**
     * Normalize raw location details into the canonical shape.
     * Accepts pickup or dropoff — same shape either way.
     */
    public static function normalize(mixed $raw): ?array
    {
        if (empty($raw)) {
            return null;
        }

        $data = is_array($raw) ? $raw : (array) $raw;

        $normalized = [
            'name' => self::stringOrNull($data['name'] ?? $data['location_name'] ?? null),
            'address_1' => self::stringOrNull($data['address_1'] ?? $data['address'] ?? null),
            'address_2' => self::stringOrNull($data['address_2'] ?? null),
            'address_3' => self::stringOrNull($data['address_3'] ?? null),
            'address_city' => self::stringOrNull($data['address_city'] ?? $data['city'] ?? $data['town'] ?? null),
            'address_county' => self::stringOrNull($data['address_county'] ?? $data['county'] ?? $data['state'] ?? null),
            'address_postcode' => self::stringOrNull($data['address_postcode'] ?? $data['postal_code'] ?? $data['postcode'] ?? null),
            'address_country' => self::stringOrNull($data['address_country'] ?? $data['country'] ?? null),
            'telephone' => self::stringOrNull($data['telephone'] ?? $data['phone'] ?? null),
            'email' => self::stringOrNull($data['email'] ?? null),
            'whatsapp' => self::stringOrNull($data['whatsapp'] ?? null),
            'iata' => self::stringOrNull($data['iata'] ?? $data['iata_code'] ?? null),
            'latitude' => self::floatOrNull($data['latitude'] ?? null),
            'longitude' => self::floatOrNull($data['longitude'] ?? null),
            'opening_hours' => self::arrayOrNull($data['opening_hours'] ?? $data['office_opening_hours'] ?? null),
            'out_of_hours' => self::stringOrNull($data['out_of_hours'] ?? null),
            'out_of_hours_dropoff' => self::arrayOrNull($data['out_of_hours_dropoff'] ?? null),
            'out_of_hours_charge' => self::stringOrNull(
                is_array($data['out_of_hours_charge'] ?? null) ? null : ($data['out_of_hours_charge'] ?? null)
            ),
            'daytime_closures_hours' => self::arrayOrNull($data['daytime_closures_hours'] ?? null),
            'pickup_instructions' => self::stringOrNull($data['pickup_instructions'] ?? $data['collection_details'] ?? null),
            'dropoff_instructions' => self::stringOrNull($data['dropoff_instructions'] ?? $data['return_instructions'] ?? null),
        ];

        // Drop entries that are entirely empty so consumers can check empty()
        // cheaply without treating an all-null shape as "present".
        $hasSignal = false;
        foreach ($normalized as $value) {
            if ($value !== null) {
                $hasSignal = true;
                break;
            }
        }

        return $hasSignal ? $normalized : null;
    }

    /**
     * True when two normalized location shapes represent different places
     * (i.e. one-way). Tolerates floating-point coordinate noise.
     */
    public static function isDistinctLocation(?array $pickup, ?array $dropoff): bool
    {
        if (empty($pickup) || empty($dropoff)) {
            return false;
        }

        $pLat = $pickup['latitude'] ?? null;
        $pLng = $pickup['longitude'] ?? null;
        $dLat = $dropoff['latitude'] ?? null;
        $dLng = $dropoff['longitude'] ?? null;

        if ($pLat !== null && $pLng !== null && $dLat !== null && $dLng !== null) {
            return abs($pLat - $dLat) > 0.0001 || abs($pLng - $dLng) > 0.0001;
        }

        $pName = strtolower(trim((string) ($pickup['name'] ?? '')));
        $dName = strtolower(trim((string) ($dropoff['name'] ?? '')));
        if ($pName !== '' && $dName !== '' && $pName !== $dName) {
            return true;
        }

        $pAddr = strtolower(trim((string) ($pickup['address_1'] ?? '')));
        $dAddr = strtolower(trim((string) ($dropoff['address_1'] ?? '')));

        return $pAddr !== '' && $dAddr !== '' && $pAddr !== $dAddr;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value)) {
            $trimmed = trim($value);

            return $trimmed === '' ? null : $trimmed;
        }
        if (is_numeric($value) || is_bool($value)) {
            return (string) $value;
        }

        return null;
    }

    private static function floatOrNull(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }
        $float = (float) $value;

        return abs($float) < 0.000001 ? null : $float;
    }

    private static function arrayOrNull(mixed $value): ?array
    {
        if (! is_array($value) || empty($value)) {
            return null;
        }

        return $value;
    }
}
