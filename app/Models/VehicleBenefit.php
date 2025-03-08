<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleBenefit extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'limited_km_per_day',
        'limited_km_per_week',
        'limited_km_per_month',
        'limited_km_per_day_range',
        'limited_km_per_week_range',
        'limited_km_per_month_range',
        'cancellation_available_per_day',
        'cancellation_available_per_week',
        'cancellation_available_per_month',
        'cancellation_available_per_day_date',
        'cancellation_available_per_week_date',
        'cancellation_available_per_month_date',
        'price_per_km_per_day',
        'price_per_km_per_week',
        'price_per_km_per_month',
        'minimum_driver_age',
    ];

    /**
     * Define relationship with Vehicle
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
