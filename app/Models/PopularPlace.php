<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PopularPlace extends Model
{
    use HasFactory;

    protected $table = 'popular_places';

    protected $fillable = [
        'place_name',
        'city',
        'state',
        'country',
        'latitude',
        'longitude',
        'unified_location_id',
        'image',
        'provider_count',
        'last_verified_at',
        'is_active',
    ];

    protected $casts = [
        'last_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'provider_count' => 'integer',
    ];

    protected $appends = [
        'search_url',
    ];

    /**
     * Count the bookable (non-internal) providers on a resolved gateway
     * location record. Zero means the destination would show no cars.
     */
    public static function countBookableProviders(?array $location): int
    {
        return collect($location['providers'] ?? [])
            ->filter(fn ($provider) => is_array($provider)
                && strtolower(trim((string) ($provider['provider'] ?? ''))) !== 'internal'
                && strtolower(trim((string) ($provider['provider'] ?? ''))) !== '')
            ->count();
    }

    public function getSearchUrlAttribute(): ?string
    {
        $unifiedLocationId = (int) ($this->unified_location_id ?? 0);
        if ($unifiedLocationId <= 0) {
            return null;
        }

        // Suppliers rarely have inventory for near-term pickups, so default the
        // pickup a couple of weeks out (config-driven) — a same/next-day default
        // lands most destination clicks on an empty results page.
        $leadDays = (int) config('destinations.default_lead_days', 14);
        $rentalDays = max(1, (int) config('destinations.default_rental_days', 3));
        $pickupTime = (string) config('destinations.default_pickup_time', '10:00');

        $now = Carbon::now();
        $pickupDate = $now->copy()->addDays($leadDays)->format('Y-m-d');
        $dropoffDate = $now->copy()->addDays($leadDays + $rentalDays)->format('Y-m-d');
        $where = (string) ($this->place_name ?? '');

        $params = array_filter([
            'where' => $where,
            'city' => $this->city,
            'country' => $this->country,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'provider' => 'mixed',
            'unified_location_id' => (string) $unifiedLocationId,
            'dropoff_unified_location_id' => (string) $unifiedLocationId,
            'dropoff_where' => $where,
            'date_from' => $pickupDate,
            'date_to' => $dropoffDate,
            'start_time' => $pickupTime,
            'end_time' => $pickupTime,
            'age' => '35',
        ], fn ($value) => $value !== null && $value !== '');

        return '/s?'.http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }
}
