<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;

class LocautoBooking extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'confirmation_number',
        'pickup_location_code',
        'dropoff_location_code',
        'pickup_date',
        'pickup_time',
        'return_date',
        'return_time',
        'vehicle_code',
        'vehicle_details',
        'customer_details',
        'selected_extras',
        'total_amount',
        'deposit_amount',
        'currency',
        'status',
        'payment_type',
        'api_request',
        'api_response',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'pickup_date' => 'date',
        'return_date' => 'date',
        'pickup_time' => 'datetime:H:i:s',
        'return_time' => 'datetime:H:i:s',
        'vehicle_details' => 'array',
        'customer_details' => 'array',
        'selected_extras' => 'array',
        'total_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'api_request' => 'array',
        'api_response' => 'array',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'api_request',
        'api_response',
    ];

    /**
     * Get the user that owns the booking.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the pickup location details.
     */
    public function getPickupLocationAttribute(): string
    {
        $locations = json_decode(file_get_contents(public_path('unified_locations.json')), true);

        foreach ($locations as $location) {
            if ($location['provider'] === 'Locauto Rent' && isset($location['locauto_code']) && $location['locauto_code'] === $this->pickup_location_code) {
                return $location['name'];
            }
        }

        return $this->pickup_location_code;
    }

    /**
     * Get the dropoff location details.
     */
    public function getDropoffLocationAttribute(): string
    {
        $locations = json_decode(file_get_contents(public_path('unified_locations.json')), true);

        foreach ($locations as $location) {
            if ($location['provider'] === 'Locauto Rent' && isset($location['locauto_code']) && $location['locauto_code'] === $this->dropoff_location_code) {
                return $location['name'];
            }
        }

        return $this->dropoff_location_code;
    }

    /**
     * Get formatted pickup datetime.
     */
    public function getFormattedPickupDatetimeAttribute(): string
    {
        return $this->pickup_date->format('d M Y') . ' at ' . $this->pickup_time->format('H:i');
    }

    /**
     * Get formatted return datetime.
     */
    public function getFormattedReturnDatetimeAttribute(): string
    {
        return $this->return_date->format('d M Y') . ' at ' . $this->return_time->format('H:i');
    }

    /**
     * Get booking duration in days.
     */
    public function getDurationDaysAttribute(): int
    {
        return $this->pickup_date->diffInDays($this->return_date);
    }

    /**
     * Get vehicle group name from SIPP code.
     */
    public function getVehicleGroupAttribute(): string
    {
        $sippGroups = [
            'MBMR' => 'Mini Car (Group A)',
            'ECAR' => 'Economy Car (Group B)',
            'CCAR' => 'Compact Car (Group C)',
            'CDAR' => 'Compact Car (Group D)',
            'EDMR' => 'Economy Car (Group E)',
            'FDAR' => 'Full Size Car (Group F)',
            'IFAR' => 'Intermediate SUV (Group I)',
            'IWAV' => 'Intermediate Wagon (Group I)',
            'IVAR' => 'Intermediate Van (Group I)',
            'LDAR' => 'Luxury Car (Group L)',
            'LCAR' => 'Luxury Car (Group L)',
            'MDAR' => 'Midsize Car (Group M)',
            'MFAR' => 'Midsize SUV (Group M)',
            'PCAR' => 'Premium Car (Group P)',
            'PFAR' => 'Premium SUV (Group P)',
            'SFAR' => 'Standard SUV (Group S)',
            'SMIN' => 'Sports Car (Group S)',
            'STAR' => 'Standard Car (Group S)',
            'SVAR' => 'Standard Van (Group S)',
            'XCAR' => 'Special Car (Group X)',
            'XFAR' => 'Special SUV (Group X)',
        ];

        return $sippGroups[$this->vehicle_code] ?? $this->vehicle_code;
    }

    /**
     * Check if booking is confirmed.
     */
    public function isConfirmed(): bool
    {
        return in_array($this->status, ['confirmed', 'reserved']);
    }

    /**
     * Check if booking is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if booking is in the past.
     */
    public function isPast(): bool
    {
        return $this->return_date < now();
    }

    /**
     * Check if booking is upcoming.
     */
    public function isUpcoming(): bool
    {
        return $this->pickup_date > now();
    }

    /**
     * Scope a query to only include confirmed bookings.
     */
    public function scopeConfirmed($query)
    {
        return $query->whereIn('status', ['confirmed', 'reserved']);
    }

    /**
     * Scope a query to only include cancelled bookings.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope a query to only include upcoming bookings.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('pickup_date', '>', now());
    }

    /**
     * Scope a query to only include past bookings.
     */
    public function scopePast($query)
    {
        return $query->where('return_date', '<', now());
    }

    /**
     * Get booking status with human readable label.
     */
    public function getStatusLabelAttribute(): string
    {
        $statuses = [
            'pending' => 'Pending Confirmation',
            'confirmed' => 'Confirmed',
            'reserved' => 'Reserved',
            'cancelled' => 'Cancelled',
            'completed' => 'Completed',
            'modified' => 'Modified',
        ];

        return $statuses[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get payment type label.
     */
    public function getPaymentTypeLabelAttribute(): string
    {
        $types = [
            'POA' => 'Pay on Arrival',
            'PREPAID' => 'Prepaid',
            'GUARANTEE' => 'Credit Card Guarantee',
        ];

        return $types[$this->payment_type] ?? $this->payment_type;
    }

    /**
     * Generate unique confirmation number.
     */
    public static function generateConfirmationNumber(): string
    {
        do {
            $number = 'LCR' . date('Y') . strtoupper(substr(uniqid(), -6));
        } while (self::where('confirmation_number', $number)->exists());

        return $number;
    }

    /**
     * Create booking from API response.
     */
    public static function createFromApiResponse(array $bookingData, array $apiRequest, array $apiResponse): self
    {
        return self::create([
            'confirmation_number' => $bookingData['confirmation_number'] ?? static::generateConfirmationNumber(),
            'pickup_location_code' => $bookingData['pickup_location_code'],
            'dropoff_location_code' => $bookingData['dropoff_location_code'],
            'pickup_date' => $bookingData['pickup_date'],
            'pickup_time' => $bookingData['pickup_time'],
            'return_date' => $bookingData['return_date'],
            'return_time' => $bookingData['return_time'],
            'vehicle_code' => $bookingData['vehicle_code'],
            'vehicle_details' => $bookingData['vehicle_details'],
            'customer_details' => $bookingData['customer_details'],
            'selected_extras' => $bookingData['selected_extras'] ?? null,
            'total_amount' => $bookingData['total_amount'],
            'deposit_amount' => $bookingData['deposit_amount'] ?? 0,
            'currency' => $bookingData['currency'] ?? 'EUR',
            'status' => $bookingData['status'] ?? 'pending',
            'payment_type' => $bookingData['payment_type'] ?? 'POA',
            'api_request' => $apiRequest,
            'api_response' => $apiResponse,
            'user_id' => auth()->id(),
        ]);
    }
}