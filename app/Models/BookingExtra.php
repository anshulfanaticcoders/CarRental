<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BookingExtra extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'extra_type',
        'extra_name',
        'quantity',
        'price'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer'
    ];

    // Relationship with Booking
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    // Calculate total price for this extra
    public function getTotalPriceAttribute(): float
    {
        return $this->price * $this->quantity;
    }

    // Get formatted price
    public function getFormattedPriceAttribute(): string
    {
        return '€' . number_format($this->price, 2);
    }

    // Get formatted total price
    public function getFormattedTotalPriceAttribute(): string
    {
        return '€' . number_format($this->getTotalPriceAttribute(), 2);
    }
}