<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
//use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'category_id',
        'brand',
        'model',
        'color',
        'mileage',
        'transmission',
        'fuel',
        'seating_capacity',
        'number_of_doors',
        'luggage_capacity',
        'horsepower',
        'co2',
        'location',
        'latitude',
        'longitude',
        'status',
        'features', 
        'featured',
        'security_deposit',
        'payment_method',
        'price_per_day',
        
        // vehicle specifications fillables
        'registration_number',
        'registration_country',
        'registration_date',
        'gross_vehicle_mass',
        'vehicle_height',
        'dealer_cost',
        'phone_number',
        
    ];

    public function category()
    {
        return $this->belongsTo(VehicleCategory::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

     // Relationship with VehicleSpecification
     public function specifications()
     {
         return $this->hasOne(VehicleSpecification::class, 'vehicle_id');
     }

      // Relationship with VehicleImage
    public function images()
    {
        return $this->hasMany(VehicleImage::class, 'vehicle_id');
    }
    public function vendorProfile()
    {
        return $this->belongsTo(UserProfile::class, 'vendor_id', 'user_id');
    }
    public function vendorProfileData()
{
    return $this->belongsTo(VendorProfile::class, 'vendor_id', 'user_id');
}
}