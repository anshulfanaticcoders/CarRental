<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TapfiliateUserMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tapfiliate_affiliate_id',
        'referral_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
