<?php

namespace App\Http\Middleware;

use Closure;

class RedirectCountry
{
    public function handle($request, Closure $next)
    {
        $path = $request->path();

        // Already has /{locale}/{country}/blog pattern - pass through
        if (preg_match('#^[a-z]{2}/[a-z]{2}/blog#i', $path)) {
            return $next($request);
        }

        // Get country from session (set by SetCurrency middleware earlier in pipeline)
        $country = session('country', 'us');

        $segments = explode('/', $path);
        $locale = $segments[0] ?? 'en';

        // Single blog: /en/blog/slug -> /en/us/blog/slug
        if (preg_match('#blog/([^/]+)#', $path, $matches)) {
            return redirect("/{$locale}/{$country}/blog/{$matches[1]}");
        }

        // Blog index: /en/blog -> /en/us/blog
        return redirect("/{$locale}/{$country}/blog");
    }
}
