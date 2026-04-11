<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
//use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{

    use HasFactory;

    public const SEARCHABLE_STATUSES = ['active', 'available'];

    protected $fillable = [
        'vendor_id',
        'vendor_location_id',
        'category_id',
        'brand',
        'model',
        'color',
        'mileage',
        'transmission',
        'fuel',
        'body_style',
        'air_conditioning',
        'sipp_code',
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
        'terms_policy',
        'fuel_policy',
        'pickup_instructions',
        'dropoff_instructions',
        'location_phone',
        'rental_policy',
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

    public function vendorLocation()
    {
        return $this->belongsTo(VendorLocation::class, 'vendor_location_id');
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

    public function apiBookings()
    {
        return $this->hasMany(ApiBooking::class, 'vehicle_id');
    }

    public function operatingHours()
    {
        return $this->hasMany(VehicleOperatingHour::class)->orderBy('day_of_week');
    }

    /**
     * Check if the vehicle is available for handover on the given day and time.
     *
     * @param int    $dayOfWeek 0=Monday ... 6=Sunday
     * @param string $time      HH:MM format (24h)
     */
    public function isAvailableAt(int $dayOfWeek, string $time): bool
    {
        $hours = $this->operatingHours->firstWhere('day_of_week', $dayOfWeek);

        // No hours record = treat as available (defensive fallback)
        if (!$hours) {
            return true;
        }

        if (!$hours->is_open) {
            return false;
        }

        return $time >= $hours->open_time && $time <= $hours->close_time;
    }

    /**
     * Get operating hours for a specific day.
     */
    public function getOperatingHoursForDay(int $dayOfWeek): ?VehicleOperatingHour
    {
        return $this->operatingHours->firstWhere('day_of_week', $dayOfWeek);
    }

    public static function searchableStatuses(): array
    {
        return self::SEARCHABLE_STATUSES;
    }

    // If using JSON columns, add casts
    protected $casts = [
        'pickup_times' => 'array',
        'return_times' => 'array',
        'air_conditioning' => 'boolean',
    ];
}
