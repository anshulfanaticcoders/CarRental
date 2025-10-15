<?php

namespace App\Models\Affiliate;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AffiliateBusinessLocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'affiliate_business_locations';

    protected $fillable = [
        'uuid',
        'business_id',
        'location_code',
        'name',
        'description',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'country',
        'postal_code',
        'latitude',
        'longitude',
        'location_accuracy_radius',
        'location_email',
        'location_phone',
        'manager_name',
        'timezone',
        'operating_hours',
        'qr_code_url',
        'qr_code_image_path',
        'qr_code_generated_at',
        'verification_status',
        'is_active',
        'verified_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'location_accuracy_radius' => 'integer',
        'operating_hours' => 'array',
        'qr_code_generated_at' => 'datetime',
        'is_active' => 'boolean',
        'verified_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $dates = [
        'qr_code_generated_at',
        'verified_at',
        'deleted_at',
    ];

    /**
     * Get the business that owns this location.
     */
    public function business()
    {
        return $this->belongsTo(AffiliateBusiness::class, 'business_id', 'id');
    }

    /**
     * Get the QR codes for this location.
     */
    public function qrCodes()
    {
        return $this->hasMany(AffiliateQrCode::class);
    }

    /**
     * Get customer scans that matched this location.
     */
    public function matchedScans()
    {
        return $this->hasMany(AffiliateCustomerScan::class, 'matched_location_id');
    }

    /**
     * Get commissions for this location.
     */
    public function commissions()
    {
        return $this->hasMany(AffiliateCommission::class);
    }

    /**
     * Get the full address.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->state,
            $this->country,
            $this->postal_code,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Calculate distance to another location in kilometers.
     */
    public function distanceTo(AffiliateBusinessLocation $other): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo = deg2rad($other->latitude);
        $lonTo = deg2rad($other->longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Find locations within a given radius.
     */
    public static function findWithinRadius($latitude, $longitude, $radiusKm = 1.0)
    {
        $locations = self::active()->get();
        $nearbyLocations = [];

        foreach ($locations as $location) {
            $distance = self::calculateDistance(
                $latitude, $longitude,
                $location->latitude, $location->longitude
            );

            if ($distance <= $radiusKm) {
                $location->distance = $distance;
                $nearbyLocations[] = $location;
            }
        }

        return collect($nearbyLocations)->sortBy('distance');
    }

    /**
     * Calculate distance between two points.
     */
    private static function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Scope a query to include only active locations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to include only verified locations.
     */
    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    /**
     * Scope a query to include locations for a specific business.
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope a query to search locations by name or address.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('city', 'like', "%{$search}%")
              ->orWhere('country', 'like', "%{$search}%");
        });
    }
}