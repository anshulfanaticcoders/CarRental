<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdobeBooking extends Model
{
    use HasFactory;

    protected $table = 'adobe_bookings';

    protected $fillable = [
        'adobe_booking_ref',
        'vehicle_category',
        'vehicle_model',
        'pickup_location_id',
        'dropoff_location_id',
        'start_date',
        'start_time',
        'end_date',
        'end_time',
        'customer_code',
        'customer_details',
        'tdr_total',
        'pli_total',
        'ldw_total',
        'spp_total',
        'dro_total',
        'extras_total',
        'base_rate',
        'vehicle_total',
        'grand_total',
        'currency',
        'selected_protections',
        'selected_extras',
        'payment_handler_ref',
        'payment_status',
        'payment_type',
        'booking_status',
        'customer_comment',
        'reference',
        'flight_number',
        'language',
        'api_response',
        'pre_registration_link',
        'user_id',
        'stripe_checkout_session_id',
        'payment_completed_at',
        'cancelled_at',
    ];

    /**
     * Get the user that owns the Adobe booking.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $casts = [
        'customer_details' => 'array',
        'selected_protections' => 'array',
        'selected_extras' => 'array',
        'api_response' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'payment_completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'tdr_total' => 'decimal:2',
        'pli_total' => 'decimal:2',
        'ldw_total' => 'decimal:2',
        'spp_total' => 'decimal:2',
        'dro_total' => 'decimal:2',
        'extras_total' => 'decimal:2',
        'base_rate' => 'decimal:2',
        'vehicle_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    /**
     * Get the human-readable status for the booking.
     */
    public function getStatusLabelAttribute()
    {
        return match($this->booking_status) {
            'pending' => 'Pending Confirmation',
            'confirmed' => 'Confirmed',
            'cancelled' => 'Cancelled',
            'completed' => 'Completed',
            'failed' => 'Failed',
            default => 'Unknown Status'
        };
    }

    /**
     * Get the human-readable payment status.
     */
    public function getPaymentStatusLabelAttribute()
    {
        return match($this->payment_status) {
            'pending' => 'Pending Payment',
            'paid' => 'Paid',
            'failed' => 'Payment Failed',
            'refunded' => 'Refunded',
            default => 'Unknown Status'
        };
    }

    /**
     * Check if the booking is paid.
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if the booking is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->booking_status === 'cancelled';
    }

    /**
     * Get the full pickup datetime.
     */
    public function getFullPickupDatetimeAttribute()
    {
        return $this->start_date->format('Y-m-d') . ' ' . $this->start_time->format('H:i');
    }

    /**
     * Get the full dropoff datetime.
     */
    public function getFullDropoffDatetimeAttribute()
    {
        return $this->end_date->format('Y-m-d') . ' ' . $this->end_time->format('H:i');
    }

    /**
     * Get the rental duration in days.
     */
    public function getRentalDaysAttribute()
    {
        return $this->start_date->diffInDays($this->end_date);
    }

    /**
     * Scope to get only active bookings.
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('booking_status', ['cancelled', 'failed']);
    }

    /**
     * Scope to get bookings by user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
