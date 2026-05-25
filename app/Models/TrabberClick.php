<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrabberClick extends Model
{
    use HasFactory;

    protected $fillable = [
        'clickid',
        'offer_id',
        'source',
        'clicked_url',
        'landing_url',
        'search_metadata',
        'clicked_at',
        'expires_at',
    ];

    protected $casts = [
        'search_metadata' => 'array',
        'clicked_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
