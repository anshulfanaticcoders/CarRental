<?php

namespace App\Observers;

use App\Models\PageTranslation;
use App\Models\SeoRedirect;

class PageTranslationObserver
{
    /**
     * When a page translation slug changes, create a 301 redirect
     * from old slug to new slug.
     */
    public function updating(PageTranslation $translation): void
    {
        $originalSlug = $translation->getOriginal('slug');
        $newSlug = $translation->slug;

        if (! $originalSlug || $originalSlug === $newSlug) {
            return;
        }

        $locale = $translation->locale;
        $oldPath = "/{$locale}/page/{$originalSlug}";
        $newPath = "/{$locale}/page/{$newSlug}";

        SeoRedirect::addRedirect($oldPath, $newPath, "Page #{$translation->page_id} slug changed");
    }
}
