<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class ApiConsumer extends Model
{
    use Notifiable;

    protected $fillable = [
        'name',
        'contact_name',
        'contact_email',
        'contact_phone',
        'company_url',
        'status',
        'plan',
        'rate_limit',
        'notes',
    ];

    protected $casts = [
        'rate_limit' => 'integer',
    ];

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function activeKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class)->where('status', 'active');
    }

    public function apiLogs(): HasMany
    {
        return $this->hasMany(ApiLog::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function routeNotificationForMail(): string
    {
        return $this->contact_email;
    }
}
