<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'company_phone_number',
        'company_email',
        'company_address',
        'company_gst_number',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

     // Add this relationship
     public function vehicles()
     {
         return $this->hasMany(Vehicle::class, 'user_id');
     }

     public function reviews()
{
    return $this->hasMany(Review::class, 'vendor_profile_id');
}

}
