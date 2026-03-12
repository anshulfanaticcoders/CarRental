<?php

namespace Tests\Unit;

use App\Services\Locations\ProviderLocationFetchManager;
use App\Services\Locations\ProviderLocationFetcherInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProviderLocationFetchManagerTest extends TestCase
{
    #[Test]
    public function it_fetches_locations_from_registered_fetchers_by_key(): void
    {
        $internalRows = [['source' => 'internal', 'provider_location_id' => 'internal_1']];
        $greenMotionRows = [['source' => 'greenmotion', 'provider_location_id' => '359']];
        $okMobilityRows = [['source' => 'okmobility', 'provider_location_id' => '650']];

        $manager = new ProviderLocationFetchManager([
            new class ($internalRows) implements ProviderLocationFetcherInterface {
                public function __construct(private array $rows)
                {
                }

                public function key(): string
                {
                    return 'internal';
                }

                public function fetch(): array
                {
                    return $this->rows;
                }
            },
            new class ($greenMotionRows) implements ProviderLocationFetcherInterface {
                public function __construct(private array $rows)
                {
                }

                public function key(): string
                {
                    return 'greenmotion';
                }

                public function fetch(): array
                {
                    return $this->rows;
                }
            },
            new class ($okMobilityRows) implements ProviderLocationFetcherInterface {
                public function __construct(private array $rows)
                {
                }

                public function key(): string
                {
                    return 'okmobility';
                }

                public function fetch(): array
                {
                    return $this->rows;
                }
            },
        ]);

        $this->assertSame($internalRows, $manager->getInternalVehicleLocations());
        $this->assertSame($greenMotionRows, $manager->fetchProviderLocations('greenmotion'));
        $this->assertSame($okMobilityRows, $manager->fetchOkMobilityLocations());
        $this->assertSame([], $manager->fetchProviderLocations('missing-provider'));
    }
}
