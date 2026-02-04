<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnifiedLocationMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'unified_location_id',
        'provider',
        'provider_location_id',
        'status',
        'match_reason',
        'last_matched_at',
    ];

    protected $casts = [
        'last_matched_at' => 'datetime',
    ];
}
