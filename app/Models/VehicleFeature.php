<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'feature_name',
        'icon_url',
    ];

    /**
     * Get the category that owns the feature.
     */
    public function category()
    {
        return $this->belongsTo(VehicleCategory::class, 'category_id');
    }
}
