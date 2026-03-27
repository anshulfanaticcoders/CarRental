<?php

namespace Tests\Unit;

use App\Models\Vehicle;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VehicleSearchStatusTest extends TestCase
{
    #[Test]
    public function it_exposes_the_shared_internal_search_statuses(): void
    {
        $this->assertSame(
            ['active', 'available', 'rented'],
            Vehicle::searchableStatuses()
        );
    }
}
