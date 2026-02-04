<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnifiedLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_key',
        'name',
        'aliases',
        'city',
        'country',
        'latitude',
        'longitude',
        'location_type',
        'iata',
        'confidence',
        'is_manual',
        'is_active',
    ];

    protected $casts = [
        'aliases' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'is_manual' => 'boolean',
        'is_active' => 'boolean',
    ];
}
