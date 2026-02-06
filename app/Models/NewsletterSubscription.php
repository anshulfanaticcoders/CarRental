<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSubscription extends Model
{
    protected $fillable = [
        'email',
        'status',
        'confirmed_at',
        'unsubscribed_at',
        'source',
        'locale',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    public static function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }
}
