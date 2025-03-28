<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_type', 
        'plan_value',
        'features',
        'plan_description',
    ];

    protected $casts = [
        'plan_value' => 'decimal:2',
        'features' => 'array'
    ];
    


// In Plan.php model
public function vendorVehiclePlans()
{
    return $this->hasMany(VendorVehiclePlan::class);
}
}