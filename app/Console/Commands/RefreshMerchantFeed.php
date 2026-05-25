<?php

namespace App\Console\Commands;

use App\Services\MerchantFeeds\MerchantFeedRefreshService;
use Illuminate\Console\Command;

class RefreshMerchantFeed extends Command
{
    protected $signature = 'merchant-feed:refresh {feed=awin}';

    protected $description = 'Refresh a public merchant product feed XML file';

    public function handle(MerchantFeedRefreshService $refreshService): int
    {
        $feedName = (string) $this->argument('feed');

        try {
            $stats = $refreshService->refresh($feedName);
        } catch (\Throwable $exception) {
            $this->error("Merchant feed refresh failed for [{$feedName}].");
            $this->line($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Merchant feed [{$feedName}] refreshed.");
        $this->line('Internal items: '.$stats['internal_items']);
        $this->line('External items: '.$stats['external_items']);
        $this->line('XML items: '.$stats['xml_items']);
        $this->line('Skipped items: '.$stats['skipped_items']);
        $this->line('Output: '.$stats['output_path']);

        return self::SUCCESS;
    }
}
