<?php

namespace App\Services\Skyscanner;

class CarHirePublicResponseSerializer
{
    public function searchPayload(array $payload): array
    {
        return [
            'quotes' => array_values(array_map(
                fn (array $quote) => $this->quote($quote),
                array_filter($payload['quotes'] ?? [], 'is_array'),
            )),
        ];
    }

    public function redirectPayload(array $quote, mixed $tracking = null): array
    {
        return array_filter([
            'quote' => $this->quote($quote),
            'tracking' => is_array($tracking) ? $tracking : null,
        ], static fn ($value) => $value !== null);
    }

    public function quote(array $quote): array
    {
        return array_filter([
            'quote_id' => $this->nullableString($quote['quote_id'] ?? null),
            'case_id' => $this->nullableString($quote['case_id'] ?? null),
            'created_at' => $this->nullableString($quote['created_at'] ?? null),
            'expires_at' => $this->nullableString($quote['expires_at'] ?? null),
            'free_esim_included' => array_key_exists('free_esim_included', $quote) ? (bool) $quote['free_esim_included'] : null,
            'applied_offers' => $this->appliedOffers($quote['applied_offers'] ?? null),
            'vehicle' => $this->vehicle($quote['vehicle'] ?? []),
            'specs' => $this->specs($quote['specs'] ?? []),
            'pricing' => $this->assoc($quote['pricing'] ?? null),
            'policies' => $this->assoc($quote['policies'] ?? null),
            'pickup_location_details' => $this->location($quote['pickup_location_details'] ?? []),
            'dropoff_location_details' => $this->location($quote['dropoff_location_details'] ?? []),
            'deeplink' => $this->deeplink($quote['deeplink'] ?? []),
        ], static fn ($value) => $value !== null);
    }

    private function vehicle(mixed $vehicle): ?array
    {
        $vehicle = $this->assoc($vehicle);

        if ($vehicle === null) {
            return null;
        }

        return array_filter([
            'display_name' => $this->nullableString($vehicle['display_name'] ?? null),
            'brand' => $this->nullableString($vehicle['brand'] ?? null),
            'model' => $this->nullableString($vehicle['model'] ?? null),
            'category' => $this->nullableString($vehicle['category'] ?? null),
            'image_url' => $this->nullableString($vehicle['image_url'] ?? null),
            'sipp_code' => $this->nullableString($vehicle['sipp_code'] ?? null),
            'transmission' => $this->nullableString($vehicle['transmission'] ?? null),
            'fuel_type' => $this->nullableString($vehicle['fuel_type'] ?? null),
            'air_conditioning' => array_key_exists('air_conditioning', $vehicle) ? $vehicle['air_conditioning'] : null,
            'seats' => $vehicle['seats'] ?? null,
            'doors' => $vehicle['doors'] ?? null,
            'luggage' => $this->luggage($vehicle['luggage'] ?? []),
        ], static fn ($value) => $value !== null);
    }

    private function specs(mixed $specs): ?array
    {
        $specs = $this->assoc($specs);

        if ($specs === null) {
            return null;
        }

        return array_filter([
            'sipp_code' => $this->nullableString($specs['sipp_code'] ?? null),
            'transmission' => $this->nullableString($specs['transmission'] ?? null),
            'fuel' => $this->nullableString($specs['fuel'] ?? null),
            'air_conditioning' => array_key_exists('air_conditioning', $specs) ? $specs['air_conditioning'] : null,
            'seating_capacity' => $specs['seating_capacity'] ?? null,
            'doors' => $specs['doors'] ?? null,
            'luggage_small' => $specs['luggage_small'] ?? null,
            'luggage_medium' => $specs['luggage_medium'] ?? null,
            'luggage_large' => $specs['luggage_large'] ?? null,
        ], static fn ($value) => $value !== null);
    }

    private function location(mixed $location): ?array
    {
        $location = $this->assoc($location);

        if ($location === null) {
            return null;
        }

        return array_filter([
            'name' => $this->nullableString($location['name'] ?? null),
            'address' => $this->nullableString($location['address'] ?? null),
            'city' => $this->nullableString($location['city'] ?? null),
            'state' => $this->nullableString($location['state'] ?? null),
            'country' => $this->nullableString($location['country'] ?? null),
            'country_code' => $this->nullableString($location['country_code'] ?? null),
            'location_type' => $this->nullableString($location['location_type'] ?? null),
            'iata' => $this->nullableString($location['iata'] ?? null),
            'latitude' => $location['latitude'] ?? null,
            'longitude' => $location['longitude'] ?? null,
            'phone' => $this->nullableString($location['phone'] ?? null),
            'pickup_instructions' => $this->nullableString($location['pickup_instructions'] ?? null),
            'dropoff_instructions' => $this->nullableString($location['dropoff_instructions'] ?? null),
        ], static fn ($value) => $value !== null);
    }

    private function deeplink(mixed $deeplink): ?array
    {
        $deeplink = $this->assoc($deeplink);

        if ($deeplink === null) {
            return null;
        }

        return array_filter([
            'landing_page_url' => $this->nullableString($deeplink['landing_page_url'] ?? null),
            'quote_redirect_url' => $this->nullableString($deeplink['quote_redirect_url'] ?? null),
            'tracking_query_parameter' => $this->nullableString($deeplink['tracking_query_parameter'] ?? null),
            'signature_query_parameter' => $this->nullableString($deeplink['signature_query_parameter'] ?? null),
        ], static fn ($value) => $value !== null);
    }

    private function appliedOffers(mixed $offers): ?array
    {
        if (!is_array($offers)) {
            return null;
        }

        $normalized = array_values(array_filter(array_map(function ($offer) {
            if (!is_array($offer)) {
                return null;
            }

            return array_filter([
                'id' => $offer['id'] ?? null,
                'name' => $this->nullableString($offer['name'] ?? null),
                'slug' => $this->nullableString($offer['slug'] ?? null),
                'title' => $this->nullableString($offer['title'] ?? null),
                'effect_type' => $this->nullableString($offer['effect_type'] ?? null),
                'effect_payload' => $this->assoc($offer['effect_payload'] ?? null),
                'discount_amount' => array_key_exists('discount_amount', $offer) ? $offer['discount_amount'] : null,
            ], static fn ($value) => $value !== null);
        }, $offers)));

        return $normalized === [] ? null : $normalized;
    }

    private function luggage(mixed $luggage): ?array
    {
        $luggage = $this->assoc($luggage);

        if ($luggage === null) {
            return null;
        }

        return array_filter([
            'small' => $luggage['small'] ?? null,
            'medium' => $luggage['medium'] ?? null,
            'large' => $luggage['large'] ?? null,
        ], static fn ($value) => $value !== null);
    }

    private function assoc(mixed $value): ?array
    {
        return is_array($value) ? $value : null;
    }

    private function nullableString(mixed $value): ?string
    {
        $string = trim((string) ($value ?? ''));

        return $string === '' ? null : $string;
    }
}
