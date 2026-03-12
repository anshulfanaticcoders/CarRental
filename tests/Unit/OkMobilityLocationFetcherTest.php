<?php

namespace Tests\Unit;

use App\Services\Locations\Fetchers\OkMobilityLocationFetcher;
use App\Services\OkMobilityService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OkMobilityLocationFetcherTest extends TestCase
{
    #[Test]
    public function it_normalizes_country_ids_from_the_country_feed(): void
    {
        $okMobilityService = $this->createMock(OkMobilityService::class);
        $okMobilityService->method('getCountries')->willReturn(<<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <getCountriesResult>
      <Country>
        <countryCode>239</countryCode>
        <country>united arab emirates</country>
        <ISOCode>AE</ISOCode>
      </Country>
    </getCountriesResult>
  </soap:Body>
</soap:Envelope>
XML);
        $okMobilityService->method('getStations')->willReturn(<<<XML
<?xml version="1.0" encoding="utf-8"?>
<Stations>
  <RentalStation>
    <StationID>650</StationID>
    <Station>Dubai Airport</Station>
    <City>Dubai</City>
    <CountryID>239</CountryID>
    <Address>Dubai Airport Terminal 1</Address>
    <Latitude>25.252778</Latitude>
    <Longitude>55.364444</Longitude>
  </RentalStation>
</Stations>
XML);

        $fetcher = new OkMobilityLocationFetcher($okMobilityService);

        $results = $fetcher->fetch();

        $this->assertCount(1, $results);
        $this->assertSame('United Arab Emirates', $results[0]['country']);
        $this->assertSame('AE', $results[0]['country_code'] ?? null);
        $this->assertSame('Dubai, United Arab Emirates', $results[0]['below_label']);
    }
}
