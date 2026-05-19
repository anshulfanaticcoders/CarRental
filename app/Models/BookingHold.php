<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingHold extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'user_id',
        'search_session_id',
        'stripe_session_id',
        'customer_email',
        'pickup_date',
        'pickup_time',
        'dropoff_date',
        'dropoff_time',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'pickup_date' => 'date',
        'dropoff_date' => 'date',
        'expires_at' => 'datetime',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', 'active')
            ->where('expires_at', '>', now());
    }
}
