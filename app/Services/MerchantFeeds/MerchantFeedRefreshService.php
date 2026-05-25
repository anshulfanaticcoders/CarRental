<?php

namespace App\Services\MerchantFeeds;

use App\Models\MerchantFeedItem;
use App\Models\PopularPlace;
use App\Models\Vehicle;
use App\Services\Vehicles\InternalVehicleAvailabilityService;
use App\Services\VrooemGatewayService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MerchantFeedRefreshService
{
    public function __construct(
        private readonly InternalVehicleAvailabilityService $availabilityService,
        private readonly MerchantFeedItemBuilder $itemBuilder,
        private readonly VrooemGatewayService $gatewayService,
        private readonly GoogleMerchantXmlWriter $xmlWriter,
        private readonly MerchantFeedFileLocator $fileLocator,
    ) {}

    public function refresh(string $feedName = 'awin'): array
    {
        $now = CarbonImmutable::now();
        $window = $this->buildWindow($feedName, $now);
        $seen = [
            'internal' => [],
            'external' => [],
        ];
        $stats = [
            'feed_name' => $feedName,
            'internal_items' => 0,
            'external_items' => 0,
            'external_locations' => 0,
            'external_successful_searches' => 0,
            'skipped_items' => 0,
        ];

        if ((bool) config("merchant_feeds.{$feedName}.include_internal", true)) {
            $seen['internal'] = $this->refreshInternal($feedName, $window, $now, $stats);
            $this->markMissingItemsOutOfStock($feedName, 'internal', $seen['internal'], $now);
        }

        if ((bool) config("merchant_feeds.{$feedName}.include_external", true) && (bool) config('vrooem.enabled', false)) {
            $seen['external'] = $this->refreshExternal($feedName, $window, $now, $stats);
            if ($stats['external_successful_searches'] > 0) {
                $this->markMissingItemsOutOfStock($feedName, 'external', $seen['external'], $now);
            }
        }

        $this->purgeExpired($feedName, $now);

        $items = $this->itemsForXml($feedName, $now);
        $this->xmlWriter->write($items, $this->fileLocator->path($feedName), $feedName);

        $stats['xml_items'] = $items->count();
        $stats['output_path'] = $this->fileLocator->path($feedName);

        return $stats;
    }

    public function buildWindow(string $feedName = 'awin', ?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();
        $pickupOffsetDays = max(1, (int) config("merchant_feeds.{$feedName}.pickup_offset_days", 1));
        $rentalDays = max(1, (int) config("merchant_feeds.{$feedName}.rental_days", 1));
        $pickupDate = $now->addDays($pickupOffsetDays);
        $dropoffDate = $pickupDate->addDays($rentalDays);

        return [
            'pickup_date' => $pickupDate->toDateString(),
            'dropoff_date' => $dropoffDate->toDateString(),
            'pickup_time' => $this->timeConfig($feedName, 'pickup_time', '09:00'),
            'dropoff_time' => $this->timeConfig($feedName, 'dropoff_time', '09:00'),
            'rental_days' => $rentalDays,
            'currency' => strtoupper((string) config("merchant_feeds.{$feedName}.currency", 'EUR')),
            'driver_age' => (int) config("merchant_feeds.{$feedName}.driver_age", 35),
        ];
    }

    private function refreshInternal(string $feedName, array $window, CarbonImmutable $now, array &$stats): array
    {
        $vehicles = Vehicle::query()
            ->with(['images', 'category', 'vendorLocation', 'vendorProfile', 'vendorProfileData', 'benefits', 'vendorPlans', 'addons', 'operatingHours'])
            ->whereIn('status', Vehicle::searchableStatuses())
            ->whereNotNull('price_per_day')
            ->get();

        $availableIds = $vehicles->isEmpty()
            ? collect()
            : $this->availabilityService
                ->apply(Vehicle::query()->whereIn('id', $vehicles->pluck('id')), [
                    'pickup_date' => $window['pickup_date'],
                    'pickup_time' => $window['pickup_time'],
                    'dropoff_date' => $window['dropoff_date'],
                    'dropoff_time' => $window['dropoff_time'],
                ])
                ->pluck('id')
                ->flip();

        $seenKeys = [];
        foreach ($vehicles as $vehicle) {
            $item = $this->itemBuilder->fromInternalVehicle(
                $vehicle,
                $availableIds->has($vehicle->id),
                $window,
                $feedName
            );

            if ($item === null) {
                $stats['skipped_items']++;

                continue;
            }

            $this->upsertItem($item, $now, $item['availability'] === 'in_stock');
            $seenKeys[] = $item['feed_key'];
            $stats['internal_items']++;
        }

        return $seenKeys;
    }

    private function refreshExternal(string $feedName, array $window, CarbonImmutable $now, array &$stats): array
    {
        $locations = $this->externalLocations($feedName);
        $stats['external_locations'] = $locations->count();
        $seenKeys = [];

        foreach ($locations as $location) {
            $params = [
                'unified_location_id' => (int) $location->unified_location_id,
                'pickup_date' => $window['pickup_date'],
                'dropoff_date' => $window['dropoff_date'],
                'pickup_time' => $window['pickup_time'],
                'dropoff_time' => $window['dropoff_time'],
                'currency' => $window['currency'],
                'driver_age' => $window['driver_age'],
            ];

            try {
                $result = $this->gatewayService->searchVehicles($params);
            } catch (\Throwable $exception) {
                Log::warning('Merchant feed external search failed', [
                    'feed_name' => $feedName,
                    'unified_location_id' => $location->unified_location_id,
                    'error' => $exception->getMessage(),
                ]);

                continue;
            }

            if (! empty($result['search_id'])) {
                $stats['external_successful_searches']++;
            }

            foreach (($result['vehicles'] ?? []) as $gatewayVehicle) {
                try {
                    $item = $this->itemBuilder->fromGatewayVehicle(
                        $gatewayVehicle,
                        $location->toArray(),
                        $window,
                        $feedName
                    );
                } catch (\Throwable $exception) {
                    Log::warning('Merchant feed external vehicle skipped', [
                        'feed_name' => $feedName,
                        'unified_location_id' => $location->unified_location_id,
                        'vehicle_id' => $gatewayVehicle['id'] ?? null,
                        'error' => $exception->getMessage(),
                    ]);
                    $stats['skipped_items']++;

                    continue;
                }

                if ($item === null) {
                    $stats['skipped_items']++;

                    continue;
                }

                $this->upsertItem($item, $now, $item['availability'] === 'in_stock');
                $seenKeys[] = $item['feed_key'];
                $stats['external_items']++;
            }
        }

        return array_values(array_unique($seenKeys));
    }

    private function externalLocations(string $feedName): Collection
    {
        $limit = max(1, (int) config("merchant_feeds.{$feedName}.external_location_limit", 50));

        return PopularPlace::query()
            ->whereNotNull('unified_location_id')
            ->where('unified_location_id', '>', 0)
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'place_name', 'city', 'state', 'country', 'latitude', 'longitude', 'unified_location_id']);
    }

    private function upsertItem(array $item, CarbonImmutable $now, bool $inStock): void
    {
        $item['last_seen_at'] = $now;
        $item['expires_at'] = $inStock
            ? $now->addHours($this->expiresAfterHours($item['feed_name']))
            : $now->addHours($this->staleAfterHours($item['feed_name']));

        MerchantFeedItem::query()->updateOrCreate(
            [
                'feed_name' => $item['feed_name'],
                'feed_key' => $item['feed_key'],
            ],
            $item
        );
    }

    private function markMissingItemsOutOfStock(string $feedName, string $source, array $seenKeys, CarbonImmutable $now): void
    {
        $query = MerchantFeedItem::query()
            ->where('feed_name', $feedName)
            ->where('source', $source);

        if (! empty($seenKeys)) {
            $query->whereNotIn('feed_key', $seenKeys);
        }

        $query->update([
            'availability' => 'out_of_stock',
            'expires_at' => $now->addHours($this->staleAfterHours($feedName)),
            'updated_at' => $now,
        ]);
    }

    private function purgeExpired(string $feedName, CarbonImmutable $now): void
    {
        MerchantFeedItem::query()
            ->where('feed_name', $feedName)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now)
            ->delete();
    }

    private function itemsForXml(string $feedName, CarbonImmutable $now): Collection
    {
        return MerchantFeedItem::query()
            ->where('feed_name', $feedName)
            ->where('availability', 'in_stock')
            ->where(function ($query) use ($now) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', $now);
            })
            ->orderBy('source')
            ->orderBy('feed_key')
            ->get()
            ->filter(fn (MerchantFeedItem $item): bool => mb_strlen($item->feed_key) <= 50)
            ->values();
    }

    private function timeConfig(string $feedName, string $key, string $default): string
    {
        $value = (string) config("merchant_feeds.{$feedName}.{$key}", $default);

        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value) ? $value : $default;
    }

    private function staleAfterHours(string $feedName): int
    {
        return max(1, (int) config("merchant_feeds.{$feedName}.stale_after_hours", 6));
    }

    private function expiresAfterHours(string $feedName): int
    {
        return max($this->staleAfterHours($feedName), (int) config("merchant_feeds.{$feedName}.expires_after_hours", 24));
    }
}
