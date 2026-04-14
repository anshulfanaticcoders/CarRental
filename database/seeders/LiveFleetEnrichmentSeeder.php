<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use App\Services\Vehicles\LiveFleetEnrichmentService;
use Illuminate\Database\Seeder;

class LiveFleetEnrichmentSeeder extends Seeder
{
    private const TARGET_VENDOR_ID = 148;

    public function run(): void
    {
        /** @var LiveFleetEnrichmentService $service */
        $service = app(LiveFleetEnrichmentService::class);

        $stats = [
            'processed' => 0,
            'linked_locations' => 0,
            'missing_locations' => 0,
            'created_locations' => 0,
            'generated_sipp' => 0,
            'missing_sipp' => 0,
        ];

        Vehicle::query()
            ->where('vendor_id', self::TARGET_VENDOR_ID)
            ->with(['category:id,name', 'vendorProfileData:id,user_id,company_name,company_phone_number'])
            ->orderBy('id')
            ->chunkById(50, function ($vehicles) use ($service, &$stats) {
                foreach ($vehicles as $vehicle) {
                    $result = $service->enrich($vehicle);

                    $stats['processed']++;
                    $stats['linked_locations'] += $result['location_linked'] ? 1 : 0;
                    $stats['missing_locations'] += $result['location_linked'] ? 0 : 1;
                    $stats['created_locations'] += $result['location_created'] ? 1 : 0;
                    $stats['generated_sipp'] += $result['sipp_code'] ? 1 : 0;
                    $stats['missing_sipp'] += $result['sipp_code'] ? 0 : 1;
                }
            });

        $this->command?->info(sprintf(
            'Live fleet enrichment completed for vendor_id=%d.',
            self::TARGET_VENDOR_ID,
        ));
        $this->command?->table(
            ['processed', 'linked_locations', 'missing_locations', 'created_locations', 'generated_sipp', 'missing_sipp'],
            [[
                $stats['processed'],
                $stats['linked_locations'],
                $stats['missing_locations'],
                $stats['created_locations'],
                $stats['generated_sipp'],
                $stats['missing_sipp'],
            ]]
        );
    }
}
