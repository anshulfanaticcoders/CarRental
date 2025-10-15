<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OkMobilityBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ok_mobility_booking_ref',
        'vehicle_id',
        'location_id',
        'vehicle_location',
        'start_date',
        'start_time',
        'end_date',
        'end_time',
        'age',
        'rental_code',
        'customer_details',
        'selected_extras',
        'vehicle_total',
        'currency',
        'grand_total',
        'payment_handler_ref',
        'quote_id',
        'payment_type',
        'dropoff_location_id',
        'remarks',
        'booking_status',
        'api_response',
    ];

    protected $casts = [
        'customer_details' => 'array',
        'selected_extras' => 'array',
        'api_response' => 'array',
    ];

    /**
     * Get the user that owns the booking.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
