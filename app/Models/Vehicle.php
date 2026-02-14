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
        'location_type',
        'latitude',
        'longitude',
        'city',
        'state', 
        'country',
        'full_vehicle_address',
        'status',
        'features',
        'featured',
        'security_deposit',
        'payment_method',
        'guidelines',
        'price_per_day',
        'price_per_week',
        'weekly_discount',
        'price_per_month',
        'monthly_discount',
        'preferred_price_type',
        'limited_km',
        'cancellation_available',
        'price_per_km',
        'pickup_times',
        'return_times',

        // vehicle specifications fillables
        'registration_number',
        'registration_country',
        'registration_date',
        'gross_vehicle_mass',
        'vehicle_height',
        'dealer_cost',
        'phone_number',

        // Blocking dates
        // 'blocking_start_date',
        // 'blocking_end_date',

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
    public function features()
    {
        return $this->belongsToMany(VehicleFeature::class);
    }
    public function vendorProfile()
    {
        return $this->belongsTo(UserProfile::class, 'vendor_id', 'user_id');
    }
    public function vendorProfileData()
    {
        return $this->belongsTo(VendorProfile::class, 'vendor_id', 'user_id');
    }
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'vehicle_id');
    }
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'user_vehicle', 'vehicle_id', 'user_id');
    }

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function benefits()
    {
        return $this->hasOne(VehicleBenefit::class);
    }

    // In Vehicle.php model
    public function vendorPlans()
    {
        return $this->hasMany(VendorVehiclePlan::class);
    }

    // Add the relationship for VendorVehicleAddon
    public function addons()
    {
        return $this->hasMany(VendorVehicleAddon::class, 'vehicle_id');
    }

    public function blockings()
    {
        return $this->hasMany(BlockingDate::class, 'vehicle_id');
    }


    // If using JSON columns, add casts
    protected $casts = [
        'pickup_times' => 'array',
        'return_times' => 'array',
    ];
}
