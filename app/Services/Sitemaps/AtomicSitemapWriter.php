<?php

namespace App\Services\Sitemaps;

use RuntimeException;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\SitemapIndex;

class AtomicSitemapWriter
{
    public function write(Sitemap|SitemapIndex $sitemap, string $path): void
    {
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $tempPath = $path . '.tmp';

        $sitemap->writeToFile($tempPath);

        if (@rename($tempPath, $path)) {
            return;
        }

        $copied = @copy($tempPath, $path);
        $deleted = @unlink($tempPath);

        if (! $copied || ! $deleted) {
            throw new RuntimeException("Failed to atomically write sitemap to {$path}");
        }
    }
}
