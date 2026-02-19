<?php

namespace App\Services\Sitemaps;

use RuntimeException;

class SitemapUrlPolicy
{
    public function assertAllowed(string $url): void
    {
        if (! $this->isAllowed($url)) {
            throw new RuntimeException("Blocked URL detected in sitemap generation: {$url}");
        }
    }

    public function isAllowed(string $url): bool
    {
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '';
        $query = $parsed['query'] ?? '';

        if ($query !== '') {
            return false;
        }

        if (preg_match('#^/[a-z]{2}/s$#', $path) || $path === '/s') {
            return false;
        }

        if (preg_match('#/(booking|bookings)(/|$)#', $path)) {
            return false;
        }

        if (preg_match('#/(vehicle|wheelsys-car|adobe-car|renteon-car|locauto-rent-car)(/|$)#', $path)) {
            return false;
        }

        if (
            str_contains($path, 'ok-mobility-booking')
            || str_contains($path, 'green-motion-booking')
            || str_contains($path, 'adobe-booking')
            || str_contains($path, 'locauto-rent-booking')
            || str_contains($path, 'wheelsys-booking')
        ) {
            return false;
        }

        if (preg_match('#/(admin|vendor|profile|messages|dashboard|auth)(/|$)#', $path)) {
            return false;
        }

        if (preg_match('#/affiliate/#', $path)) {
            return false;
        }

        return true;
    }
}
