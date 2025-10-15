<?php

namespace App\Models\Affiliate;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AffiliateDashboardSession extends Model
{
    use HasFactory;

    protected $table = 'affiliate_dashboard_sessions';

    protected $fillable = [
        'uuid',
        'business_id',
        'session_token',
        'ip_address',
        'user_agent',
        'device_type',
        'expires_at',
        'last_accessed_at',
        'is_active',
        'revoked_at',
        'revoke_reason',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'is_active' => 'boolean',
        'revoked_at' => 'datetime',
    ];

    protected $dates = [
        'expires_at',
        'last_accessed_at',
        'revoked_at',
    ];

    /**
     * Get the business that owns this session.
     */
    public function business()
    {
        return $this->belongsTo(AffiliateBusiness::class);
    }

    /**
     * Check if the session is still valid.
     */
    public function isValid(): bool
    {
        return $this->is_active &&
               $this->expires_at &&
               $this->expires_at->isFuture() &&
               !$this->revoked_at;
    }

    /**
     * Check if the session has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the session has been revoked.
     */
    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    /**
     * Revoke the session.
     */
    public function revoke($reason = null): void
    {
        $this->update([
            'is_active' => false,
            'revoked_at' => now(),
            'revoke_reason' => $reason,
        ]);
    }

    /**
     * Extend the session expiration.
     */
    public function extend($days = 30): void
    {
        $this->update([
            'expires_at' => now()->addDays($days),
            'is_active' => true,
        ]);
    }

    /**
     * Refresh the last accessed timestamp.
     */
    public function touchLastAccessed(): void
    {
        $this->update(['last_accessed_at' => now()]);
    }

    /**
     * Get the session information as a formatted string.
     */
    public function getSessionInfoAttribute(): string
    {
        $info = "Device: {$this->device_type}";

        if ($this->ip_address) {
            $info .= " | IP: {$this->ip_address}";
        }

        if ($this->expires_at) {
            $info .= " | Expires: {$this->expires_at->format('Y-m-d H:i')}";
        }

        return $info;
    }

    /**
     * Clean up expired sessions.
     */
    public static function cleanupExpired(): int
    {
        return static::where('expires_at', '<', now())
                    ->orWhere(function ($query) {
                        $query->where('is_active', false)
                              ->where('revoked_at', '<', now()->subDays(30));
                    })
                    ->delete();
    }

    /**
     * Get active sessions for a business.
     */
    public static function getActiveForBusiness($businessId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('business_id', $businessId)
                    ->where('is_active', true)
                    ->where('expires_at', '>', now())
                    ->orderBy('last_accessed_at', 'desc')
                    ->get();
    }

    /**
     * Revoke all active sessions for a business.
     */
    public static function revokeAllForBusiness($businessId, $reason = null): int
    {
        return static::where('business_id', $businessId)
                    ->where('is_active', true)
                    ->update([
                        'is_active' => false,
                        'revoked_at' => now(),
                        'revoke_reason' => $reason ?? 'Business requested revocation',
                    ]);
    }

    /**
     * Find a session by token.
     */
    public static function findByToken($token): ?static
    {
        return static::where('session_token', $token)
                    ->where('is_active', true)
                    ->where('expires_at', '>', now())
                    ->first();
    }

    /**
     * Create a new session.
     */
    public static function createForBusiness(AffiliateBusiness $business, $request): static
    {
        // Revoke existing sessions for this business
        static::revokeAllForBusiness($business->id, 'New session created');

        return static::create([
            'business_id' => $business->id,
            'session_token' => 'SESSION-' . strtoupper(uniqid()) . '-' . bin2hex(random_bytes(16)),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_type' => static::detectDeviceType($request),
            'expires_at' => now()->addDays(30),
        ]);
    }

    /**
     * Detect device type from request.
     */
    private static function detectDeviceType($request): string
    {
        $userAgent = $request->userAgent();

        if (preg_match('/Mobile|Android|iPhone|iPad|iPod/', $userAgent)) {
            if (preg_match('/iPad/', $userAgent)) {
                return 'tablet';
            }
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Scope a query to include only active sessions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope a query to include only expired sessions.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope a query to include only revoked sessions.
     */
    public function scopeRevoked($query)
    {
        return $query->whereNotNull('revoked_at');
    }

    /**
     * Scope a query to include sessions for a specific business.
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }
}