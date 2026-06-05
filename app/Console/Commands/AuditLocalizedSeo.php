<?php

namespace App\Console\Commands;

use App\Models\BlogTranslation;
use App\Models\PageTranslation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class AuditLocalizedSeo extends Command
{
    protected $signature = 'seo:audit-localized-routes';

    protected $description = 'Audit localized public SEO routes and suspicious translated slugs.';

    public function handle(): int
    {
        $errors = 0;
        $warnings = 0;

        if (! Route::has('affiliate.register')) {
            $this->error('Missing route: affiliate.register');
            $errors++;
        }

        $about = PageTranslation::query()
            ->where('locale', 'en')
            ->where('slug', 'about-us')
            ->first();

        if ($about) {
            $fr = PageTranslation::query()->where('page_id', $about->page_id)->where('locale', 'fr')->first();
            $nl = PageTranslation::query()->where('page_id', $about->page_id)->where('locale', 'nl')->first();

            if ($fr?->slug === 'over-ons' && $nl?->slug === 'a-propos-de-nous') {
                $this->error('About Us FR/NL slugs are still swapped: fr=over-ons, nl=a-propos-de-nous');
                $errors++;
            }
        }

        foreach ($this->suspiciousBlogSlugFindings() as $finding) {
            $this->warn($finding);
            $warnings++;
        }

        if ($errors > 0) {
            $this->error("Localized SEO audit failed with {$errors} error(s) and {$warnings} warning(s).");

            return self::FAILURE;
        }

        $this->info("Localized SEO audit passed with {$warnings} warning(s).");

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function suspiciousBlogSlugFindings(): array
    {
        $patterns = [
            'es' => ['auto-huren', 'autoverhuur', 'voiture', 'location-de'],
            'fr' => ['auto-huren', 'autoverhuur', 'alquiler', 'coches'],
            'nl' => ['location-de', 'voiture', 'alquiler', 'coches'],
        ];

        $findings = [];
        $translations = BlogTranslation::query()
            ->whereIn('locale', array_keys($patterns))
            ->get(['id', 'blog_id', 'locale', 'slug', 'title']);

        foreach ($translations as $translation) {
            $slug = strtolower((string) $translation->slug);

            foreach ($patterns[$translation->locale] as $needle) {
                if (! str_contains($slug, $needle)) {
                    continue;
                }

                $findings[] = "Suspicious {$translation->locale} blog slug '{$translation->slug}' on blog #{$translation->blog_id} ({$translation->title}) contains '{$needle}'.";
                break;
            }
        }

        return $findings;
    }
}
