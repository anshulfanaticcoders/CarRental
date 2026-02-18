<?php

namespace App\Services\Sitemaps;

use Spatie\Sitemap\SitemapIndex;

class SitemapBuildResult
{
    public SitemapIndex $index;

    /** @var SitemapFile[] */
    public array $files = [];
}
