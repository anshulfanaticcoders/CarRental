<?php

namespace Tests\Feature;

use Tests\TestCase;

class CurrencyApiTest extends TestCase
{
    public function test_web_currency_api_keeps_legacy_keys_and_adds_registry_payload(): void
    {
        $response = $this->getJson('/api/currencies');

        $response->assertOk()
            ->assertJsonStructure([
                'current_currency',
                'supported_currencies',
                'base_currency',
                'default_currency',
                'currencies' => [
                    '*' => ['code', 'name', 'symbol', 'flag_country', 'minor_unit', 'popular', 'selectable', 'checkout_enabled'],
                ],
                'popular_currencies',
                'version',
            ])
            ->assertJsonPath('base_currency', 'EUR');
    }

    public function test_mobile_currency_api_keeps_existing_shape_and_adds_metadata(): void
    {
        $response = $this->getJson('/api/mobile/currencies');

        $response->assertOk()
            ->assertJsonStructure([
                'supported',
                'base',
                'currencies' => [
                    '*' => ['code', 'name', 'symbol', 'flag_country', 'minor_unit', 'popular', 'selectable', 'checkout_enabled'],
                ],
                'popular',
                'default',
                'version',
            ])
            ->assertJsonPath('base', 'EUR');
    }
}
