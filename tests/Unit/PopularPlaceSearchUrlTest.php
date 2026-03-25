<?php

namespace Tests\Unit;

use App\Models\PopularPlace;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PopularPlaceSearchUrlTest extends TestCase
{
    #[Test]
    public function it_serializes_a_gateway_search_url_for_popular_places(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-21 10:00:00', 'UTC'));

        $place = new PopularPlace([
            'place_name' => 'Dubai Airport (DXB)',
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
            'latitude' => 25.251369,
            'longitude' => 55.347204,
            'unified_location_id' => 3272373056,
        ]);

        $payload = $place->toArray();

        $this->assertArrayHasKey('search_url', $payload);
        $this->assertNotNull($payload['search_url']);

        $query = [];
        parse_str(parse_url($payload['search_url'], PHP_URL_QUERY) ?? '', $query);

        $this->assertSame('Dubai Airport (DXB)', $query['where'] ?? null);
        $this->assertSame('Dubai', $query['city'] ?? null);
        $this->assertSame('United Arab Emirates', $query['country'] ?? null);
        $this->assertSame('3272373056', $query['unified_location_id'] ?? null);
        $this->assertSame('3272373056', $query['dropoff_unified_location_id'] ?? null);
        $this->assertSame('mixed', $query['provider'] ?? null);
        $this->assertSame('2026-03-22', $query['date_from'] ?? null);
        $this->assertSame('2026-03-23', $query['date_to'] ?? null);
        $this->assertSame('09:00', $query['start_time'] ?? null);
        $this->assertSame('09:00', $query['end_time'] ?? null);
        $this->assertSame('35', $query['age'] ?? null);

        Carbon::setTestNow();
    }
}
