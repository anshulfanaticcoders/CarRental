<?php

namespace App\Models\Affiliate;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AffiliateCommission extends Model
{
    use HasFactory;

    protected $table = 'affiliate_commissions';

    protected $fillable = [
        'uuid',
        'business_id',
        'location_id',
        'booking_id',
        'customer_id',
        'qr_scan_id',
        'booking_amount',
        'commissionable_amount',
        'commission_rate',
        'commission_amount',
        'discount_amount',
        'tax_amount',
        'net_commission',
        'booking_type',
        'commission_type',
        'commission_tier',
        'status',
        'approved_by',
        'approved_at',
        'payout_id',
        'scheduled_payout_date',
        'paid_at',
        'payment_method',
        'transaction_reference',
        'dispute_reason',
        'dispute_resolved_at',
        'dispute_resolution',
        'audit_log',
        'compliance_checked',
        'fraud_review_required',
    ];

    protected $casts = [
        'booking_amount' => 'decimal:2',
        'commissionable_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'net_commission' => 'decimal:2',
        'commission_tier' => 'array',
        'approved_at' => 'datetime',
        'scheduled_payout_date' => 'date',
        'paid_at' => 'datetime',
        'dispute_resolved_at' => 'datetime',
        'audit_log' => 'array',
        'compliance_checked' => 'boolean',
        'fraud_review_required' => 'boolean',
    ];

    protected $dates = [
        'approved_at',
        'scheduled_payout_date',
        'paid_at',
        'dispute_resolved_at',
    ];

    /**
     * Get the business that earned this commission.
     */
    public function business()
    {
        return $this->belongsTo(AffiliateBusiness::class);
    }

    /**
     * Get the location that generated this commission.
     */
    public function location()
    {
        return $this->belongsTo(AffiliateBusinessLocation::class);
    }

    /**
     * Get the booking that generated this commission.
     */
    public function booking()
    {
        return $this->belongsTo(\App\Models\Booking::class);
    }

    /**
     * Get the customer who made the booking.
     */
    public function customer()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Get the customer scan that led to this commission.
     */
    public function customerScan()
    {
        return $this->belongsTo(AffiliateCustomerScan::class, 'qr_scan_id');
    }

    /**
     * Get the admin who approved this commission.
     */
    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Get the payout for this commission.
     */
    public function payout()
    {
        return $this->belongsTo(\App\Models\Affiliate\AffiliatePayout::class);
    }

    /**
     * Check if the commission is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the commission is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the commission is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if the commission is disputed.
     */
    public function isDisputed(): bool
    {
        return $this->status === 'disputed';
    }

    /**
     * Approve the commission.
     */
    public function approve($approvedBy = null): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    /**
     * Mark the commission as paid.
     */
    public function markAsPaid($paymentMethod = null, $transactionReference = null): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_method' => $paymentMethod,
            'transaction_reference' => $transactionReference,
        ]);
    }

    /**
     * Reject the commission.
     */
    public function reject($approvedBy = null, $reason = null): void
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        if ($reason) {
            $this->addAuditLog('rejected', [
                'reason' => $reason,
                'approved_by' => $approvedBy,
            ]);
        }
    }

    /**
     * Create a dispute for this commission.
     */
    public function createDispute($reason): void
    {
        $this->update([
            'status' => 'disputed',
            'dispute_reason' => $reason,
        ]);

        $this->addAuditLog('dispute_created', ['reason' => $reason]);
    }

    /**
     * Resolve a dispute.
     */
    public function resolveDispute($resolution, $approvedBy = null): void
    {
        $this->update([
            'status' => $approvedBy ? 'approved' : 'rejected',
            'dispute_resolved_at' => now(),
            'dispute_resolution' => $resolution,
        ]);

        if ($approvedBy) {
            $this->approved_at = now();
            $this->approved_by = $approvedBy;
        }

        $this->addAuditLog('dispute_resolved', [
            'resolution' => $resolution,
            'resolved_by' => $approvedBy,
        ]);

        $this->save();
    }

    /**
     * Add an entry to the audit log.
     */
    public function addAuditLog($action, $data = []): void
    {
        $auditLog = $this->audit_log ?? [];
        $auditLog[] = [
            'action' => $action,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ];

        $this->audit_log = $auditLog;
        $this->save();
    }

    /**
     * Get the commission as a formatted string.
     */
    public function getFormattedCommissionAttribute(): string
    {
        return $this->business->currency === 'EUR'
            ? "€{$this->commission_amount}"
            : "{$this->commission_amount} {$this->business->currency}";
    }

    /**
     * Get the commission rate as a formatted string.
     */
    public function getFormattedCommissionRateAttribute(): string
    {
        return $this->commission_type === 'percentage'
            ? "{$this->commission_rate}%"
            : $this->commission_type;
    }

    /**
     * Scope a query to include only pending commissions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to include only approved commissions.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to include only paid commissions.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope a query to include only disputed commissions.
     */
    public function scopeDisputed($query)
    {
        return $query->where('status', 'disputed');
    }

    /**
     * Scope a query to include commissions for a specific business.
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope a query to include commissions for a specific date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to include commissions that need fraud review.
     */
    public function scopeNeedsFraudReview($query)
    {
        return $query->where('fraud_review_required', true);
    }
}