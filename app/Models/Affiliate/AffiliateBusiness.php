<?php

namespace App\Models\Affiliate;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class AffiliateBusiness extends Model
{
    use HasFactory, SoftDeletes, Notifiable;

    protected $table = 'affiliate_businesses';

    protected $fillable = [
        'uuid',
        'business_registration_number',
        'tax_id',
        'name',
        'business_type',
        'parent_business_id',
        'contact_email',
        'contact_phone',
        'website',
        'legal_address',
        'billing_address',
        'city',
        'state',
        'country',
        'postal_code',
        'currency',
        'max_qr_codes_per_month',
        'allowed_discount_types',
        'verification_status',
        'verification_token',
        'verification_documents',
        'verification_notes',
        'status',
        'dashboard_access_token',
        'dashboard_token_expires_at',
        'last_dashboard_access',
        'verified_at',
    ];

    protected $casts = [
        'business_registration_number' => 'string',
        'tax_id' => 'string',
        'allowed_discount_types' => 'array',
        'verification_documents' => 'array',
        'dashboard_token_expires_at' => 'datetime',
        'last_dashboard_access' => 'datetime',
        'verified_at' => 'datetime',
    ];

    protected $dates = [
        'dashboard_token_expires_at',
        'last_dashboard_access',
        'verified_at',
        'deleted_at',
    ];

    protected $hidden = [
        'dashboard_access_token',
    ];

    /**
     * Get the parent business (for hotel chains).
     */
    public function parentBusiness()
    {
        return $this->belongsTo(AffiliateBusiness::class, 'parent_business_id');
    }

    /**
     * Get the child businesses (for hotel chains).
     */
    public function childBusinesses()
    {
        return $this->hasMany(AffiliateBusiness::class, 'parent_business_id');
    }

    /**
     * Get the business model configuration.
     */
    public function businessModel()
    {
        return $this->hasOne(AffiliateBusinessModel::class, 'business_id');
    }

    /**
     * Get the locations for this business.
     */
    public function locations()
    {
        return $this->hasMany(AffiliateBusinessLocation::class, 'business_id', 'id');
    }

    /**
     * Get the QR codes for this business.
     */
    public function qrCodes()
    {
        return $this->hasMany(AffiliateQrCode::class, 'business_id', 'id');
    }

    /**
     * Get the customer scans for this business.
     */
    public function customerScans()
    {
        return $this->hasManyThrough(
            AffiliateCustomerScan::class,
            AffiliateQrCode::class,
            'business_id',
            'qr_code_id'
        );
    }

    /**
     * Get the commissions for this business.
     */
    public function commissions()
    {
        return $this->hasMany(AffiliateCommission::class, 'business_id');
    }

    /**
     * Get the dashboard sessions for this business.
     */
    public function dashboardSessions()
    {
        return $this->hasMany(AffiliateDashboardSession::class);
    }

    /**
     * Check if the business is verified.
     */
    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }

    /**
     * Check if the business is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the business is a hotel chain.
     */
    public function isHotelChain(): bool
    {
        return $this->business_type === 'hotel_chain';
    }

    /**
     * Check if the dashboard token is still valid.
     */
    public function isDashboardTokenValid(): bool
    {
        return $this->dashboard_token_expires_at &&
               $this->dashboard_token_expires_at->isFuture();
    }

    /**
     * Generate a new dashboard access token.
     */
    public function generateDashboardToken(): string
    {
        $this->dashboard_access_token = 'AFF-' . strtoupper(uniqid()) . '-' . bin2hex(random_bytes(16));
        $this->dashboard_token_expires_at = now()->addDays(30);
        $this->save();

        return $this->dashboard_access_token;
    }

    /**
     * Refresh the dashboard token.
     */
    public function refreshDashboardToken(): string
    {
        return $this->generateDashboardToken();
    }

    /**
     * Get the business model with fallback to global settings.
     */
    public function getEffectiveBusinessModel(): array
    {
        $businessModel = $this->businessModel;
        $globalSettings = AffiliateGlobalSetting::first();

        return [
            'discount_type' => $businessModel->discount_type ?? $globalSettings->global_discount_type ?? 'percentage',
            'discount_value' => $businessModel->discount_value ?? $globalSettings->global_discount_value ?? 0,
            'min_booking_amount' => $businessModel->min_booking_amount ?? $globalSettings->global_min_booking_amount ?? 0,
            'max_discount_amount' => $businessModel->max_discount_amount ?? $globalSettings->global_max_discount_amount ?? 0,
            'commission_rate' => $businessModel->commission_rate ?? $globalSettings->global_commission_rate ?? 0,
            'commission_type' => $businessModel->commission_type ?? $globalSettings->global_commission_type ?? 'percentage',
            'payout_threshold' => $businessModel->payout_threshold ?? $globalSettings->global_payout_threshold ?? 100,
            'max_qr_codes_per_month' => $businessModel->max_qr_codes_per_month ?? $globalSettings->max_qr_codes_per_business ?? 100,
        ];
    }

    /**
     * Scope a query to include only active businesses.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to include only verified businesses.
     */
    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    /**
     * Scope a query to include businesses of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('business_type', $type);
    }

    /**
     * Scope a query to search businesses by name or email.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('contact_email', 'like', "%{$search}%");
        });
    }

    /**
     * Get the notification routing information for the business.
     */
    public function routeNotificationFor($notification)
    {
        return $this->contact_email;
    }
}