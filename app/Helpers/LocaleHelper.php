<?php

namespace App\Helpers;

use App\Models\SeoMetaTranslation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

class LocaleHelper
{
    /**
     * Generates alternate URLs for different locales for the current route.
     * This is used for generating hreflang tags for SEO.
     *
     * @param string $currentRoute The name of the current route.
     * @param array $routeParameters The parameters of the current route.
     * @return array An array of URLs keyed by locale.
     */
    public static function getAlternateUrls($currentRoute, $routeParameters)
    {
        $urls = [];
        $locales = ['en', 'fr', 'nl']; // Available locales
        $translatedSlugs = [];
        $slugKey = null;
        $originalSlug = null;

        // Find the slug parameter, assuming it's the first parameter besides 'locale'
        if (!empty($routeParameters)) {
            $slugKey = key($routeParameters);
            $slugValue = reset($routeParameters);

            if ($slugValue instanceof Model) {
                // If it's a model instance from route model binding
                $originalSlug = $slugValue->getRouteKey();
            } else {
                // If it's a plain slug string
                $originalSlug = $slugValue;
            }
        }

        if ($originalSlug) {
            // Find the SeoMeta record based on the current slug and locale
            $currentLocale = app()->getLocale();
            $seoMetaTranslation = SeoMetaTranslation::where('url_slug', $originalSlug)
                                                    ->where('locale', $currentLocale)
                                                    ->first();

            // If we found the SEO meta, get all its translations
            if ($seoMetaTranslation && $seoMetaTranslation->seoMeta) {
                $allTranslations = $seoMetaTranslation->seoMeta->translations;
                foreach ($allTranslations as $translation) {
                    if (!empty($translation->url_slug)) {
                        $translatedSlugs[$translation->locale] = $translation->url_slug;
                    }
                }
            }
        }

        foreach ($locales as $locale) {
            $params = $routeParameters;
            $params['locale'] = $locale;

            // If a translated slug exists for this locale, use it
            if ($slugKey && isset($translatedSlugs[$locale])) {
                $params[$slugKey] = $translatedSlugs[$locale];
            } elseif ($slugKey) {
                // Fallback to original slug if no translation found for this locale
                 $params[$slugKey] = $originalSlug;
            }


            try {
                // Generate the URL for the given locale
                $urls[$locale] = route($currentRoute, $params);
            } catch (\Exception $e) {
                // If a URL cannot be generated, it will be omitted.
                // You might want to log the exception for debugging.
                // \Log::error("Could not generate alternate URL for locale '{$locale}': " . $e->getMessage());
            }
        }

        return $urls;
    }
}
