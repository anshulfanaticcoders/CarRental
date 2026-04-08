<?php

namespace App\Http\Middleware;

use App\Models\SeoRedirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleSeoRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only handle GET requests (redirects don't apply to POST/PUT/DELETE)
        if (! $request->isMethod('GET')) {
            return $next($request);
        }

        $path = '/' . ltrim($request->path(), '/');

        // Permanently removed URL patterns — return 410 Gone
        // ~1,000 old vehicle pages like /{locale}/vehicle/{id} indexed in Google
        if (preg_match('#^/([a-z]{2}/)?vehicle/\d+#', $path)) {
            abort(410, 'This page has been permanently removed.');
        }

        $map = SeoRedirect::getCachedMap();

        if (! isset($map[$path])) {
            return $next($request);
        }

        $entry = $map[$path];

        // Record hit asynchronously (fire and forget)
        SeoRedirect::where('id', $entry['id'])->increment('hits');
        SeoRedirect::where('id', $entry['id'])->update(['last_hit_at' => now()]);

        if ($entry['status_code'] === 410) {
            abort(410, 'This page has been permanently removed.');
        }

        return redirect($entry['to_url'], $entry['status_code']);
    }
}
