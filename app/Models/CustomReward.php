<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reward_type',
        'amount',
        'currency',
        'status',
        'tapfiliate_conversion_id',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
