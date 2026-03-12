<?php

namespace Tests\Unit;

use App\Console\Commands\UpdateUnifiedLocationsCommand;
use App\Services\LocationMatchingService;
use App\Services\LocationSearchService;
use App\Services\Locations\ProviderLocationFetchManager;
use App\Services\VrooemGatewayService;
use Illuminate\Console\OutputStyle;
use ReflectionMethod;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\TestCase;

class UpdateUnifiedLocationsCommandTest extends TestCase
{
    public function test_it_merges_alias_airports_that_share_the_same_iata_code(): void
    {
        $locations = [
            [
                'label' => 'Marrakech Airport',
                'city' => 'Marrakech',
                'country' => 'Morocco',
                'latitude' => 31.60188,
                'longitude' => -8.02568,
                'source' => 'greenmotion',
                'provider_location_id' => '359',
                'location_type' => 'airport',
                'iata' => 'RAK',
            ],
            [
                'label' => 'Marrakech Airport',
                'city' => 'Marrakech',
                'country' => 'Morocco',
                'latitude' => 31.60188,
                'longitude' => -8.02568,
                'source' => 'usave',
                'provider_location_id' => '359',
                'location_type' => 'airport',
                'iata' => 'RAK',
            ],
            [
                'label' => 'Marrakesh Airport',
                'city' => 'Marrakech',
                'country' => 'MA',
                'latitude' => 31.589453,
                'longitude' => -8.009716,
                'source' => 'surprice',
                'provider_location_id' => 'RAK',
                'location_type' => 'airport',
                'iata' => 'RAK',
            ],
            [
                'label' => 'Marrakesh airport',
                'city' => 'Marrakesh',
                'country' => 'MA',
                'latitude' => 31.6069,
                'longitude' => -8.0363,
                'source' => 'renteon',
                'provider_location_id' => 'MA-MAR-RAK',
                'location_type' => 'airport',
                'iata' => 'RAK',
            ],
            [
                'label' => 'Marrakech-Menara Airport (RAK)',
                'city' => 'Marrakesh',
                'country' => 'MA',
                'latitude' => 31.6069,
                'longitude' => -8.0363,
                'source' => 'xdrive',
                'provider_location_id' => '21',
                'location_type' => 'airport',
                'iata' => 'RAK',
            ],
        ];

        $results = $this->invokeMergeAndNormalizeLocations($locations);
        $rakLocations = array_values(array_filter(
            $results,
            static fn (array $location): bool => ($location['iata'] ?? null) === 'RAK'
        ));

        $this->assertCount(1, $rakLocations);
        $this->assertSame('Marrakech Airport', $rakLocations[0]['name']);
        $this->assertEqualsCanonicalizing(
            ['greenmotion', 'usave', 'surprice', 'renteon', 'xdrive'],
            array_column($rakLocations[0]['providers'], 'provider')
        );
        $this->assertContains('Marrakesh Airport', $rakLocations[0]['aliases']);
    }

    public function test_it_keeps_distinct_dubai_airports_separate(): void
    {
        $locations = [
            [
                'label' => 'Dubai Airport Terminal 1',
                'city' => 'Dubai',
                'country' => 'United Arab Emirates',
                'latitude' => 25.252778,
                'longitude' => 55.364444,
                'source' => 'greenmotion',
                'provider_location_id' => '59610',
                'location_type' => 'airport',
                'iata' => 'DXB',
            ],
            [
                'label' => 'Dubai Airport Terminal 2',
                'city' => 'Dubai',
                'country' => 'United Arab Emirates',
                'latitude' => 25.2647,
                'longitude' => 55.3657,
                'source' => 'usave',
                'provider_location_id' => '60847',
                'location_type' => 'airport',
                'iata' => 'DXB',
            ],
            [
                'label' => 'Dubai International Airport (DXB)',
                'city' => 'Dubai',
                'country' => 'United Arab Emirates',
                'latitude' => 25.253174,
                'longitude' => 55.365673,
                'source' => 'xdrive',
                'provider_location_id' => '83',
                'location_type' => 'airport',
                'iata' => 'DXB',
            ],
            [
                'label' => 'Dubai Al Maktoum Airport',
                'city' => 'Dubai',
                'country' => 'United Arab Emirates',
                'latitude' => 24.896356,
                'longitude' => 55.161389,
                'source' => 'surprice',
                'provider_location_id' => 'DWC',
                'location_type' => 'airport',
                'iata' => 'DWC',
            ],
        ];

        $results = $this->invokeMergeAndNormalizeLocations($locations);
        $airportLocations = array_values(array_filter(
            $results,
            static fn (array $location): bool => ($location['location_type'] ?? null) === 'airport'
        ));

        usort($airportLocations, static fn (array $a, array $b): int => strcmp((string) ($a['iata'] ?? ''), (string) ($b['iata'] ?? '')));

        $this->assertCount(2, $airportLocations);
        $this->assertSame(['DWC', 'DXB'], array_column($airportLocations, 'iata'));
        $this->assertNotSame($airportLocations[0]['unified_location_id'], $airportLocations[1]['unified_location_id']);
        $this->assertSame(['surprice'], array_column($airportLocations[0]['providers'], 'provider'));
        $this->assertEqualsCanonicalizing(
            ['greenmotion', 'usave', 'xdrive'],
            array_column($airportLocations[1]['providers'], 'provider')
        );
    }

    public function test_it_merges_alias_airports_even_when_one_provider_has_no_coordinates_or_iata(): void
    {
        $results = $this->invokeMergeAndNormalizeLocations([
            [
                'label' => 'Marrakech Airport',
                'city' => 'Marrakech',
                'country' => 'Morocco',
                'latitude' => 31.60188,
                'longitude' => -8.02568,
                'source' => 'greenmotion',
                'provider_location_id' => '359',
                'location_type' => 'airport',
                'iata' => 'RAK',
            ],
            [
                'label' => 'Marrakesh airport',
                'city' => 'Marrakesh',
                'country' => 'MA',
                'latitude' => 0,
                'longitude' => 0,
                'source' => 'renteon',
                'provider_location_id' => 'MA-MAR-RAK',
                'location_type' => 'airport',
            ],
        ]);

        $this->assertCount(1, $results);
        $this->assertEqualsCanonicalizing(
            ['greenmotion', 'renteon'],
            array_column($results[0]['providers'], 'provider')
        );
        $this->assertContains('Marrakesh airport', $results[0]['aliases']);
    }

    public function test_it_preserves_provider_specific_location_metadata_in_unified_rows(): void
    {
        $results = $this->invokeMergeAndNormalizeLocations([
            [
                'label' => 'Marrakech Airport',
                'city' => 'Marrakech',
                'country' => 'Morocco',
                'latitude' => 31.60188,
                'longitude' => -8.02568,
                'source' => 'surprice',
                'provider_location_id' => 'RAK',
                'provider_extended_location_code' => 'RAKA01',
                'location_type' => 'airport',
                'iata' => 'RAK',
            ],
        ]);

        $this->assertCount(1, $results);
        $this->assertSame('RAKA01', $results[0]['providers'][0]['extended_location_code'] ?? null);
    }

    public function test_it_merges_non_airport_locations_when_country_is_name_or_alpha2_code(): void
    {
        $results = $this->invokeMergeAndNormalizeLocations([
            [
                'label' => 'Dubai Downtown',
                'city' => 'Dubai',
                'country' => 'United Arab Emirates',
                'latitude' => 25.2048,
                'longitude' => 55.2708,
                'source' => 'greenmotion',
                'provider_location_id' => 'DXB-DT-1',
                'location_type' => 'downtown',
            ],
            [
                'label' => 'Dubai Downtown',
                'city' => 'Dubai',
                'country' => 'AE',
                'latitude' => 25.2051,
                'longitude' => 55.2711,
                'source' => 'surprice',
                'provider_location_id' => 'DXBC01',
                'location_type' => 'downtown',
            ],
        ]);

        $this->assertCount(1, $results);
        $this->assertEqualsCanonicalizing(
            ['greenmotion', 'surprice'],
            array_column($results[0]['providers'], 'provider')
        );
    }

    public function test_it_does_not_collapse_distinct_unknown_city_locations_into_a_generic_city_row(): void
    {
        $results = $this->invokeMergeAndNormalizeLocations([
            [
                'label' => 'Dubai IBN Battuta Mall',
                'city' => 'Dubai',
                'country' => 'United Arab Emirates',
                'latitude' => 25.041851,
                'longitude' => 55.118729,
                'source' => 'greenmotion',
                'provider_location_id' => '60923',
                'location_type' => 'unknown',
            ],
            [
                'label' => 'Movenpick Grand Al Bustan Dubai',
                'city' => 'Dubai',
                'country' => 'United Arab Emirates',
                'latitude' => 25.248868,
                'longitude' => 55.345703,
                'source' => 'usave',
                'provider_location_id' => '61295',
                'location_type' => 'unknown',
            ],
        ]);

        $this->assertCount(2, $results);
        $this->assertNotContains('Dubai', array_column($results, 'name'));
    }

    public function test_it_uses_the_label_to_replace_suspicious_short_city_names(): void
    {
        $results = $this->invokeMergeAndNormalizeLocations([
            [
                'label' => 'Dubai Downtown',
                'city' => 'Bur',
                'country' => 'United Arab Emirates',
                'latitude' => 25.25812,
                'longitude' => 55.297569,
                'source' => 'greenmotion',
                'provider_location_id' => '60160',
                'location_type' => 'downtown',
            ],
        ]);

        $this->assertCount(1, $results);
        $this->assertSame('Dubai', $results[0]['city']);
        $this->assertSame('Dubai Downtown', $results[0]['name']);
    }

    public function test_it_delegates_ok_mobility_fetching_to_the_provider_location_fetch_manager(): void
    {
        $fetchManager = $this->createMock(ProviderLocationFetchManager::class);
        $fetchManager->expects($this->once())
            ->method('fetchOkMobilityLocations')
            ->willReturn([
                [
                    'label' => 'Dubai Airport',
                    'city' => 'Dubai',
                    'country' => 'United Arab Emirates',
                    'country_code' => 'AE',
                    'latitude' => 25.252778,
                    'longitude' => 55.364444,
                    'source' => 'okmobility',
                    'provider_location_id' => '650',
                    'location_type' => 'airport',
                ],
            ]);

        $command = $this->makeCommand(fetchManager: $fetchManager);
        $method = new ReflectionMethod(UpdateUnifiedLocationsCommand::class, 'fetchOkMobilityLocations');
        $method->setAccessible(true);

        $results = $method->invoke($command);

        $this->assertCount(1, $results);
        $this->assertSame('Dubai Airport', $results[0]['label']);
    }

    public function test_it_prefers_the_canonical_country_name_from_country_code_when_building_unified_rows(): void
    {
        $results = $this->invokeMergeAndNormalizeLocations([
            [
                'label' => 'Dubai Airport',
                'city' => 'Dubai',
                'country' => 'Verenigde Arabische Emiraten',
                'country_code' => 'AE',
                'latitude' => 25.248081,
                'longitude' => 55.345093,
                'source' => 'internal',
                'id' => 'internal_dubai_airport',
                'location_type' => 'airport',
                'iata' => 'DXB',
            ],
            [
                'label' => 'Dubai Airport',
                'city' => 'Dubai',
                'country' => 'United Arab Emirates',
                'country_code' => 'AE',
                'latitude' => 25.254444,
                'longitude' => 55.356389,
                'source' => 'surprice',
                'provider_location_id' => 'DXB',
                'provider_extended_location_code' => 'DXBA01',
                'location_type' => 'airport',
                'iata' => 'DXB',
            ],
        ]);

        $this->assertCount(1, $results);
        $this->assertSame('United Arab Emirates', $results[0]['country']);
        $this->assertSame('AE', $results[0]['country_code']);
    }

    private function invokeMergeAndNormalizeLocations(array ...$groups): array
    {
        $command = $this->makeCommand();
        $method = new ReflectionMethod(UpdateUnifiedLocationsCommand::class, 'mergeAndNormalizeLocations');
        $method->setAccessible(true);

        return $method->invokeArgs($command, $groups);
    }

    private function makeCommand(?ProviderLocationFetchManager $fetchManager = null): UpdateUnifiedLocationsCommand
    {
        $locationSearchService = new LocationSearchService($this->createMock(VrooemGatewayService::class));
        $locationMatchingService = new LocationMatchingService($locationSearchService);

        $command = new UpdateUnifiedLocationsCommand(
            $locationSearchService,
            $locationMatchingService,
            $fetchManager ?? new ProviderLocationFetchManager([])
        );

        $command->setOutput(new OutputStyle(new ArrayInput([]), new NullOutput()));

        return $command;
    }
}
