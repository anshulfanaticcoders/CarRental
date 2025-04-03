<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'user_id',
        'vehicle_id',
        'vendor_profile_id',
        'rating',
        'review_text',
        'reply_text',
        'status',
    ];

        public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function vendorProfileData()
{
    return $this->belongsTo(VendorProfile::class, 'vendor_profile_id'); 
}

}