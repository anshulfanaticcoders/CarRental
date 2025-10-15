<?php

namespace App\Models\Affiliate;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AffiliateBusinessModel extends Model
{
    use HasFactory;

    protected $table = 'affiliate_business_models';

    protected $fillable = [
        'uuid',
        'business_id',
        'discount_type',
        'discount_value',
        'min_booking_amount',
        'max_discount_amount',
        'commission_rate',
        'commission_type',
        'payout_threshold',
        'max_qr_codes_per_month',
        'qr_code_validity_days',
        'is_active',
        'configured_by',
        'configured_at',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_booking_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'payout_threshold' => 'decimal:2',
        'is_active' => 'boolean',
        'configured_at' => 'datetime',
    ];

    protected $dates = [
        'configured_at',
    ];

    /**
     * Get the business that owns this model.
     */
    public function business()
    {
        return $this->belongsTo(AffiliateBusiness::class, 'business_id');
    }

    /**
     * Get the admin who configured this business model.
     */
    public function configuredBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'configured_by');
    }

    /**
     * Check if this model has custom discount settings.
     */
    public function hasCustomDiscount(): bool
    {
        return $this->discount_value !== null;
    }

    /**
     * Check if this model has custom commission settings.
     */
    public function hasCustomCommission(): bool
    {
        return $this->commission_rate !== null;
    }

    /**
     * Check if this model has custom settings.
     */
    public function hasCustomSettings(): bool
    {
        return $this->hasCustomDiscount() || $this->hasCustomCommission();
    }

    /**
     * Get the effective discount value.
     */
    public function getEffectiveDiscountValue(): float
    {
        if ($this->discount_value !== null) {
            return $this->discount_value;
        }

        $globalSettings = AffiliateGlobalSetting::first();
        return $globalSettings ? $globalSettings->global_discount_value : 0;
    }

    /**
     * Get the effective commission rate.
     */
    public function getEffectiveCommissionRate(): float
    {
        if ($this->commission_rate !== null) {
            return $this->commission_rate;
        }

        $globalSettings = AffiliateGlobalSetting::first();
        return $globalSettings ? $globalSettings->global_commission_rate : 0;
    }

    /**
     * Scope a query to include only active models.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to include models with custom settings.
     */
    public function scopeWithCustomSettings($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('discount_value')
              ->orWhereNotNull('commission_rate');
        });
    }

    /**
     * Scope a query to include models with custom discount settings.
     */
    public function scopeWithCustomDiscount($query)
    {
        return $query->whereNotNull('discount_value');
    }

    /**
     * Scope a query to include models with custom commission settings.
     */
    public function scopeWithCustomCommission($query)
    {
        return $query->whereNotNull('commission_rate');
    }
}