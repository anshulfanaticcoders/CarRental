<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorVehiclePlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'vehicle_id',
        'plan_id',
        'plan_type',
        'price',
        'features'
    ];

    protected $casts = [
        'features' => 'array',
        'price' => 'decimal:2'
    ];

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}