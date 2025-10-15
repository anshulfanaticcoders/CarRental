<?php

namespace App\Models\Affiliate;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AffiliateCustomerScan extends Model
{
    use HasFactory;

    protected $table = 'affiliate_customer_scans';

    protected $fillable = [
        'uuid',
        'qr_code_id',
        'customer_id',
        'session_id',
        'scan_token',
        'tracking_url',
        'device_id',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'location_detection_method',
        'detected_latitude',
        'detected_longitude',
        'detected_accuracy',
        'ip_geolocation',
        'matched_location_id',
        'location_match_distance',
        'scan_date',
        'scan_hour',
        'user_timezone',
        'scan_result',
        'discount_applied',
        'discount_percentage',
        'booking_initiated',
        'booking_completed',
        'booking_id',
        'booking_type',
        'conversion_time_minutes',
        'fraud_score',
        'is_suspicious',
        'fraud_flags',
        'processing_time_ms',
        'server_region',
    ];

    protected $casts = [
        'detected_latitude' => 'decimal:8',
        'detected_longitude' => 'decimal:8',
        'detected_accuracy' => 'integer',
        'ip_geolocation' => 'array',
        'location_match_distance' => 'decimal:3',
        'scan_date' => 'date',
        'discount_applied' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'booking_initiated' => 'boolean',
        'booking_completed' => 'boolean',
        'conversion_time_minutes' => 'integer',
        'fraud_score' => 'integer',
        'is_suspicious' => 'boolean',
        'fraud_flags' => 'array',
        'processing_time_ms' => 'integer',
    ];

    protected $dates = [
        'scan_date',
    ];

    /**
     * Get the QR code that was scanned.
     */
    public function qrCode()
    {
        return $this->belongsTo(AffiliateQrCode::class);
    }

    /**
     * Get the customer who scanned the QR code.
     */
    public function customer()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Get the matched location (if any).
     */
    public function matchedLocation()
    {
        return $this->belongsTo(AffiliateBusinessLocation::class, 'matched_location_id');
    }

    /**
     * Get the booking associated with this scan.
     */
    public function booking()
    {
        return $this->belongsTo(\App\Models\Booking::class);
    }

    /**
     * Get the commission generated from this scan.
     */
    public function commission()
    {
        return $this->hasOne(AffiliateCommission::class, 'qr_scan_id');
    }

    /**
     * Get the business associated with this scan.
     */
    public function business()
    {
        return $this->hasOneThrough(
            AffiliateBusiness::class,
            AffiliateQrCode::class,
            'qr_code_id',
            'business_id'
        );
    }

    /**
     * Check if this scan resulted in a booking.
     */
    public function hasBooking(): bool
    {
        return $this->booking_completed && $this->booking_id !== null;
    }

    /**
     * Check if this scan is suspicious.
     */
    public function isSuspicious(): bool
    {
        return $this->is_suspicious || $this->fraud_score > 70;
    }

    /**
     * Check if this scan has a valid location match.
     */
    public function hasLocationMatch(): bool
    {
        return $this->matched_location_id !== null;
    }

    /**
     * Get the device information as a formatted string.
     */
    public function getDeviceInfoAttribute(): string
    {
        $parts = [];

        if ($this->browser) {
            $parts[] = $this->browser;
        }

        if ($this->platform) {
            $parts[] = $this->platform;
        }

        if ($this->device_type && $this->device_type !== 'unknown') {
            $parts[] = ucfirst($this->device_type);
        }

        return implode(' - ', $parts);
    }

    /**
     * Mark this scan as resulting in a booking.
     */
    public function markAsBookingCompleted($bookingId, $bookingType = 'platform'): void
    {
        $this->update([
            'booking_initiated' => true,
            'booking_completed' => true,
            'booking_id' => $bookingId,
            'booking_type' => $bookingType,
            'conversion_time_minutes' => $this->created_at->diffInMinutes(now()),
        ]);
    }

    /**
     * Get the fraud risk level.
     */
    public function getFraudRiskLevel(): string
    {
        if ($this->fraud_score >= 80) {
            return 'High';
        } elseif ($this->fraud_score >= 50) {
            return 'Medium';
        } elseif ($this->fraud_score >= 20) {
            return 'Low';
        }

        return 'Very Low';
    }

    /**
     * Scope a query to include only successful scans.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('scan_result', 'success');
    }

    /**
     * Scope a query to include only scans that resulted in bookings.
     */
    public function scopeWithBookings($query)
    {
        return $query->where('booking_completed', true)
                    ->whereNotNull('booking_id');
    }

    /**
     * Scope a query to include only suspicious scans.
     */
    public function scopeSuspicious($query)
    {
        return $query->where('is_suspicious', true)
                    ->orWhere('fraud_score', '>', 50);
    }

    /**
     * Scope a query to include scans for a specific business.
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->whereHas('qrCode', function ($q) use ($businessId) {
            $q->where('business_id', $businessId);
        });
    }

    /**
     * Scope a query to include scans for a specific date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('scan_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to include scans with a specific fraud score or higher.
     */
    public function scopeWithFraudScore($query, $minScore = 0)
    {
        return $query->where('fraud_score', '>=', $minScore);
    }
}