<?php

namespace App\Observers;

use App\Models\Page;
use App\Models\SeoRedirect;
use Illuminate\Support\Facades\Artisan;

class PageObserver
{
    /**
     * Create 410 entries before translations are cascade-deleted.
     */
    public function deleting(Page $page): void
    {
        $page->loadMissing('translations');

        foreach ($page->translations as $translation) {
            if (empty($translation->slug)) {
                continue;
            }

            SeoRedirect::addGone(
                "/{$translation->locale}/page/{$translation->slug}",
                "Page #{$page->id} deleted"
            );
        }
    }

    public function saved(Page $page): void
    {
        $this->regenerateSitemap();
    }

    public function deleted(Page $page): void
    {
        $this->regenerateSitemap();
    }

    private function regenerateSitemap(): void
    {
        try {
            Artisan::call('sitemap:generate');
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
