<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OkMobilityBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'okmobility_booking_ref',
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
        'dropoff_location_id', // Same as pickup location for OK Mobility
        'remarks',
        'booking_status',
        'api_response',
        'user_id',
        'affiliate_discount_code',
        'affiliate_discount_amount',
    ];

    protected $casts = [
        'customer_details' => 'array',
        'selected_extras' => 'array',
        'api_response' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'vehicle_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'affiliate_discount_amount' => 'decimal:2',
    ];

    /**
     * Get the user that owns the booking.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get formatted pickup datetime
     */
    public function getPickupDatetimeAttribute()
    {
        return $this->start_date ? $this->start_date->format('Y-m-d') . ' ' . $this->start_time : '';
    }

    /**
     * Get formatted dropoff datetime
     */
    public function getDropoffDatetimeAttribute()
    {
        return $this->end_date ? $this->end_date->format('Y-m-d') . ' ' . $this->end_time : '';
    }

    /**
     * Get booking duration in days
     */
    public function getDurationDaysAttribute()
    {
        return ($this->start_date && $this->end_date) ? $this->start_date->diffInDays($this->end_date) : 0;
    }

    /**
     * Get customer name
     */
    public function getCustomerNameAttribute()
    {
        return ($this->customer_details['firstname'] ?? '') . ' ' . ($this->customer_details['surname'] ?? '');
    }

    /**
     * Get customer email
     */
    public function getCustomerEmailAttribute()
    {
        return $this->customer_details['email'] ?? '';
    }

    /**
     * Check if booking is confirmed
     */
    public function isConfirmed()
    {
        return in_array($this->booking_status, ['confirmed', 'completed']);
    }

    /**
     * Check if booking is cancelled
     */
    public function isCancelled()
    {
        return $this->booking_status === 'cancelled';
    }

    /**
     * Check if booking is pending
     */
    public function isPending()
    {
        return $this->booking_status === 'pending';
    }
}
