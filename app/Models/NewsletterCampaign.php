<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class NewsletterCampaign extends Model
{
    const STATUS_DRAFT = 'draft';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_SENDING = 'sending';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'subject',
        'content',
        'status',
        'scheduled_at',
        'sending_started_at',
        'sent_at',
        'total_recipients',
        'sent_count',
        'failed_count',
        'opened_count',
        'clicked_count',
        'unsubscribed_count',
        'created_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sending_started_at' => 'datetime',
        'sent_at' => 'datetime',
        'total_recipients' => 'integer',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
        'opened_count' => 'integer',
        'clicked_count' => 'integer',
        'unsubscribed_count' => 'integer',
    ];

    // Relationships

    public function logs(): HasMany
    {
        return $this->hasMany(NewsletterCampaignLog::class, 'campaign_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeSending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SENDING);
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SENT);
    }

    // Helpers

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SCHEDULED]);
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [self::STATUS_SCHEDULED, self::STATUS_SENDING]);
    }

    // Computed accessors

    public function getOpenRateAttribute(): float
    {
        if ($this->sent_count === 0) return 0;
        return round(($this->opened_count / $this->sent_count) * 100, 1);
    }

    public function getClickRateAttribute(): float
    {
        if ($this->sent_count === 0) return 0;
        return round(($this->clicked_count / $this->sent_count) * 100, 1);
    }
}
