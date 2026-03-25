<?php

namespace App\Console\Commands;

use App\Services\Sitemaps\AtomicSitemapWriter;
use App\Services\Sitemaps\EloquentSitemapDataProvider;
use App\Services\Sitemaps\PublicSitemapBuilder;
use App\Services\Sitemaps\SitemapUrlPolicy;
use Illuminate\Console\Command;
use RuntimeException;

class GeneratePublicSitemaps extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Generate public sitemaps (static files)';

    public function handle(): int
    {
        try {
            $builder = new PublicSitemapBuilder(
                new EloquentSitemapDataProvider(),
                new SitemapUrlPolicy()
            );
            $writer = new AtomicSitemapWriter();

            $result = $builder->build();

            // Clean up stale sitemap files before writing new ones
            $sitemapsDir = public_path('sitemaps');
            if (is_dir($sitemapsDir)) {
                $staleFiles = glob($sitemapsDir . '/*.xml');
                foreach ($staleFiles as $staleFile) {
                    @unlink($staleFile);
                }
                $this->line('Cleaned ' . count($staleFiles) . ' old sitemap files.');
            }

            foreach ($result->files as $file) {
                $writer->write($file->sitemap, public_path($file->path));
            }

            $writer->write($result->index, public_path('sitemap.xml'));

            $this->info('Public sitemaps generated successfully.');
            $this->line('Files: ' . count($result->files) . ' + sitemap.xml');

            return self::SUCCESS;
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        } catch (\Throwable $exception) {
            $this->error('Sitemap generation failed.');
            $this->line($exception->getMessage());
            return self::FAILURE;
        }
    }
}
