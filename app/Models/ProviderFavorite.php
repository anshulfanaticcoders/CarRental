<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderFavorite extends Model
{
    use HasFactory;

    protected $table = 'user_provider_favorites';

    protected $fillable = [
        'user_id',
        'vehicle_key',
        'source',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
