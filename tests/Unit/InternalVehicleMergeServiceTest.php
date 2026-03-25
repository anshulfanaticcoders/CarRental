<?php

namespace Tests\Unit;

use App\Services\Search\InternalVehicleMergeService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class InternalVehicleMergeServiceTest extends TestCase
{
    public function test_it_excludes_internal_vehicles_for_mixed_searches_without_an_internal_location(): void
    {
        $service = new InternalVehicleMergeService();

        $vehicles = collect([
            $this->makeInternalVehicle('airport st', 'Dubai', null, 'United Arab Emirates'),
            $this->makeInternalVehicle('downtown office', 'Dubai', null, 'United Arab Emirates'),
        ]);

        $result = $service->forGatewayMerge(
            $vehicles,
            ['provider' => 'mixed'],
            ['name' => 'Dubai Downtown', 'our_location_id' => null],
            false
        );

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    public function test_it_keeps_only_exact_internal_location_matches_for_mixed_searches(): void
    {
        $service = new InternalVehicleMergeService();

        $targetHash = 'internal_' . md5('Dubai' . '' . 'United Arab Emirates' . 'downtown office');
        $vehicles = collect([
            $this->makeInternalVehicle('downtown office', 'Dubai', null, 'United Arab Emirates'),
            $this->makeInternalVehicle('airport st', 'Dubai', null, 'United Arab Emirates'),
        ]);

        $result = $service->forGatewayMerge(
            $vehicles,
            ['provider' => 'mixed'],
            ['name' => 'Dubai Downtown', 'our_location_id' => $targetHash],
            false
        );

        $this->assertCount(1, $result);
        $this->assertSame('downtown office', $result->first()['location']);
    }

    public function test_it_excludes_internal_vehicles_for_provider_specific_external_searches(): void
    {
        $service = new InternalVehicleMergeService();

        $vehicles = collect([
            $this->makeInternalVehicle('airport st', 'Dubai', null, 'United Arab Emirates'),
        ]);

        $result = $service->forGatewayMerge(
            $vehicles,
            ['provider' => 'greenmotion'],
            ['name' => 'Dubai Airport', 'our_location_id' => 'internal_' . md5('Dubai' . '' . 'United Arab Emirates' . 'airport st')],
            false
        );

        $this->assertCount(0, $result);
    }

    public function test_it_excludes_internal_vehicles_for_one_way_searches(): void
    {
        $service = new InternalVehicleMergeService();

        $vehicles = collect([
            $this->makeInternalVehicle('downtown office', 'Dubai', null, 'United Arab Emirates'),
        ]);

        $result = $service->forGatewayMerge(
            $vehicles,
            ['provider' => 'mixed'],
            ['name' => 'Dubai Downtown', 'our_location_id' => 'internal_' . md5('Dubai' . '' . 'United Arab Emirates' . 'downtown office')],
            true
        );

        $this->assertCount(0, $result);
    }

    public function test_it_supports_canonical_internal_search_vehicle_payloads(): void
    {
        $service = new InternalVehicleMergeService();

        $targetHash = 'internal_' . md5('Dubai' . '' . 'United Arab Emirates' . 'downtown office');
        $vehicles = collect([
            [
                'source' => 'internal',
                'booking_context' => [
                    'provider_payload' => [
                        'location' => 'downtown office',
                        'city' => 'Dubai',
                        'state' => null,
                        'country' => 'United Arab Emirates',
                    ],
                ],
            ],
            [
                'source' => 'internal',
                'booking_context' => [
                    'provider_payload' => [
                        'location' => 'airport st',
                        'city' => 'Dubai',
                        'state' => null,
                        'country' => 'United Arab Emirates',
                    ],
                ],
            ],
        ]);

        $result = $service->forGatewayMerge(
            $vehicles,
            ['provider' => 'mixed'],
            ['name' => 'Dubai Downtown', 'our_location_id' => $targetHash],
            false
        );

        $this->assertCount(1, $result);
        $this->assertSame(
            'downtown office',
            $result->first()['booking_context']['provider_payload']['location']
        );
    }

    private function makeInternalVehicle(string $location, string $city, ?string $state, string $country): array
    {
        return [
            'source' => 'internal',
            'location' => $location,
            'city' => $city,
            'state' => $state,
            'country' => $country,
        ];
    }
}
