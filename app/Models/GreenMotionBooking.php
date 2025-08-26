<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GreenMotionBooking extends Model
{
    use HasFactory;

    protected $table = 'greenmotion_bookings';

    protected $fillable = [
        'greenmotion_booking_ref',
        'vehicle_id',
        'location_id',
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
        'user_id', // Add user_id to fillable
        'vehicle_location', // Add vehicle_location to fillable
    ];

    /**
     * Get the user that owns the GreenMotion booking.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $casts = [
        'customer_details' => 'array',
        'selected_extras' => 'array',
        'api_response' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
    ];
}
