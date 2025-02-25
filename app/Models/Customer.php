<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'flight_number',
        'driver_age',   
        'additional_info',
        'stripe_customer_id',
    ];

    // Relationship with User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relationship with UserProfile
    public function profile(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'user_id');
    }

    // Relationship with Bookings
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    // Helper method to get full name
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // Helper method to format phone number
    public function getFormattedPhoneAttribute(): string
    {
        // Add your phone formatting logic here if needed
        return $this->phone;
    }
}