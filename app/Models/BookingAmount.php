<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingAmount extends Model
{
    protected $fillable = [
        'booking_id',
        'base_currency',
        'booking_currency',
        'vendor_currency',
        'fx_rate',
        'vendor_fx_rate',
        'base_price',
        'extras_total',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'amount_paid',
        'pending_amount',
    ];

    protected $casts = [
        'fx_rate' => 'decimal:6',
        'vendor_fx_rate' => 'decimal:6',
        'base_price' => 'decimal:2',
        'extras_total' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'pending_amount' => 'decimal:2',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
