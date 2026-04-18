<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferEffect extends Model
{
    use HasFactory;

    protected $fillable = [
        'offer_id',
        'type',
        'config',
        'sort_order',
    ];

    protected $casts = [
        'config' => 'array',
        'sort_order' => 'integer',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
}
