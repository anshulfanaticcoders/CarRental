<?php

namespace App\Models\Affiliate;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AffiliateQrCode extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'affiliate_qr_codes';

    protected $fillable = [
        'uuid',
        'business_id',
        'location_id',
        'qr_code_value',
        'qr_hash',
        'short_code',
        'qr_url',
        'qr_image_path',
        'qr_pdf_path',
        'discount_type',
        'discount_value',
        'min_booking_amount',
        'max_discount_amount',
        'valid_from',
        'valid_until',
        'usage_limit',
        'daily_usage_limit',
        'monthly_usage_limit',
        'current_usage',
        'geo_restriction_enabled',
        'max_distance_km',
        'customer_restriction',
        'min_customer_age',
        'allowed_countries',
        'status',
        'security_level',
        'total_scans',
        'unique_scans',
        'conversion_count',
        'total_revenue_generated',
        'last_scanned_at',
        'expires_at',
        'created_at',
        'updated_at',
    ];

    // Add accessors for frontend compatibility
    protected $appends = [
        'usage_count',
        'location_name',
        'location_address',
        'is_revoked',
        'image_url'
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_booking_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'geo_restriction_enabled' => 'boolean',
        'max_distance_km' => 'decimal:2',
        'allowed_countries' => 'array',
        'total_revenue_generated' => 'decimal:2',
        'last_scanned_at' => 'datetime',
        'expires_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $dates = [
        'valid_from',
        'valid_until',
        'last_scanned_at',
        'expires_at',
        'deleted_at',
    ];

    /**
     * Get the business that owns this QR code.
     */
    public function business()
    {
        return $this->belongsTo(AffiliateBusiness::class, 'business_id', 'id');
    }

    /**
     * Get the location for this QR code.
     */
    public function location()
    {
        return $this->belongsTo(AffiliateBusinessLocation::class);
    }

    /**
     * Get the customer scans for this QR code.
     */
    public function customerScans()
    {
        return $this->hasMany(AffiliateCustomerScan::class);
    }

    /**
     * Get the commissions generated from this QR code.
     */
    public function commissions()
    {
        return $this->hasManyThrough(
            AffiliateCommission::class,
            AffiliateCustomerScan::class,
            'qr_code_id',
            'qr_scan_id'
        );
    }

    /**
     * Check if the QR code is currently valid.
     */
    public function isValid(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->valid_from && $this->valid_from->isFuture()) {
            return false;
        }

        if ($this->valid_until && $this->valid_until->isPast()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->usage_limit && $this->current_usage >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Check if the QR code has expired.
     */
    public function isExpired(): bool
    {
        return ($this->valid_until && $this->valid_until->isPast()) ||
               ($this->expires_at && $this->expires_at->isPast());
    }

    /**
     * Check if the QR code has reached its usage limit.
     */
    public function hasReachedUsageLimit(): bool
    {
        return $this->usage_limit && $this->current_usage >= $this->usage_limit;
    }

    /**
     * Check if the QR code has reached its daily usage limit.
     */
    public function hasReachedDailyUsageLimit(): bool
    {
        if (!$this->daily_usage_limit) {
            return false;
        }

        $todayScans = $this->customerScans()
            ->whereDate('created_at', today())
            ->count();

        return $todayScans >= $this->daily_usage_limit;
    }

    /**
     * Check if the QR code has reached its monthly usage limit.
     */
    public function hasReachedMonthlyUsageLimit(): bool
    {
        if (!$this->monthly_usage_limit) {
            return false;
        }

        $monthScans = $this->customerScans()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return $monthScans >= $this->monthly_usage_limit;
    }

    /**
     * Increment the usage count.
     */
    public function incrementUsage(): void
    {
        $this->increment('current_usage');
        $this->increment('total_scans');
        $this->touch('last_scanned_at');
    }

    /**
     * Get the conversion rate.
     */
    public function getConversionRate(): float
    {
        if ($this->total_scans === 0) {
            return 0;
        }

        return ($this->conversion_count / $this->total_scans) * 100;
    }

    /**
     * Get the QR code image URL.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->qr_image_path) {
            return null;
        }

        // Just return the path since frontend will construct the full URL
        return $this->qr_image_path;
    }

    /**
     * Get the QR code PDF URL.
     */
    public function getPdfUrlAttribute(): ?string
    {
        if (!$this->qr_pdf_path) {
            return null;
        }

        return \Storage::disk('upcloud')->url($this->qr_pdf_path);
    }

    /**
     * Scope a query to include only active QR codes.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to include only valid QR codes.
     */
    public function scopeValid($query)
    {
        return $query->where('status', 'active')
                    ->where(function ($q) {
                        $q->whereNull('valid_until')
                          ->orWhere('valid_until', '>', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Scope a query to include only expired QR codes.
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('valid_until', '<', now())
              ->orWhere('expires_at', '<', now());
        });
    }

    /**
     * Scope a query to include QR codes for a specific business.
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope a query to include QR codes for a specific location.
     */
    public function scopeForLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    /**
     * Get usage count accessor (alias for current_usage).
     */
    public function getUsageCountAttribute(): int
    {
        return $this->current_usage ?? 0;
    }

    /**
     * Get location name accessor.
     */
    public function getLocationNameAttribute(): ?string
    {
        return $this->location ? $this->location->name : 'Main Location';
    }

    /**
     * Get location address accessor.
     */
    public function getLocationAddressAttribute(): ?string
    {
        if (!$this->location) {
            return 'Online';
        }

        return implode(', ', array_filter([
            $this->location->address_line_1,
            $this->location->city,
            $this->location->country
        ]));
    }

    /**
     * Get is_revoked accessor.
     */
    public function getIsRevokedAttribute(): bool
    {
        return $this->status === 'suspended' || $this->status === 'revoked';
    }
}