<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'name',
        'code',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'country',
        'country_code',
        'latitude',
        'longitude',
        'location_type',
        'iata_code',
        'phone',
        'pickup_instructions',
        'dropoff_instructions',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_active' => 'boolean',
    ];

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'vendor_location_id');
    }
}
