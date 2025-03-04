<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DamageProtection extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'before_images',
        'after_images',
    ];

    protected $casts = [
        'before_images' => 'array',
        'after_images' => 'array',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}