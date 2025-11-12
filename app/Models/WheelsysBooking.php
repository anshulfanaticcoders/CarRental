<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WheelsysBooking extends Model
{
    use HasFactory;

    protected $table = 'wheelsys_bookings';

    protected $fillable = [
        // Wheelsys specific fields
        'wheelsys_booking_ref',
        'wheelsys_quote_id',
        'wheelsys_group_code',
        'wheelsys_status',
        'wheelsys_ref_no',

        // Vehicle information
        'vehicle_group_name',
        'vehicle_category',
        'vehicle_sipp_code',
        'vehicle_image_url',
        'vehicle_passengers',
        'vehicle_doors',
        'vehicle_bags',
        'vehicle_suitcases',

        // Rental details
        'pickup_station_code',
        'pickup_station_name',
        'pickup_date',
        'pickup_time',
        'return_station_code',
        'return_station_name',
        'return_date',
        'return_time',
        'rental_duration_days',

        // Customer details
        'customer_details',
        'customer_age',
        'customer_driver_licence',
        'customer_address',

        // Pricing
        'base_rate_total',
        'extras_total',
        'taxes_total',
        'grand_total',
        'currency',

        // Extras and options
        'selected_extras',
        'available_extras',

        // Payment processing
        'stripe_payment_intent_id',
        'stripe_payment_status',
        'amount_paid',
        'paid_at',

        // Booking management
        'booking_status',
        'customer_notes',
        'admin_notes',

        // API integration data
        'api_response',
        'api_quote_response',
        'api_reservation_response',

        // System fields
        'user_id',
        'affiliate_code',
        'affiliate_data',
        'session_id',
    ];

    protected $casts = [
        'customer_details' => 'array',
        'selected_extras' => 'array',
        'available_extras' => 'array',
        'api_response' => 'array',
        'api_quote_response' => 'array',
        'api_reservation_response' => 'array',
        'affiliate_data' => 'array',
        'pickup_date' => 'date',
        'return_date' => 'date',
        'pickup_time' => 'datetime:H:i:s',
        'return_time' => 'datetime:H:i:s',
        'paid_at' => 'datetime',
        'base_rate_total' => 'decimal:2',
        'extras_total' => 'decimal:2',
        'taxes_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    protected $dates = [
        'pickup_date',
        'return_date',
        'paid_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the user that owns the Wheelsys booking.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include bookings with a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('booking_status', $status);
    }

    /**
     * Scope a query to only include paid bookings.
     */
    public function scopePaid($query)
    {
        return $query->where('stripe_payment_status', 'succeeded');
    }

    /**
     * Scope a query to only include confirmed bookings.
     */
    public function scopeConfirmed($query)
    {
        return $query->whereIn('booking_status', ['confirmed', 'paid', 'completed']);
    }

    /**
     * Check if the booking is paid.
     */
    public function isPaid()
    {
        return $this->stripe_payment_status === 'succeeded' && $this->amount_paid >= $this->grand_total;
    }

    /**
     * Check if the booking is confirmed in Wheelsys system.
     */
    public function isConfirmed()
    {
        return $this->wheelsys_status === 'RES' && !empty($this->wheelsys_booking_ref);
    }

    /**
     * Check if the booking is cancelled.
     */
    public function isCancelled()
    {
        return $this->wheelsys_status === 'CNC' || $this->booking_status === 'cancelled';
    }

    /**
     * Get formatted pickup datetime.
     */
    public function getFormattedPickupDatetimeAttribute()
    {
        return $this->pickup_date->format('M j, Y') . ' at ' . $this->pickup_time->format('g:i A');
    }

    /**
     * Get formatted return datetime.
     */
    public function getFormattedReturnDatetimeAttribute()
    {
        return $this->return_date->format('M j, Y') . ' at ' . $this->return_time->format('g:i A');
    }

    /**
     * Get total number of passengers.
     */
    public function getTotalPassengersAttribute()
    {
        return $this->vehicle_passengers;
    }

    /**
     * Get total luggage capacity.
     */
    public function getTotalLuggageAttribute()
    {
        return $this->vehicle_bags + $this->vehicle_suitcases;
    }

    /**
     * Get selected extras as human readable format.
     */
    public function getFormattedSelectedExtrasAttribute()
    {
        if (empty($this->selected_extras)) {
            return 'No extras selected';
        }

        $extras = [];
        foreach ($this->selected_extras as $extra) {
            $extras[] = $extra['name'] ?? $extra['code'] ?? 'Unknown';
        }

        return implode(', ', $extras);
    }

    /**
     * Calculate outstanding balance.
     */
    public function getOutstandingBalanceAttribute()
    {
        return max(0, $this->grand_total - $this->amount_paid);
    }

    /**
     * Update booking status based on payment and API response.
     */
    public function updateBookingStatus()
    {
        if ($this->isPaid() && $this->isConfirmed()) {
            $this->booking_status = 'confirmed';
        } elseif ($this->isPaid()) {
            $this->booking_status = 'paid';
        } elseif ($this->stripe_payment_status === 'succeeded') {
            $this->booking_status = 'paid';
        } elseif ($this->isCancelled()) {
            $this->booking_status = 'cancelled';
        }

        $this->save();
    }

    /**
     * Store API response data.
     */
    public function storeApiResponse($response, $type = 'general')
    {
        switch ($type) {
            case 'quote':
                $this->api_quote_response = $response;
                break;
            case 'reservation':
                $this->api_reservation_response = $response;
                break;
            default:
                $this->api_response = $response;
        }
        $this->save();
    }

    /**
     * Get customer first name.
     */
    public function getCustomerFirstNameAttribute()
    {
        return $this->customer_details['first_name'] ?? 'N/A';
    }

    /**
     * Get customer last name.
     */
    public function getCustomerLastNameAttribute()
    {
        return $this->customer_details['last_name'] ?? 'N/A';
    }

    /**
     * Get customer full name.
     */
    public function getCustomerFullNameAttribute()
    {
        return trim(($this->customer_details['first_name'] ?? '') . ' ' . ($this->customer_details['last_name'] ?? ''));
    }

    /**
     * Get customer email.
     */
    public function getCustomerEmailAttribute()
    {
        return $this->customer_details['email'] ?? 'N/A';
    }

    /**
     * Get customer phone.
     */
    public function getCustomerPhoneAttribute()
    {
        return $this->customer_details['phone'] ?? 'N/A';
    }
}