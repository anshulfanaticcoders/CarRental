<?php

namespace App\Console\Commands;

use App\Models\PopularPlace;
use App\Services\LocationSearchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Re-checks each homepage "Top Destination" against the gateway and updates its
 * bookable-provider coverage. Destinations whose location has lost all
 * providers (gateway data drifts) are deactivated so they stop rendering empty.
 * Read-only against the gateway; only updates coverage columns on our own rows.
 */
class VerifyPopularPlaceCoverage extends Command
{
    protected $signature = 'destinations:verify';

    protected $description = 'Verify Top Destinations still map to gateway locations with bookable providers; auto-hide dead ones.';

    public function handle(LocationSearchService $locationService): int
    {
        $places = PopularPlace::all();
        if ($places->isEmpty()) {
            $this->info('No popular places to verify.');

            return self::SUCCESS;
        }

        $rows = [];
        $deactivated = 0;

        foreach ($places as $place) {
            $unifiedId = (int) ($place->unified_location_id ?? 0);
            $location = $unifiedId > 0 ? $locationService->getLocationByUnifiedId($unifiedId) : null;
            $providerCount = PopularPlace::countBookableProviders($location);
            $isActive = $providerCount > 0;

            $place->forceFill([
                'provider_count' => $providerCount,
                'last_verified_at' => now(),
                'is_active' => $isActive,
            ])->save();

            if (! $isActive) {
                $deactivated++;
            }

            $rows[] = [
                $place->id,
                mb_substr((string) $place->place_name, 0, 30),
                $unifiedId ?: '—',
                $providerCount,
                $isActive ? '✅ active' : '⛔ hidden',
            ];
        }

        $this->table(['ID', 'Destination', 'Unified ID', 'Providers', 'Status'], $rows);
        $this->info("Verified {$places->count()} destination(s); {$deactivated} hidden (no bookable providers).");

        Log::info('destinations:verify completed', [
            'total' => $places->count(),
            'deactivated' => $deactivated,
        ]);

        return self::SUCCESS;
    }
}
