<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LOCALES = ['en', 'fr', 'nl', 'es', 'ar'];

    public function up(): void
    {
        $this->repairAboutUsTranslations();
        $this->seedLegacyRedirects();
        $this->forgetLocalizedSeoCaches();

        Cache::forget('seo_redirects_map');
    }

    public function down(): void
    {
        // Intentionally not reverting content repairs; doing so would reintroduce known bad SEO data.
    }

    private function repairAboutUsTranslations(): void
    {
        $aboutUsTranslation = DB::table('page_translations')
            ->where('locale', 'en')
            ->where('slug', 'about-us')
            ->first();

        if (! $aboutUsTranslation) {
            return;
        }

        $frRow = DB::table('page_translations')
            ->where('page_id', $aboutUsTranslation->page_id)
            ->where('locale', 'fr')
            ->first();

        $nlRow = DB::table('page_translations')
            ->where('page_id', $aboutUsTranslation->page_id)
            ->where('locale', 'nl')
            ->first();

        if (! $frRow || ! $nlRow) {
            return;
        }

        if ($frRow->slug !== 'over-ons' || $nlRow->slug !== 'a-propos-de-nous') {
            return;
        }

        DB::table('page_translations')->where('id', $frRow->id)->update([
            'title' => $nlRow->title,
            'slug' => $nlRow->slug,
            'content' => $nlRow->content,
            'updated_at' => now(),
        ]);

        DB::table('page_translations')->where('id', $nlRow->id)->update([
            'title' => $frRow->title,
            'slug' => $frRow->slug,
            'content' => $frRow->content,
            'updated_at' => now(),
        ]);
    }

    private function seedLegacyRedirects(): void
    {
        if (! Schema::hasTable('seo_redirects')) {
            return;
        }

        foreach (self::LOCALES as $locale) {
            $this->addRedirect(
                "/{$locale}/business/register",
                "/{$locale}/affiliate/register",
                'Localized SEO routing fix: business registration moved to affiliate registration'
            );
        }

        $this->seedCustomPageRedirects();
        $this->seedTermsPageRedirects();
        $this->seedAboutSwapRedirects();
    }

    private function seedCustomPageRedirects(): void
    {
        $pages = DB::table('pages')
            ->select('id', 'custom_slug')
            ->whereNotNull('custom_slug')
            ->where('custom_slug', '<>', '')
            ->where('status', 'published')
            ->get();

        foreach ($pages as $page) {
            $translations = DB::table('page_translations')
                ->where('page_id', $page->id)
                ->pluck('slug', 'locale');

            foreach (self::LOCALES as $locale) {
                $slug = (string) ($translations->get($locale) ?? '');
                if ($slug === '') {
                    continue;
                }

                $this->addRedirect(
                    "/{$locale}/{$page->custom_slug}",
                    "/{$locale}/page/{$slug}",
                    'Localized SEO routing fix: custom page alias canonicalized'
                );
            }
        }
    }

    private function seedTermsPageRedirects(): void
    {
        $termsPageId = $this->pageIdForCustomSlug('terms-and-conditions')
            ?? $this->pageIdForTranslation('en', 'terms-conditions')
            ?? $this->pageIdForTranslation('en', 'terms-and-conditions');

        if (! $termsPageId) {
            return;
        }

        $translations = DB::table('page_translations')
            ->where('page_id', $termsPageId)
            ->pluck('slug', 'locale');

        foreach (self::LOCALES as $locale) {
            $slug = (string) ($translations->get($locale) ?? '');
            if ($slug === '') {
                continue;
            }

            $this->addRedirect(
                "/{$locale}/page/terms-and-conditions",
                "/{$locale}/page/{$slug}",
                'Localized SEO routing fix: legacy Terms English slug canonicalized'
            );
        }
    }

    private function seedAboutSwapRedirects(): void
    {
        $aboutPageId = $this->pageIdForTranslation('en', 'about-us');

        if (! $aboutPageId) {
            return;
        }

        $translations = DB::table('page_translations')
            ->where('page_id', $aboutPageId)
            ->pluck('slug', 'locale');

        $frSlug = (string) ($translations->get('fr') ?? '');
        if ($frSlug !== '') {
            $this->addRedirect(
                '/fr/page/over-ons',
                '/fr/page/'.$frSlug,
                'Localized SEO routing fix: historical FR/NL About slug swap'
            );
        }

        $nlSlug = (string) ($translations->get('nl') ?? '');
        if ($nlSlug !== '') {
            $this->addRedirect(
                '/nl/page/a-propos-de-nous',
                '/nl/page/'.$nlSlug,
                'Localized SEO routing fix: historical FR/NL About slug swap'
            );
        }
    }

    private function pageIdForCustomSlug(string $customSlug): ?int
    {
        $page = DB::table('pages')
            ->where('custom_slug', $customSlug)
            ->where('status', 'published')
            ->first();

        return $page ? (int) $page->id : null;
    }

    private function pageIdForTranslation(string $locale, string $slug): ?int
    {
        $translation = DB::table('page_translations')
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->first();

        return $translation ? (int) $translation->page_id : null;
    }

    private function addRedirect(string $from, string $to, string $note): void
    {
        DB::table('seo_redirects')->updateOrInsert(
            ['from_url' => $from],
            [
                'to_url' => $to,
                'status_code' => 301,
                'note' => $note,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function forgetLocalizedSeoCaches(): void
    {
        foreach ([
            'hreflang:page:fr:over-ons',
            'hreflang:page:fr:a-propos-de-nous',
            'hreflang:page:nl:over-ons',
            'hreflang:page:nl:a-propos-de-nous',
            'hreflang:page:es:terms-and-conditions',
            'hreflang:page:es:terminos-y-condiciones',
        ] as $key) {
            Cache::forget($key);
        }
    }
};
