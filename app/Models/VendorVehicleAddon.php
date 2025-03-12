<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorVehicleAddon extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'vehicle_id',
        'addon_id',
        'price',
        'quantity',
        'description',
        'extra_type', 
        'extra_name', 
    ];

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function addon()
    {
        return $this->belongsTo(BookingAddon::class, 'addon_id');
    }
}