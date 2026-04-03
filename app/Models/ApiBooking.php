<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiBooking extends Model
{
    protected $fillable = [
        'booking_number',
        'api_consumer_id',
        'vehicle_id',
        'vehicle_name',
        'vehicle_image',
        'driver_first_name',
        'driver_last_name',
        'driver_email',
        'driver_phone',
        'driver_age',
        'driver_license_number',
        'driver_license_country',
        'pickup_date',
        'pickup_time',
        'return_date',
        'return_time',
        'pickup_location',
        'return_location',
        'total_days',
        'daily_rate',
        'base_price',
        'extras_total',
        'total_amount',
        'currency',
        'status',
        'is_test',
        'cancellation_reason',
        'cancelled_at',
        'flight_number',
        'special_requests',
        'insurance_id',
    ];

    protected $casts = [
        'pickup_date' => 'datetime',
        'return_date' => 'datetime',
        'cancelled_at' => 'datetime',
        'daily_rate' => 'decimal:2',
        'base_price' => 'decimal:2',
        'extras_total' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'driver_age' => 'integer',
        'total_days' => 'integer',
        'is_test' => 'boolean',
    ];

    public function consumer(): BelongsTo
    {
        return $this->belongsTo(ApiConsumer::class, 'api_consumer_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function extras(): HasMany
    {
        return $this->hasMany(ApiBookingExtra::class);
    }

    public static function generateBookingNumber(): string
    {
        $prefix = 'API';
        $date = date('Ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return $prefix . '-' . $date . '-' . $random;
    }

    public function getDriverFullNameAttribute(): string
    {
        return $this->driver_first_name . ' ' . $this->driver_last_name;
    }
}
