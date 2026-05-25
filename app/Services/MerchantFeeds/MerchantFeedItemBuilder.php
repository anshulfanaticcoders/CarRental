<?php

namespace App\Services\MerchantFeeds;

use App\Models\Vehicle;
use App\Services\Pricing\PayablePercentageService;
use App\Services\Search\InternalSearchVehicleFactory;
use App\Services\Vehicles\GatewayVehicleTransformer;

class MerchantFeedItemBuilder
{
    private ?float $priceMarkupRate = null;

    public function __construct(
        private readonly InternalSearchVehicleFactory $internalVehicleFactory,
        private readonly GatewayVehicleTransformer $gatewayVehicleTransformer,
        private readonly PayablePercentageService $payablePercentageService,
    ) {}

    public function fromInternalVehicle(Vehicle $vehicle, bool $available, array $window, string $feedName = 'awin'): ?array
    {
        $data = $vehicle->toArray();
        $data['category_name'] = $vehicle->category?->name;

        $normalized = $this->internalVehicleFactory->make($data, $this->rentalDays($window), [
            'pickup_location_id' => $vehicle->vendor_location_id ? (string) $vehicle->vendor_location_id : (string) $vehicle->id,
            'dropoff_location_id' => $vehicle->vendor_location_id ? (string) $vehicle->vendor_location_id : (string) $vehicle->id,
        ]);

        $price = $this->positivePrice(data_get($normalized, 'pricing.price_per_day'));
        $image = $this->validUrl(data_get($normalized, 'image'));

        if ($price === null || $image === null) {
            return null;
        }

        $feedKey = 'internal-'.$vehicle->id;
        $currency = $this->currency(data_get($normalized, 'pricing.currency'));
        $grossPrice = $this->grossPrice($price);
        $locationName = $this->cleanText($vehicle->vendorLocation?->name ?: $vehicle->location ?: $vehicle->city ?: $vehicle->country, 191);
        $title = $this->title([
            $normalized['display_name'] ?? trim($vehicle->brand.' '.$vehicle->model),
            'rental',
            $vehicle->city ? 'in '.$vehicle->city : null,
        ]);

        return [
            'feed_name' => $feedName,
            'feed_key' => $feedKey,
            'source' => 'internal',
            'provider' => 'internal',
            'provider_vehicle_id' => (string) $vehicle->id,
            'title' => $title,
            'description' => $this->description([
                "Rent {$title} with Vrooem.",
                $normalized['category'] ?? null,
                data_get($normalized, 'specs.transmission'),
                data_get($normalized, 'specs.fuel'),
                data_get($normalized, 'specs.seating_capacity') ? data_get($normalized, 'specs.seating_capacity').' seats' : null,
                "From {$this->money($grossPrice, $currency)} per day.",
            ]),
            'link' => $this->internalLink($vehicle, $window, $feedName, $feedKey, $currency),
            'image_link' => $image,
            'price' => $grossPrice,
            'currency' => $currency,
            'availability' => $available ? 'in_stock' : 'out_of_stock',
            'brand' => $this->nullableText($vehicle->brand, 120),
            'product_type' => $this->productType($feedName, $vehicle->category?->name),
            'condition' => $this->condition($feedName),
            'location_name' => $locationName ?: null,
            'city' => $this->nullableText($vehicle->city, 120),
            'country' => $this->nullableText($vehicle->country, 120),
            'raw_attributes' => [
                'vehicle_id' => $vehicle->id,
                'category' => $vehicle->category?->name,
                'net_price' => $price,
                'payment_percentage' => $this->paymentPercentage(),
                'price_markup_rate' => $this->priceMarkupRate(),
                'window' => $this->windowPayload($window),
            ],
        ];
    }

    public function fromGatewayVehicle(array $gatewayVehicle, array $location, array $window, string $feedName = 'awin'): ?array
    {
        $vehicle = $this->gatewayVehicleTransformer->transform($gatewayVehicle, $this->rentalDays($window));

        $price = $this->positivePrice(
            data_get($vehicle, 'pricing.price_per_day')
            ?? data_get($vehicle, 'price_per_day')
            ?? data_get($vehicle, 'daily_rate')
        );
        $image = $this->validUrl(data_get($vehicle, 'image') ?? data_get($gatewayVehicle, 'image_url'));

        if ($price === null || $image === null) {
            return null;
        }

        $provider = $this->provider($vehicle, $gatewayVehicle);
        $providerVehicleId = $this->providerVehicleId($vehicle, $gatewayVehicle);
        $feedKey = $this->externalFeedKey($provider, $providerVehicleId, $vehicle, $location);
        $currency = $this->currency(data_get($vehicle, 'currency') ?? data_get($vehicle, 'pricing.currency') ?? $window['currency'] ?? null);
        $grossPrice = $this->grossPrice($price);
        $locationName = $this->cleanText($location['place_name'] ?? $location['name'] ?? 'Selected location', 191);
        $displayName = $this->cleanText(
            data_get($vehicle, 'display_name')
            ?: trim((string) data_get($vehicle, 'brand').' '.(string) data_get($vehicle, 'model'))
            ?: data_get($gatewayVehicle, 'name')
            ?: 'Vehicle rental',
            180
        );
        $title = $this->title([
            $displayName,
            'rental',
            $locationName ? 'in '.$locationName : null,
        ]);

        return [
            'feed_name' => $feedName,
            'feed_key' => $feedKey,
            'source' => 'external',
            'provider' => $provider,
            'provider_vehicle_id' => $providerVehicleId,
            'title' => $title,
            'description' => $this->description([
                "Compare and book {$title} with Vrooem.",
                data_get($vehicle, 'category'),
                data_get($vehicle, 'transmission'),
                data_get($vehicle, 'fuel'),
                data_get($vehicle, 'seating_capacity') ? data_get($vehicle, 'seating_capacity').' seats' : null,
                "From {$this->money($grossPrice, $currency)} per day.",
            ]),
            'link' => $this->externalLink($vehicle, $location, $window, $feedName, $feedKey, $currency),
            'image_link' => $image,
            'price' => $grossPrice,
            'currency' => $currency,
            'availability' => $this->gatewayAvailability($gatewayVehicle, $vehicle),
            'brand' => $this->nullableText(data_get($vehicle, 'brand'), 120),
            'product_type' => $this->productType($feedName, data_get($vehicle, 'category')),
            'condition' => $this->condition($feedName),
            'location_name' => $locationName ?: null,
            'city' => $this->nullableText($location['city'] ?? null, 120),
            'country' => $this->nullableText($location['country'] ?? null, 120),
            'raw_attributes' => [
                'provider' => $provider,
                'provider_vehicle_id' => $providerVehicleId,
                'gateway_vehicle_id' => data_get($vehicle, 'gateway_vehicle_id') ?? data_get($vehicle, 'id'),
                'provider_product_id' => data_get($vehicle, 'provider_product_id'),
                'provider_rate_id' => data_get($vehicle, 'provider_rate_id'),
                'net_price' => $price,
                'payment_percentage' => $this->paymentPercentage(),
                'price_markup_rate' => $this->priceMarkupRate(),
                'unified_location_id' => $location['unified_location_id'] ?? null,
                'window' => $this->windowPayload($window),
            ],
        ];
    }

    private function internalLink(Vehicle $vehicle, array $window, string $feedName, string $feedKey, string $currency): string
    {
        $url = route('vehicle.show', [
            'locale' => $this->locale($feedName),
            'id' => $vehicle->id,
        ]);

        return $this->appendQuery($url, array_merge($this->windowQuery($window, $currency), $this->utm($feedName, 'internal', $feedKey)));
    }

    private function externalLink(array $vehicle, array $location, array $window, string $feedName, string $feedKey, string $currency): string
    {
        $locationName = $location['place_name'] ?? $location['name'] ?? 'Selected location';
        $provider = $this->provider($vehicle, []);

        $params = array_merge([
            'where' => $locationName,
            'city' => $location['city'] ?? null,
            'country' => $location['country'] ?? null,
            'latitude' => $location['latitude'] ?? null,
            'longitude' => $location['longitude'] ?? null,
            'provider' => $provider,
            'provider_pickup_id' => data_get($vehicle, 'provider_pickup_id'),
            'unified_location_id' => $location['unified_location_id'] ?? null,
            'dropoff_unified_location_id' => $location['unified_location_id'] ?? null,
            'dropoff_where' => $locationName,
            'feed_item' => $feedKey,
            'feed_provider' => $provider,
            'feed_gateway_vehicle_id' => data_get($vehicle, 'gateway_vehicle_id') ?? data_get($vehicle, 'id'),
            'feed_provider_vehicle_id' => data_get($vehicle, 'provider_vehicle_id'),
            'feed_product_id' => data_get($vehicle, 'provider_product_id'),
            'feed_rate_id' => data_get($vehicle, 'provider_rate_id'),
        ], $this->windowQuery($window, $currency), $this->utm($feedName, 'external', $feedKey));

        return $this->appendQuery(route('search', ['locale' => $this->locale($feedName)]), $params);
    }

    private function appendQuery(string $url, array $params): string
    {
        $params = array_filter($params, static fn ($value): bool => $value !== null && $value !== '');
        if (empty($params)) {
            return $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?').http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    private function windowQuery(array $window, string $currency): array
    {
        return [
            'date_from' => $window['pickup_date'] ?? null,
            'date_to' => $window['dropoff_date'] ?? null,
            'start_time' => $window['pickup_time'] ?? null,
            'end_time' => $window['dropoff_time'] ?? null,
            'age' => $window['driver_age'] ?? null,
            'currency' => $currency,
        ];
    }

    private function utm(string $feedName, string $content, string $feedKey): array
    {
        $utm = config("merchant_feeds.{$feedName}.utm", []);

        return array_merge($utm, [
            'utm_content' => $content,
            'utm_term' => $feedKey,
        ]);
    }

    private function provider(array $vehicle, array $gatewayVehicle): string
    {
        return strtolower($this->cleanText(
            data_get($vehicle, 'source')
            ?? data_get($vehicle, 'provider_code')
            ?? data_get($gatewayVehicle, 'supplier_id')
            ?? 'external',
            80
        ));
    }

    private function providerVehicleId(array $vehicle, array $gatewayVehicle): string
    {
        return $this->cleanText(
            data_get($vehicle, 'provider_vehicle_id')
            ?? data_get($vehicle, 'provider_product_id')
            ?? data_get($vehicle, 'provider_rate_id')
            ?? data_get($vehicle, 'gateway_vehicle_id')
            ?? data_get($vehicle, 'id')
            ?? data_get($gatewayVehicle, 'supplier_vehicle_id')
            ?? data_get($gatewayVehicle, 'id')
            ?? 'unknown',
            191
        );
    }

    private function externalFeedKey(string $provider, string $providerVehicleId, array $vehicle, array $location): string
    {
        $material = implode('|', [
            $provider,
            $providerVehicleId,
            data_get($vehicle, 'provider_product_id'),
            data_get($vehicle, 'provider_rate_id'),
            $location['unified_location_id'] ?? '',
        ]);

        return 'ext-'.substr(hash('sha256', $material), 0, 46);
    }

    private function gatewayAvailability(array $gatewayVehicle, array $vehicle): string
    {
        $available = data_get($gatewayVehicle, 'is_available', true);
        $status = strtolower((string) (data_get($vehicle, 'availability_status') ?? data_get($gatewayVehicle, 'availability_status') ?? ''));

        if ($available === false || in_array($status, ['unavailable', 'sold_out', 'not_available'], true)) {
            return 'out_of_stock';
        }

        return 'in_stock';
    }

    private function title(array $parts): string
    {
        return $this->cleanText(implode(' ', array_filter($parts, static fn ($part): bool => filled($part))), 150);
    }

    private function description(array $parts): string
    {
        return $this->cleanText(implode(' ', array_filter($parts, static fn ($part): bool => filled($part))), 5000);
    }

    private function productType(string $feedName, mixed $category): string
    {
        $base = $this->cleanText(config("merchant_feeds.{$feedName}.product_type", 'Car Rental'), 120);
        $category = $this->nullableText($category, 80);

        return $category ? "{$base} > {$category}" : $base;
    }

    private function condition(string $feedName): string
    {
        $condition = strtolower($this->cleanText(config("merchant_feeds.{$feedName}.condition", 'used'), 20));

        return in_array($condition, ['new', 'refurbished', 'used'], true) ? $condition : 'used';
    }

    private function locale(string $feedName): string
    {
        return $this->cleanText(config("merchant_feeds.{$feedName}.locale", 'en'), 10) ?: 'en';
    }

    private function currency(mixed $value): string
    {
        $currency = strtoupper($this->cleanText($value ?: 'EUR', 3));

        return preg_match('/^[A-Z]{3}$/', $currency) ? $currency : 'EUR';
    }

    private function positivePrice(mixed $value): ?float
    {
        $price = round((float) $value, 2);

        return $price > 0 ? $price : null;
    }

    private function grossPrice(float $netPrice): float
    {
        return round($netPrice * (1 + $this->priceMarkupRate()), 2);
    }

    private function priceMarkupRate(): float
    {
        return $this->priceMarkupRate ??= $this->payablePercentageService->rate();
    }

    private function paymentPercentage(): float
    {
        return round($this->priceMarkupRate() * 100, 2);
    }

    private function validUrl(mixed $value): ?string
    {
        $url = $this->cleanText($value, 2000);
        $parts = parse_url($url);

        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        return $url;
    }

    private function nullableText(mixed $value, int $limit): ?string
    {
        $text = $this->cleanText($value, $limit);

        return $text === '' ? null : $text;
    }

    private function cleanText(mixed $value, int $limit): string
    {
        $text = html_entity_decode(strip_tags((string) ($value ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $text);
        if ($converted !== false) {
            $text = $converted;
        }

        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = trim($text);

        return function_exists('mb_substr') ? mb_substr($text, 0, $limit) : substr($text, 0, $limit);
    }

    private function rentalDays(array $window): int
    {
        return max(1, (int) ($window['rental_days'] ?? 1));
    }

    private function money(float $price, string $currency): string
    {
        return number_format($price, 2, '.', '').' '.$currency;
    }

    private function windowPayload(array $window): array
    {
        return [
            'pickup_date' => $window['pickup_date'] ?? null,
            'dropoff_date' => $window['dropoff_date'] ?? null,
            'pickup_time' => $window['pickup_time'] ?? null,
            'dropoff_time' => $window['dropoff_time'] ?? null,
            'driver_age' => $window['driver_age'] ?? null,
            'currency' => $window['currency'] ?? null,
        ];
    }
}
