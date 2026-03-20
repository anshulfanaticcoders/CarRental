<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class UpdateGeoIpDatabase extends Command
{
    protected $signature = 'geoip:update';
    protected $description = 'Download the latest MaxMind GeoLite2-City database';

    public function handle(): int
    {
        $licenseKey = config('location.maxmind.license_key');

        if (!$licenseKey) {
            $this->error('MAXMIND_LICENSE_KEY is not set in .env');
            return self::FAILURE;
        }

        $url = sprintf(
            'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&license_key=%s&suffix=tar.gz',
            $licenseKey
        );

        $dbDir = database_path('maxmind');
        $tarPath = $dbDir . '/GeoLite2-City.tar.gz';
        $dbPath = $dbDir . '/GeoLite2-City.mmdb';

        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        $this->info('Downloading GeoLite2-City database...');

        try {
            $response = Http::timeout(120)->withOptions(['sink' => $tarPath])->get($url);

            if (!$response->successful()) {
                $this->error('Download failed: HTTP ' . $response->status());
                return self::FAILURE;
            }

            $this->info('Extracting database...');

            // Use shell tar command (works reliably on Linux servers)
            $exitCode = 0;
            $output = [];
            exec("tar -xzf {$tarPath} -C {$dbDir} 2>&1", $output, $exitCode);

            if ($exitCode !== 0) {
                $this->error('tar extraction failed: ' . implode("\n", $output));
                // Fallback to PharData
                $this->info('Trying PharData fallback...');
                $this->extractWithPhar($tarPath, $dbDir, $dbPath);
            } else {
                // Find and move the .mmdb file
                $mmdbFiles = glob($dbDir . '/GeoLite2-City_*/*.mmdb');
                if (!empty($mmdbFiles)) {
                    rename($mmdbFiles[0], $dbPath);
                }
                // Clean up extracted directory
                $extractedDirs = glob($dbDir . '/GeoLite2-City_*');
                foreach ($extractedDirs as $dir) {
                    $this->removeDirectory($dir);
                }
            }

            @unlink($tarPath);

            if (file_exists($dbPath)) {
                $this->info('GeoLite2-City database updated successfully (' . round(filesize($dbPath) / 1024 / 1024, 1) . ' MB)');
                return self::SUCCESS;
            }

            $this->error('Database file not found after extraction');
            return self::FAILURE;

        } catch (\Exception $e) {
            $this->error('Update failed: ' . $e->getMessage());
            @unlink($tarPath);
            return self::FAILURE;
        }
    }

    private function extractWithPhar(string $tarPath, string $dbDir, string $dbPath): void
    {
        $phar = new \PharData($tarPath);
        $phar->decompress();

        $tarFile = str_replace('.tar.gz', '.tar', $tarPath);
        $pharTar = new \PharData($tarFile);

        foreach ($pharTar as $file) {
            if ($file->isDir()) {
                foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($file->getPathname())) as $innerFile) {
                    if (str_ends_with($innerFile->getFilename(), '.mmdb')) {
                        copy($innerFile->getPathname(), $dbPath);
                        break 2;
                    }
                }
            }
        }

        @unlink($tarFile);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
        }
        rmdir($dir);
    }
}
