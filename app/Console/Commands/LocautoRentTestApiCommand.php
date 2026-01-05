<?php
/**
 * LocautoRent API Test Script
 *
 * This script tests LocautoRent API endpoints using the existing LocautoRentService class
 * and saves raw XML responses for analysis and documentation purposes.
 *
 * Usage: php artisan locauto:test_api
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LocautoRentService;

class LocautoRentTestApiCommand extends Command
{
    protected $signature = 'locauto:test_api';
    protected $description = 'Test LocautoRent API and save responses';

    public function handle(LocautoRentService $locautoService)
    {
        $this->info('=== LocautoRent API Test Script ===');
        $this->info('Testing LocautoRent API endpoints...');
        $this->newLine();

        // Test locations - use valid codes from LocautoRent documentation
        $locations = [
            'MXP' => 'Milan Malpensa Airport T1',
            'LIN' => 'Milan Linate Airport',
            'BGY' => 'Bergamo Orio al Serio Airport',
            'MIT' => 'Milan Central Station',
            'RMT' => 'Rome Termini Station'
        ];

        // Test parameters - USE FUTURE DATES (today is 2026-01-02)
        $pickupDate = '2026-02-01';
        $pickupTime = '10:00';
        $returnDate = '2026-02-05';
        $returnTime = '10:00';
        $age = 35;

        $outputDir = base_path('tests/api/locauto_rent');
        $testNumber = 1;

        foreach ($locations as $locationCode => $locationName) {
            $this->info("Testing location: $locationName ($locationCode)");

            try {
                // Call the API using the service
                $response = $locautoService->getVehicles(
                    $locationCode,
                    $pickupDate,
                    $pickupTime,
                    $returnDate,
                    $returnTime,
                    $age
                );

                if ($response) {
                    // Save response to file
                    $filename = sprintf('%s/locauto_%02d_vehicles_%s.xml', $outputDir, $testNumber, strtolower($locationCode));
                    file_put_contents($filename, $response);
                    $fileSize = filesize($filename);
                    $this->info("  ✓ Response saved to: $filename ($fileSize bytes)");

                    // Parse and display summary
                    $vehicles = $locautoService->parseVehicleResponse($response);
                    $this->info("  - Vehicle count: " . count($vehicles));

                    if (!empty($vehicles)) {
                        $firstVehicle = $vehicles[0];
                        $this->info("  - Currency: " . ($firstVehicle['currency'] ?? 'N/A'));
                        $this->info("  - Sample vehicle: " . ($firstVehicle['name'] ?? 'N/A'));

                        // Show extras info
                        if (!empty($firstVehicle['extras'])) {
                            $this->info("  - Extras available: " . count($firstVehicle['extras']));
                        }
                    }
                } else {
                    $this->error("  ✗ API call failed - no response");
                }
            } catch (\Exception $e) {
                $this->error("  ✗ Exception: " . $e->getMessage());
            }

            $this->newLine();
            $testNumber++;
        }

        $this->info('=== Testing Complete ===');
        return 0;
    }
}
