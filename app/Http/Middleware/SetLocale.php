<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Set the locale from the session, or fall back to the app's default locale
        $locale = session('locale', app()->getLocale());
        App::setLocale($locale);

        // Share the locale and translations with Inertia
        Inertia::share([
            'locale' => $locale,
            'translations' => trans('messages') ?? [],
            'pageTranslations' => $this->getPageTranslations($request),
            'howItWorksTranslations' => $this->getHowItWorksTranslations($request),
        ]);

        return $next($request);
    }

    /**
     * Get page-specific translations based on the current route or path.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function getPageTranslations(Request $request)
    {
        $currentRoute = $request->route() ? $request->route()->getName() : null;
        $currentPath = $request->path();

        // Map routes/paths to translation files
        $pageTranslationMap = [
            '/' => 'homepage',
            // 'vehicles' => 'vehicles',
            // 'profile' => 'profile',
        ];

        // Determine which translation file to use based on current path
        $translationFile = 'messages'; // default
        foreach ($pageTranslationMap as $path => $file) {
            if ($currentPath === $path || $currentRoute === $path) {
                $translationFile = $file;
                break;
            }
        }

        return trans($translationFile) ?? [];
    }

    /**
     * Get how_it_works translations for the homepage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|null
     */
    protected function getHowItWorksTranslations(Request $request)
    {
        $currentRoute = $request->route() ? $request->route()->getName() : null;
        $currentPath = $request->path();

        if ($currentPath === '/' || $currentRoute === 'home') {
            return trans('how_it_works') ?? [];
        }

        return null;
    }
}