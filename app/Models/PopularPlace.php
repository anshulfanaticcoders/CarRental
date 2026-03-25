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
    ];

    protected $appends = [
        'search_url',
    ];

    public function getSearchUrlAttribute(): ?string
    {
        $unifiedLocationId = (int) ($this->unified_location_id ?? 0);
        if ($unifiedLocationId <= 0) {
            return null;
        }

        $now = Carbon::now();
        $pickupDate = $now->copy()->addDay()->format('Y-m-d');
        $dropoffDate = $now->copy()->addDays(2)->format('Y-m-d');
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
            'start_time' => '09:00',
            'end_time' => '09:00',
            'age' => '35',
        ], fn ($value) => $value !== null && $value !== '');

        return '/s?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }
}
