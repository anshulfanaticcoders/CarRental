<?php

namespace Tests\Feature;

use App\Models\PopularPlace;
use App\Services\LocationSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PopularPlaceCoverageTest extends TestCase
{
    use RefreshDatabase;

    private function makePlace(int $unifiedId, bool $active = true, int $providerCount = 2): PopularPlace
    {
        return PopularPlace::create([
            'place_name' => 'Test Airport '.$unifiedId,
            'city' => 'Test City',
            'country' => 'Testland',
            'latitude' => 1.0,
            'longitude' => 1.0,
            'unified_location_id' => $unifiedId,
            'image' => 'https://example.com/x.jpg',
            'provider_count' => $providerCount,
            'is_active' => $active,
        ]);
    }

    #[Test]
    public function search_url_pushes_pickup_out_by_the_configured_lead_time(): void
    {
        config(['destinations.default_lead_days' => 14, 'destinations.default_rental_days' => 3]);
        $place = $this->makePlace(555);

        parse_str(parse_url($place->search_url, PHP_URL_QUERY), $q);

        $this->assertSame(now()->addDays(14)->format('Y-m-d'), $q['date_from']);
        $this->assertSame(now()->addDays(17)->format('Y-m-d'), $q['date_to']);
        $this->assertSame('555', $q['unified_location_id']);
    }

    #[Test]
    public function counts_only_non_internal_providers(): void
    {
        $location = ['providers' => [
            ['provider' => 'internal'],
            ['provider' => 'greenmotion'],
            ['provider' => 'surprice'],
            ['provider' => ''],
        ]];

        $this->assertSame(2, PopularPlace::countBookableProviders($location));
        $this->assertSame(0, PopularPlace::countBookableProviders(null));
    }

    #[Test]
    public function verify_command_deactivates_zero_provider_destinations(): void
    {
        $live = $this->makePlace(100, active: true);   // gateway will report providers
        $dead = $this->makePlace(200, active: true);   // gateway will report none

        $this->mock(LocationSearchService::class, function ($mock) {
            $mock->shouldReceive('getLocationByUnifiedId')->with(100)->andReturn([
                'providers' => [['provider' => 'internal'], ['provider' => 'okmobility'], ['provider' => 'surprice']],
            ]);
            $mock->shouldReceive('getLocationByUnifiedId')->with(200)->andReturn([
                'providers' => [['provider' => 'internal']],
            ]);
        });

        $this->artisan('destinations:verify')->assertSuccessful();

        $live->refresh();
        $dead->refresh();

        $this->assertTrue($live->is_active);
        $this->assertSame(2, $live->provider_count);
        $this->assertNotNull($live->last_verified_at);

        $this->assertFalse($dead->is_active);
        $this->assertSame(0, $dead->provider_count);
    }

    #[Test]
    public function destinations_page_only_returns_active_places(): void
    {
        $this->makePlace(100, active: true);
        $this->makePlace(200, active: false);

        $response = $this->get(route('destinations.index', ['locale' => 'en']));

        $response->assertOk();
        $data = $response->viewData('page')['props']['popularPlaces']['data'];
        $this->assertCount(1, $data);
        $this->assertSame(100, (int) $data[0]['unified_location_id']);
    }
}
