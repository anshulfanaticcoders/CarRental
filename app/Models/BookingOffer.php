<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'offer_id',
        'name',
        'slug',
        'title',
        'effect_type',
        'effect_payload',
        'discount_amount',
        'metadata',
    ];

    protected $casts = [
        'offer_id' => 'integer',
        'effect_payload' => 'array',
        'metadata' => 'array',
        'discount_amount' => 'decimal:2',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
