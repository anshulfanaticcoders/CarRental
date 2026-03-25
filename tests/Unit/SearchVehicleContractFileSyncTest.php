<?php

namespace Tests\Unit;

use Tests\TestCase;

class SearchVehicleContractFileSyncTest extends TestCase
{
    public function test_search_vehicle_contract_schema_matches_gateway_copy_byte_for_byte(): void
    {
        $carRentalContract = base_path('contracts/search-vehicle-v1.schema.json');
        $gatewayContract = dirname(base_path()) . '/vrooem-gateway/contracts/search-vehicle-v1.schema.json';

        $this->assertFileExists($carRentalContract);
        $this->assertFileExists($gatewayContract);
        $this->assertSame(file_get_contents($gatewayContract), file_get_contents($carRentalContract));
    }
}
