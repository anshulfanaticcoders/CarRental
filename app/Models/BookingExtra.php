<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BookingExtra extends Model
{
    use HasFactory;

    protected $fillable = [
        'extra_type',
        'extra_name',
        'description',
        'quantity',
        'price'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer'
    ];

    // Helper method to calculate total price for this extra
    public function getTotalPrice(): float
    {
        return $this->price * $this->quantity;
    }
}