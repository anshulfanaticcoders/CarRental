<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SeoRedirect extends Model
{
    protected $fillable = ['from_url', 'to_url', 'status_code', 'note'];

    protected $casts = [
        'last_hit_at' => 'datetime',
    ];

    const CACHE_KEY = 'seo_redirects_map';
    const CACHE_TTL = 3600; // 1 hour

    /**
     * Get the cached redirect map: [from_url => [to_url, status_code]]
     */
    public static function getCachedMap(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::query()
                ->select('id', 'from_url', 'to_url', 'status_code')
                ->get()
                ->keyBy('from_url')
                ->map(fn ($r) => ['id' => $r->id, 'to_url' => $r->to_url, 'status_code' => $r->status_code])
                ->all();
        });
    }

    /**
     * Clear the redirect cache (call after create/update/delete).
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Record a hit on this redirect.
     */
    public function recordHit(): void
    {
        $this->increment('hits');
        $this->update(['last_hit_at' => now()]);
    }

    /**
     * Add a 301 redirect.
     */
    public static function addRedirect(string $from, string $to, string $note = ''): self
    {
        $redirect = self::updateOrCreate(
            ['from_url' => $from],
            ['to_url' => $to, 'status_code' => 301, 'note' => $note]
        );
        self::clearCache();
        return $redirect;
    }

    /**
     * Add a 410 Gone entry.
     */
    public static function addGone(string $from, string $note = ''): self
    {
        $redirect = self::updateOrCreate(
            ['from_url' => $from],
            ['to_url' => null, 'status_code' => 410, 'note' => $note]
        );
        self::clearCache();
        return $redirect;
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::clearCache());
        static::deleted(fn () => self::clearCache());
    }
}
