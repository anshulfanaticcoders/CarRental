<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoLocationService
{
    /**
     * Get user's real IP address
     */
    public static function getUserRealIp($request)
    {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',    // Cloudflare
            'HTTP_X_FORWARDED_FOR',     // Squid, Nginx, Apache
            'HTTP_X_REAL_IP',          // Nginx
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'              // Fallback
        ];

        foreach ($ipKeys as $key) {
            if ($request->server($key) && !empty($request->server($key))) {
                $ips = explode(',', $request->server($key));
                $ip = trim($ips[0]);

                // Validate IP address
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $request->ip();
    }

    /**
     * Detect country from multiple IP geolocation services
     */
    public static function detectCountry($request)
    {
        $userIp = self::getUserRealIp($request);

        Log::info('GeoLocationService: Detecting country for IP: ' . $userIp);

        // Try multiple services in order
        $country = self::tryIpApi($userIp);
        if ($country) return $country;

        $country = self::tryIpInfo($userIp);
        if ($country) return $country;

        $country = self::tryGeoJs($userIp);
        if ($country) return $country;

        Log::error('GeoLocationService: All services failed for IP: ' . $userIp);
        return null;
    }

    /**
     * Try IP-API.com (free, reliable)
     */
    private static function tryIpApi($ip)
    {
        try {
            $response = Http::timeout(5)->get("http://ip-api.com/json/{$ip}");

            if ($response->successful()) {
                $data = $response->json();
                $countryCode = strtolower($data['countryCode'] ?? null);

                if ($countryCode) {
                    Log::info('GeoLocationService: IP-API detected country: ' . $countryCode . ' for IP: ' . $ip);
                    return $countryCode;
                }
            }
        } catch (\Exception $e) {
            Log::error('GeoLocationService: IP-API failed for IP ' . $ip . ': ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Try ipinfo.io (free tier)
     */
    private static function tryIpInfo($ip)
    {
        try {
            $response = Http::timeout(5)->get("http://ipinfo.io/{$ip}/json");

            if ($response->successful()) {
                $data = $response->json();
                $countryCode = strtolower($data['country'] ?? null);

                if ($countryCode) {
                    Log::info('GeoLocationService: ipinfo.io detected country: ' . $countryCode . ' for IP: ' . $ip);
                    return $countryCode;
                }
            }
        } catch (\Exception $e) {
            Log::error('GeoLocationService: ipinfo.io failed for IP ' . $ip . ': ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Try geojs.io (free)
     */
    private static function tryGeoJs($ip)
    {
        try {
            $response = Http::timeout(5)->get("https://geojs.io/{$ip}.json");

            if ($response->successful()) {
                $data = $response->json();
                $countryCode = strtolower($data['country'] ?? null);

                if ($countryCode) {
                    Log::info('GeoLocationService: geojs.io detected country: ' . $countryCode . ' for IP: ' . $ip);
                    return $countryCode;
                }
            }
        } catch (\Exception $e) {
            Log::error('GeoLocationService: geojs.io failed for IP ' . $ip . ': ' . $e->getMessage());
        }

        return null;
    }
}