<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiBookingExtra extends Model
{
    protected $fillable = [
        'api_booking_id',
        'extra_id',
        'extra_name',
        'quantity',
        'unit_price',
        'total_price',
        'currency',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(ApiBooking::class, 'api_booking_id');
    }
}
