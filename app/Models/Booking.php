<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_number',
        'customer_id',
        'vehicle_id',
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
        'payment_status',
        'booking_status',
        'cancellation_reason',
        'notes',
    ];

    protected $casts = [
        'pickup_date' => 'datetime',
        'return_date' => 'datetime',
        'base_price' => 'decimal:2',
        'extra_charges' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
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
    // Generate unique booking number
    public static function generateBookingNumber(): string
    {
        $prefix = 'BK';
        $year = date('Y');
        $month = date('m');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return $prefix . $year . $month . $random;
    }

    public function payments()
    {
        return $this->hasMany(BookingPayment::class);
    }

    public function extras()
    {
        return $this->hasMany(BookingExtra::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class); // No need to specify foreign key if it's plan_id
    }

    public function damageProtection()
    {
        return $this->hasOne(DamageProtection::class);
    }

}