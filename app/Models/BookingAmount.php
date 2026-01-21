<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingAmount extends Model
{
    protected $fillable = [
        'booking_id',
        'admin_currency',
        'admin_total_amount',
        'admin_paid_amount',
        'admin_pending_amount',
        'admin_extra_amount',
        'vendor_currency',
        'vendor_total_amount',
        'vendor_paid_amount',
        'vendor_pending_amount',
        'vendor_extra_amount',
    ];

    protected $casts = [
        'admin_total_amount' => 'decimal:2',
        'admin_paid_amount' => 'decimal:2',
        'admin_pending_amount' => 'decimal:2',
        'admin_extra_amount' => 'decimal:2',
        'vendor_total_amount' => 'decimal:2',
        'vendor_paid_amount' => 'decimal:2',
        'vendor_pending_amount' => 'decimal:2',
        'vendor_extra_amount' => 'decimal:2',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
