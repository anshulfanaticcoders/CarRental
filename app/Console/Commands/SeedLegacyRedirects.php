<?php

namespace App\Console\Commands;

use App\Models\SeoRedirect;
use Illuminate\Console\Command;

class SeedLegacyRedirects extends Command
{
    protected $signature = 'seo:seed-legacy-redirects';

    protected $description = 'Seed 410 Gone entries for legacy/deleted pages into the seo_redirects table';

    public function handle(): int
    {
        $gonePaths = [
            // Legacy provider booking pages
            '/green-motion-cars',
            '/adobe-booking',
            '/wheelsys-car',
            '/locauto-rent-cars',
            '/locauto-rent-booking-success',
            '/locauto-rent-booking-cancel',
            '/ok-mobility-success',
            '/ok-mobility-cancel',
            '/single-car',

            // Old non-localized page paths
            '/about-us',
            '/privacy-policy',
            '/terms-and-conditions',
        ];

        $created = 0;
        foreach ($gonePaths as $path) {
            $exists = SeoRedirect::where('from_url', $path)->exists();
            if (! $exists) {
                SeoRedirect::addGone($path, 'Legacy page - seeded');
                $created++;
            }
        }

        $this->info("Seeded {$created} legacy 410 entries.");
        $this->line('Total seo_redirects: ' . SeoRedirect::count());

        return self::SUCCESS;
    }
}
