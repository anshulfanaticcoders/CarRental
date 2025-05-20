<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
        'image'
    ];

    /**
     * Get all of the features for the VehicleCategory.
     */
    public function features()
    {
        return $this->hasMany(VehicleFeature::class, 'category_id');
    }
}
