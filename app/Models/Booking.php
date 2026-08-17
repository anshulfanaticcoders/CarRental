<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_number',
        'customer_id',
        'vehicle_id',
        'provider_source',
        'provider_vehicle_id',
        'provider_booking_ref',
        'provider_metadata',
        'provider_grand_total',
        'vehicle_name',
        'vehicle_image',
        'pickup_date',
        'return_date',
        'pickup_location',
        'return_location',
        'pickup_time',
        'return_time',
        'plan',
        'plan_price',
        'total_days',
        'base_price',
        'preferred_day',
        'extra_charges',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'pending_amount',
        'amount_paid',
        'booking_currency',
        'payment_status',
        'booking_status',
        'booking_reference',
        'cancellation_reason',
        'notes',
        'stripe_session_id',
        'stripe_payment_intent_id',
    ];

    protected $casts = [
        'pickup_date' => 'datetime',
        'return_date' => 'datetime',
        'base_price' => 'decimal:2',
        'extra_charges' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'pending_amount' => 'decimal:2',
        'provider_metadata' => 'array',
    ];

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    // New relationship to get vendorProfile through vehicle
    public function vendorProfile()
    {
        return $this->hasOneThrough(UserProfile::class, Vehicle::class, 'id', 'user_id', 'vehicle_id', 'vendor_id');
    }

    // Generate unique booking number. The old 4-digit random (9,999 values per
    // month, no uniqueness check) started colliding under real volume; each
    // collision 500'd the payment webhook. 6 digits + an exists() check leaves
    // only the concurrent-insert race, which the caller retries.
    public static function generateBookingNumber(): string
    {
        $prefix = 'BK'.date('Y').date('m');

        do {
            $number = $prefix.str_pad((string) mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (static::where('booking_number', $number)->exists());

        return $number;
    }

    public function payments()
    {
        return $this->hasMany(BookingPayment::class);
    }

    public function amounts(): HasOne
    {
        return $this->hasOne(BookingAmount::class);
    }

    public function extras()
    {
        return $this->hasMany(BookingExtra::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(BookingOffer::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class); // No need to specify foreign key if it's plan_id
    }

    public function damageProtection()
    {
        return $this->hasOne(DamageProtection::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class, 'booking_id');
    }

    public function vehicleBenefit(): HasOne
    {
        return $this->hasOne(VehicleBenefit::class);
    }
}
