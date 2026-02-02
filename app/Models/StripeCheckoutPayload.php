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
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
