<?php

namespace App\Models\Affiliate;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AffiliateGlobalSetting extends Model
{
    use HasFactory;

    protected $table = 'affiliate_global_settings';

    protected $fillable = [
        'uuid',
        'global_discount_type',
        'global_discount_value',
        'global_min_booking_amount',
        'global_max_discount_amount',
        'global_commission_rate',
        'global_commission_type',
        'global_payout_threshold',
        'max_qr_codes_per_business',
        'qr_code_validity_days',
        'session_tracking_hours',
        'allow_business_override',
        'require_business_verification',
        'auto_approve_commissions',
        'configured_by',
        'last_updated_at',
    ];

    protected $casts = [
        'global_discount_value' => 'decimal:2',
        'global_min_booking_amount' => 'decimal:2',
        'global_max_discount_amount' => 'decimal:2',
        'global_commission_rate' => 'decimal:2',
        'global_payout_threshold' => 'decimal:2',
        'allow_business_override' => 'boolean',
        'require_business_verification' => 'boolean',
        'auto_approve_commissions' => 'boolean',
        'last_updated_at' => 'datetime',
    ];

    protected $dates = [
        'last_updated_at',
    ];

    /**
     * Get the admin who configured the global settings.
     */
    public function configuredBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'configured_by');
    }

    /**
     * Get the default discount type.
     */
    public function getDiscountTypeAttribute($value)
    {
        return $this->global_discount_type;
    }

    /**
     * Get the default discount value.
     */
    public function getDiscountValueAttribute($value)
    {
        return $this->global_discount_value;
    }

    /**
     * Get the default commission rate.
     */
    public function getCommissionRateAttribute($value)
    {
        return $this->global_commission_rate;
    }

    /**
     * Get the default payout threshold.
     */
    public function getPayoutThresholdAttribute($value)
    {
        return $this->global_payout_threshold;
    }

    /**
     * Scope a query to include only active settings.
     */
    public function scopeActive($query)
    {
        return $query->where('global_commission_rate', '>', 0)
                      ->orWhere('global_discount_value', '>', 0);
    }
}