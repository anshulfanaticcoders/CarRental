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

            @unlink($tarPath);
            @unlink($tarFile);

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
}
