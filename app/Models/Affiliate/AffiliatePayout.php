<?php

namespace App\Models\Affiliate;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AffiliatePayout extends Model
{
    protected $fillable = [
        'uuid',
        'business_id',
        'total_amount',
        'currency',
        'status',
        'period_start',
        'period_end',
        'bank_transfer_reference',
        'paid_at',
        'paid_by',
        'admin_notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (AffiliatePayout $payout) {
            if (empty($payout->uuid)) {
                $payout->uuid = (string) Str::uuid();
            }
        });
    }

    public function business()
    {
        return $this->belongsTo(AffiliateBusiness::class, 'business_id');
    }

    public function commissions()
    {
        return $this->hasMany(AffiliateCommission::class, 'payout_id');
    }

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }
}
