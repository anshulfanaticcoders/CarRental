<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StripeCheckoutPayload extends Model
{
    use HasFactory;

    /**
     * Statuses that must never be reset to pending — a webhook replay or
     * queued retry that downgrades one of these can resurrect a booking
     * that was deliberately deleted, or re-fulfil an already-handled session.
     */
    public const TERMINAL_STATUSES = ['fulfilled', 'manual_review', 'expired'];

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
