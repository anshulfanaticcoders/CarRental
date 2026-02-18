<?php

namespace App\Services\Sitemaps;

use Carbon\Carbon;
use Spatie\Sitemap\Sitemap;

class SitemapFile
{
    public function __construct(
        public string $path,
        public Sitemap $sitemap,
        public ?Carbon $lastModified,
        public int $urlCount
    ) {
    }
}
