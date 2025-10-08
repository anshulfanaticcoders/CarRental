<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShareCountryFromUrl
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        // Get country from URL parameter first (highest priority)
        $country = $request->route('country');

        // If country is in URL, store it and use it
        if ($country) {
            session(['country' => strtolower($country)]);
            \Log::info('Country from URL stored in session: ' . strtolower($country));
        } else {
            // Always try to detect from IP using Stevebauman Location
            \Log::info('No country in URL, attempting dynamic IP detection with Stevebauman...');

            try {
                $position = \Stevebauman\Location\Facades\Location::get();

                if ($position && $position->countryCode) {
                    $detectedCountry = strtolower($position->countryCode);
                    session(['country' => $detectedCountry]);
                    $country = $detectedCountry;
                    \Log::info('✅ SUCCESS: Stevebauman detected country: ' . $detectedCountry . ' from IP: ' . $position->ip);
                } else {
                    \Log::error('❌ FAILED: Stevebauman returned false or no country code');

                    // Default to US if no country detected and no session country
                    if (!session('country')) {
                        $country = 'us';
                        session(['country' => $country]);
                        \Log::info('🇺🇸 DEFAULT: Setting US as default country');
                    } else {
                        $country = session('country');
                        \Log::info('Using existing session country: ' . $country);
                    }
                }
            } catch (\Exception $e) {
                \Log::error('❌ ERROR: Stevebauman Location failed: ' . $e->getMessage());

                // Default to US if no country detected and no session country
                if (!session('country')) {
                    $country = 'us';
                    session(['country' => $country]);
                    \Log::info('🇺🇸 DEFAULT: Setting US as default country (exception case)');
                } else {
                    $country = session('country');
                    \Log::info('Using existing session country: ' . $country);
                }
            }
        }

        // Final fallback - if we still don't have a country, default to US
        if (!$country) {
            $country = 'us';
            session(['country' => $country]);
            \Log::info('🇺🇸 FINAL FALLBACK: Setting US as final default country');
        }

        // Ensure country is lowercase and stored in session
        if ($country) {
            $country = strtolower($country);
            session(['country' => $country]);
        }

        // Share country with all views
        view()->share('country', $country);
        inertia()->share('country', $country);
        \Log::info('Final country result: ' . ($country ?: 'NULL') . ' | Session country: ' . session('country'));

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