<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingAmount extends Model
{
    protected $fillable = [
        'booking_id',
        'booking_currency',
        'booking_total_amount',
        'booking_paid_amount',
        'booking_pending_amount',
        'booking_extra_amount',
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
        'booking_to_admin_rate',
        'booking_to_vendor_rate',
    ];

    protected $casts = [
        'booking_total_amount' => 'decimal:2',
        'booking_paid_amount' => 'decimal:2',
        'booking_pending_amount' => 'decimal:2',
        'booking_extra_amount' => 'decimal:2',
        'admin_total_amount' => 'decimal:2',
        'admin_paid_amount' => 'decimal:2',
        'admin_pending_amount' => 'decimal:2',
        'admin_extra_amount' => 'decimal:2',
        'vendor_total_amount' => 'decimal:2',
        'vendor_paid_amount' => 'decimal:2',
        'vendor_pending_amount' => 'decimal:2',
        'vendor_extra_amount' => 'decimal:2',
        'booking_to_admin_rate' => 'decimal:6',
        'booking_to_vendor_rate' => 'decimal:6',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
