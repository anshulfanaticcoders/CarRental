<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = session('locale', app()->getLocale());
        App::setLocale($locale);

        // Load page-specific translations
        $pageTranslations = $this->getPageTranslations($request);

        // Share the locale and page-specific translations with Inertia
        Inertia::share([
            'locale' => $locale,
            'pageTranslations' => $pageTranslations,
        ]);

        return $next($request);
    }

    protected function getPageTranslations(Request $request)
    {
        $currentRoute = $request->route() ? $request->route()->getName() : null;
        $currentPath = $request->path();

        $pageTranslationMap = [
            '/' => 'homepage',
        ];

        $translationFile = null; // Default to null for non-mapped routes
        foreach ($pageTranslationMap as $path => $file) {
            if ($currentPath === $path || $currentRoute === $path) {
                $translationFile = $file;
                break;
            }
        }

        if ($translationFile) {
            return $this->loadTranslationsSafely($translationFile);
        }

        return [];
    }

    protected function loadTranslationsSafely($file)
    {
        $translations = trans($file);
        if ($translations === null || !is_array($translations)) {
            \Log::warning("Translation file '$file' is missing or did not return an array for locale '" . app()->getLocale() . "'.");
            return [];
        }

        foreach ($translations as $key => $value) {
            if (!$this->isSerializable($value)) {
                \Log::warning("Non-serializable translation in file '$file' for key '$key':", ['value' => $value]);
                $translations[$key] = null;
            }
        }

        return $translations;
    }

    protected function isSerializable($value)
    {
        try {
            json_encode($value, JSON_THROW_ON_ERROR);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}