<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleSpecification extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'registration_number',
        'registration_country',
        'registration_date',
        'gross_vehicle_mass',
        'vehicle_height',
        'dealer_cost',
        'phone_number',
    ];

    // Relationship with Vehicle
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }
} 