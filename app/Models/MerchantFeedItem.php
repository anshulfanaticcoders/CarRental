<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantFeedItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'feed_name',
        'feed_key',
        'source',
        'provider',
        'provider_vehicle_id',
        'title',
        'description',
        'link',
        'image_link',
        'price',
        'currency',
        'availability',
        'brand',
        'product_type',
        'condition',
        'location_name',
        'city',
        'country',
        'raw_attributes',
        'last_seen_at',
        'expires_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'raw_attributes' => 'array',
        'last_seen_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
