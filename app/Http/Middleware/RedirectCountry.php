<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Stevebauman\Location\Facades\Location;

class RedirectCountry
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

    public function handle($request, Closure $next)
    {
        $path = $request->path();

        // Get the real user IP address
        $userIp = $this->getUserRealIp($request);

        \Log::info('RedirectCountry middleware hit: ' . $path . ', IP: ' . $userIp);

        try {
            $position = Location::get($userIp);

            if ($position && $position->countryCode) {
                $detectedCountry = strtolower($position->countryCode);
                \Log::info('✅ SUCCESS: Location detected - IP: ' . $userIp . ', Country: ' . $detectedCountry);
            } else {
                \Log::error('❌ FAILED: Stevebauman returned false or no country code for IP: ' . $userIp);
                $detectedCountry = null;
            }
        } catch (\Exception $e) {
            \Log::error('❌ ERROR: Location detection failed: ' . $e->getMessage());
            $detectedCountry = null;
        }

        \Log::info('Detected country: ' . $detectedCountry);

        // Store detected country, or default to US
        if ($detectedCountry) {
            session(['country' => $detectedCountry]);
            \Log::info('✅ Country stored in session: ' . $detectedCountry);
        } else {
            // Default to US for local development when detection fails
            session(['country' => 'us']);
            \Log::info('🇺🇸 DEFAULT: Setting US as default country (detection failed)');
        }

        // Agar path me locale + country nahi hai aur country available hai
        $countryForRedirect = $detectedCountry ?: session('country');
        if (!preg_match('#^[a-z]{2}/[a-z]{2}/blog#i', $path) && $countryForRedirect) {

            $segments = explode('/', $path);
            $locale = $segments[0] ?? 'en';

            // Single blog check
            if (preg_match('#blog/([^/]+)#', $path, $matches)) {
                $slug = $matches[1];
                return redirect("/{$locale}/{$countryForRedirect}/blog/{$slug}");
            }

            // Blog index
            return redirect("/{$locale}/{$countryForRedirect}/blog");
        }

        return $next($request);
    }

    /**
     * Get the real user IP address
     */
    private function getUserRealIp($request)
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

}
