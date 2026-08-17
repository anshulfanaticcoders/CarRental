<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StripeCheckoutPayload extends Model
{
    use HasFactory;

    protected $fillable = [
        'stripe_session_id',
        'payload',
        'payment_status',
        'fulfilment_status',
        'stripe_payment_intent_id',
        'booking_id',
        'fulfilment_attempts',
        'paid_at',
        'fulfilled_at',
        'last_attempt_at',
        'last_error',
    ];

    protected $casts = [
        'payload' => 'array',
        'paid_at' => 'datetime',
        'fulfilled_at' => 'datetime',
        'last_attempt_at' => 'datetime',
    ];
}
