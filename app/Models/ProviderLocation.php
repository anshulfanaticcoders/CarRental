<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'provider_location_id',
        'raw_name',
        'raw_address',
        'raw_city',
        'raw_state',
        'raw_country',
        'raw_latitude',
        'raw_longitude',
        'raw_iata',
        'raw_type',
        'name_norm',
        'city_norm',
        'country_norm',
        'type_norm',
        'iata_norm',
        'geohash',
        'last_seen_at',
    ];

    protected $casts = [
        'raw_latitude' => 'float',
        'raw_longitude' => 'float',
        'last_seen_at' => 'datetime',
    ];
}
