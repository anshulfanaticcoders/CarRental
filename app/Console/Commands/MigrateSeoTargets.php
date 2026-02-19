<?php

namespace App\Console\Commands;

use App\Models\Blog;
use App\Models\Page;
use App\Models\SeoMeta;
use App\Services\Seo\SeoMetaResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MigrateSeoTargets extends Command
{
    protected $signature = 'seo:migrate-targets {--dry-run : Show changes without saving}';

    protected $description = 'Backfill seoable + route targets on existing SeoMeta records.';

    public function handle(SeoMetaResolver $resolver): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $updated = 0;
        $skipped = 0;

        SeoMeta::query()->orderBy('id')->chunkById(200, function ($metas) use (&$updated, &$skipped, $dryRun, $resolver) {
            foreach ($metas as $meta) {
                $changes = [];

                $slug = $meta->url_slug;

                if ($meta->seoable_type || $meta->route_name) {
                    $skipped++;
                    continue;
                }

                if ($slug === '/' || $slug === null) {
                    // Homepage
                    $changes['route_name'] = 'welcome';
                    $changes['route_params'] = [];
                    $changes['route_params_hash'] = $resolver->hashRouteParams([]);
                } elseif (is_string($slug) && Str::startsWith($slug, 'blog/')) {
                    $blogSlug = Str::after($slug, 'blog/');
                    $blog = Blog::query()->where('slug', $blogSlug)->first();
                    if ($blog) {
                        $changes['seoable_type'] = $blog->getMorphClass();
                        $changes['seoable_id'] = $blog->getKey();
                    }
                } elseif (is_string($slug) && Str::startsWith($slug, 'page/')) {
                    $pageSlug = Str::after($slug, 'page/');
                    $page = Page::query()->where('slug', $pageSlug)->first();
                    if ($page) {
                        $changes['seoable_type'] = $page->getMorphClass();
                        $changes['seoable_id'] = $page->getKey();
                    }
                } elseif ($slug === 'contact-us' || $slug === '/contact-us') {
                    $changes['route_name'] = 'contact-us';
                    $changes['route_params'] = [];
                    $changes['route_params_hash'] = $resolver->hashRouteParams([]);
                } elseif ($slug === '/faq' || $slug === 'faq') {
                    $changes['route_name'] = 'faq.show';
                    $changes['route_params'] = [];
                    $changes['route_params_hash'] = $resolver->hashRouteParams([]);
                }

                if (empty($changes)) {
                    $skipped++;
                    continue;
                }

                $this->line('SeoMeta #' . $meta->id . ' (' . ($meta->url_slug ?? '-') . ') => ' . json_encode($changes));

                if (! $dryRun) {
                    $meta->forceFill($changes)->save();
                }

                $updated++;
            }
        });

        $this->info("Updated: {$updated}");
        $this->info("Skipped: {$skipped}");

        if ($dryRun) {
            $this->comment('Dry run mode: no changes were saved.');
        }

        return self::SUCCESS;
    }
}
