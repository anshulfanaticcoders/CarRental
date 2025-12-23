# Renteon API Complete Documentation

> **Test Date**: December 23, 2025
> **Base URL**: `https://aggregator.renteon.com`

---

## Table of Contents
1. [API Credentials](#api-credentials)
2. [Working Endpoints Summary](#working-endpoints-summary)
3. [Detailed Endpoint Documentation](#detailed-endpoint-documentation)
4. [Data Catalog](#data-catalog)
5. [SIPP Car Categories](#sipp-car-categories)
6. [Configuration Examples](#configuration-examples)

---

## API Credentials

| Parameter | Value |
|-----------|-------|
| Base URL | `https://aggregator.renteon.com` |
| Username | `vrooem.agg.api` |
| Password | `T45h-G11!r$jj76` |
| Provider Code | `demo` |

---

## Working Endpoints Summary

| Endpoint | Method | Status | Data Type | Count |
|----------|--------|--------|-----------|-------|
| `/api/setup/locations` | GET | ✅ Working | Locations | 1,521 |
| `/api/setup/services` | GET | ✅ Working | Services | 100 |
| `/api/setup/providers` | GET | ✅ Working | Providers | 92 |
| `/api/setup/carCategories` | GET | ✅ Working | Car Categories (SIPP) | 803 |
| `/api/setup/provider/{code}` | GET | ✅ Working | Provider Details | N/A |

---

## Detailed Endpoint Documentation

### 1. GET /api/setup/locations

**Description**: Returns all supported pickup/dropoff locations across all providers.

**Response Statistics**:
- **Total Locations**: 1,521
- **Countries Covered**: 58
- **Location Types**: Airport, Downtown, Port, RailwayStation, BusStation

**Top 15 Countries by Location Count**:

| Rank | Country | Code | Locations |
|------|---------|------|-----------|
| 1 | Spain | ES | 235 |
| 2 | Greece | GR | 146 |
| 3 | Croatia | HR | 110 |
| 4 | Italy | IT | 99 |
| 5 | Poland | PL | 76 |
| 6 | Bulgaria | BG | 69 |
| 7 | Turkey | TR | 69 |
| 8 | United Kingdom | GB | 55 |
| 9 | France | FR | 46 |
| 10 | Germany | DE | 43 |
| 11 | Portugal | PT | 42 |
| 12 | Albania | AL | 41 |
| 13 | Bosnia and Herzegovina | BA | 38 |
| 14 | Montenegro | ME | 31 |
| 15 | Slovenia | SI | 30 |

**Response Structure**:
```json
{
    "Code": "HR-SPL-SPU",
    "CountryCode": "HR",
    "Name": "Split airport",
    "Type": "Airport",
    "Category": "PickupDropoff",
    "Path": "Grad Split > Split airport"
}
```

**Field Descriptions**:
| Field | Type | Description |
|-------|------|-------------|
| Code | string | Unique location identifier (format: COUNTRY-CITY-LOCATION) |
| CountryCode | string | ISO 3166-1 alpha-2 country code |
| Name | string | Human-readable location name |
| Type | string | Airport, Downtown, Port, RailwayStation, BusStation, or empty |
| Category | string | City or PickupDropoff |
| Path | string | Hierarchical path (Region > City > Location) |

---

### 2. GET /api/setup/services

**Description**: Returns all available services (equipment, fees, insurance, etc.) across providers.

**Response Statistics**:
- **Total Services**: 100

**Services by Category**:

| Category | Count | Percentage |
|----------|-------|------------|
| Insurance | 35 | 35% |
| Additional Fee | 27 | 27% |
| Additional Equipment | 23 | 23% |
| Other Services | 15 | 15% |

#### Insurance Services (35 codes)

**Basic Insurances**:
| Code | Name | Description |
|------|------|-------------|
| INS-CDW | CDW | Collision Damage Waiver |
| INS-CDWPLUS | CDW+ | Enhanced Collision Damage Waiver |
| INS-SCDW | SCDW | Super Collision Damage Waiver |
| INS-TP | TP | Theft Protection |
| INS-PAI | PAI | Personal Accident Insurance |
| INS-TPL | TPL | Third Party Liability |
| INS-WUG | WUG | Underbody/Glass/Wheels/Tires Protection |
| INS-GP | GP | Gravel Protection |
| INS-RT | Roof Tent | Specialized insurance for campers |
| INS-SANDASH | Sand & Ash | Specialized coverage |

**Insurance Packages (Combinations)**:
| Code | Coverage |
|------|----------|
| INS-PCK_CDW_TP | CDW + Theft Protection |
| INS-PCK_CDW_TP_SCDW | CDW + TP + SCDW |
| INS-PCK_CDW_TP_SCDW_WUG | CDW + TP + SCDW + WUG |
| INS-PCK_CDW_TP_PAI | CDW + TP + PAI |
| INS-PCK_CDW_TP_GP_SCDW | CDW + TP + GP + SCDW |
| INS-PCK_TP_PAI_SCDW | TP + PAI + SCDW |
| INS-PCK_GP_SCDW | GP + SCDW |
| INS-PCK_CDW_GP_SCDW | CDW + GP + SCDW |
| INS-PCK_CDW_CDWPLUS_TP | CDW + CDW+ + TP |
| INS-PCK_CDW_CDWPLUS_TP_PAI | CDW + CDW+ + TP + PAI |
| INS-PCK_CDW_CDWPLUS_TP_PAI_SCDW | CDW + CDW+ + TP + PAI + SCDW |
| INS-PCK_CDW_CDWPLUS_TP_PAI_WUG_SCDW | Full Coverage Package |

#### Additional Equipment (23 codes)

**Child Safety**:
| Code | Name |
|------|------|
| EQ-BABYSEAT | Baby seat (0-9kg) |
| EQ-CHILDSEAT | Child seat (9-18kg) |
| EQ-CHILDSEAT0-18KG | Child seat (0-18kg) |
| EQ-CHILDSEAT18-36KG | Child seat (18-36kg) |
| EQ-BABSEISO | Baby seat ISOFIX |
| EQ-BOOSTER | Booster seat |

**Navigation & Communication**:
| Code | Name |
|------|------|
| EQ-GPS | GPS Navigation |
| EQ-WIFI | WiFi Hotspot |
| EQ-SIMCARD | SIM Card |
| EQ-MOBILEHOLDER | Mobile Phone Holder |

**Comfort & Convenience**:
| Code | Name |
|------|------|
| EQ-ROOFBOX | Roof Box |
| EQ-ROOFRACK | Roof Rack |
| EQ-ROOFTENT | Roof Tent |
| EQ-SKIRACK | Ski Rack |
| EQ-WINTERTIRE | Winter Chains (also listed as FEE) |
| EQ-KITCHENBOX | Kitchen Box |
| EQ-SLEEPINGBAG | Sleeping Bag |
| EQ-CAMPTABCHA | Camping Table & Chair |

**Other**:
| Code | Name |
|------|------|
| EQ-GOPRO | GoPro Camera |
| EQ-CARFRMINI | Mini Car Fridge |
| EQ-HELMET | Helmet |
| EQ-COUNTRYMAP | Country Map |
| EQ-BABYCARF | Baby Carriage (Foldable) |

#### Additional Fees (27 codes)

**Airport & Location Fees**:
| Code | Name |
|------|------|
| FEE-AIRPORTSURCHARGE | Airport Surcharge |
| FEE-PREMLOC | Premium Location Fee |
| FEE-MEETGREET | Meet and Greet |
| FEE-OOH | Out of Hours Service |

**Border & Travel**:
| Code | Name |
|------|------|
| FEE-CB | Cross Border |
| FEE-CBEU | Cross Border - EU |
| FEE-CBNONEU | Cross Border - Non EU |
| FEE-FERRY | Ferry Fee |
| FEE-GREENCARD | Green Card (International Insurance) |
| FEE-ONEWAY | One Way Fee |
| FEE-DROPOFF | DropOff Fee |

**Vehicle Fees**:
| Code | Name |
|------|------|
| FEE-CARPICKUP | Vehicle Pick-up Fee |
| FEE-CARDROPOFF | Vehicle Return Fee |
| FEE-DIESEL | Diesel Request |
| FEE-GUARANTEEDNEWERFLEET | Guaranteed Newer Fleet (≤1 year) |
| FEE-UPGCAR | Upgrade Car Category |
| FEE-WINTERTIRE | Winter Tires |

**Driver Fees**:
| Code | Name |
|------|------|
| FEE-DRIVERAGE | Older/Younger Driver Fee |
| ADD-DRIVER | Additional Driver |

**Other Fees**:
| Code | Name |
|------|------|
| FEE-24ASSIST | 24h Assistance |
| FEE-ROADASSIST | Road Assistance |
| FEE-EXTRP | Extended Roadside Protection |
| FEE-UNLIMITEDMILEAGE | Unlimited Mileage |
| FEE-PREPAIDFUEL | Prepaid Fuel |
| FEE-ROADTAX | Road Tax |
| FEE-TAXES | Taxes Service |
| FEE-TOLL | Toll Fee |
| FEE-VIAVERDE | Via Verde (Portugal Electronic Toll) |
| FEE-EXPRESSLANE | Express Lane |
| FEE-BAGGAGECOLLECTION | Baggage Collection |
| FEE-BAGGAGEDELIVERY | Baggage Delivery |
| FEE-PETACC | Pet Friendly Accessories |

#### Other Services

| Code | Type | Name |
|------|------|------|
| CAR-RENT | Car Rent | Car Rental Service |
| FS-FUELINGSERVICE | Fueling Service | Fueling Service |
| LOC-DELIVERY | Local Delivery | Local Delivery |
| LOC-COLLECTION | Local Collection | Local Collection |

---

### 3. GET /api/setup/providers

**Description**: Returns list of all car rental companies available on the platform.

**Response Statistics**:
- **Total Providers**: 92

**Complete Provider List** (Alphabetical by Code):

| Code | Provider Name | Code | Provider Name |
|------|---------------|------|---------------|
| 7M | 7M Rent a Car | Gallery | Gallery Rent a Car |
| ABmobil | ABmobil rent d.o.o. | Garage | Garage |
| Agio | Agio | GoRent | GO! RENT |
| Alana | Alana rent | Greencar | Green rent a car |
| Alkafi | Alkafi | HAK | HAK Rent a car |
| Alpros | Alpros | Icerental | Icerental |
| Alquicoche | Alquicoche Spain | Infinity | Infinity Rent A Car |
| Amex | Amex Car Rental LLC | JDAH | Provider |
| ARGroup | AR GROUP d.o.o. | DOOS | Jalal Creative Solution Sdn Bhd |
| Atet | Atet | JustRent | Just Rent and Trans |
| ATTRentalCars | Att Rental Cars shpk | KingTravel | King Travel |
| Autojet | Autojet | Lastminute | Last minute |
| Avantcar | Avantcar | Leos | Leos Car Rentals |
| Avia | AVIA rent a car d.o.o. | LetsDrive | LetsDrive |
| AZCarRental | AZ Car Rental USA | Lynx | Lynx |
| Barkro | BARKRO s.r.o. | MACK | M.A.C.K. d.o.o. |
| Beepit | Beepit | MDS Shpk Albania | MDS Shpk Albania |
| Beste Partners EOOD | CarRent | MiamiLifeCars | Miami Life Cars |
| BRent | BRent | Minoas | Minoas Rentals |
| Brimborg ehf. | Saga | MonteRental | Monte Rental d.o.o. |
| Canarias | Canarias | MontenegroCar | Montenegro Car |
| CAPITAL HOUSE d.o.o. | Driver | MONZA S.A. | Monza |
| Capital USA | Capital | MTL | MTL Rent a Car |
| Car Alliance | CarAlliance | NAVIS MOBIL d.o.o | Renty |
| Car1 | Car1 | Nolauto | Nolauto Alghero |
| CarFree | CarFree | Northern Lights Car Rental | Northern |
| Carwiz | Carwiz | Nova Rent a Car | Nova |
| City Rent Albania | CityRent | Paisagem Sugestiva Lda | RentX |
| Control d.o.o. | Control | Pandora | Pandora |
| Coys rent a car | Coys | Pricecarz | Pricecarz |
| demo | Demo | Quick Drive Rent A Car LLC | QuickDrive |
| Direct Rent | Direct | Radius LLC | Radius |
| Doffay Car Rental | Doffay | Rent Car Tbilisi | Tbilisi |
| Drive and Go | DriveandGo | RENTALUX SH.P.K | Rentalux |
| ECORent | EcoRent | SC Autorevolution SRL | ExpediCAR |
| Essence | Essence | SC Autorom Travel SRL | Autorom |
| Exclusive Cars | Exclusive | Schiller Rent | Schiller |
| Express Doprava s.r.o | STSRent | Sharr Express | SharrExpress |
| FRI RENT d.o.o. | Free2Rent | SMART RENT AND DRIVE SRL | DriveSmart |
| Friends Mobility | FriendsMobility | Specific Rent | Specific |
| TASLAK TRADE d.o.o. | XLRent | Sterling Rentals | SterlingRentals |
| TolisTravel | TolisTravel | Top Gear Mobility | Topgear |
| Trio Rent A Car | Trio | Tycoon LLC | Tycoon |
| UNI RENT | UNIRENT | Value Car Rental | Value |
| Vettura S.r.l. | Vettura | VITARENT SRL | Vitarent |
| Xenos Panagiotis | Xenos | YOUR RENT sp. z o.o | YourRent |
| Zippy7 Autorent GmbH | Zippy7 | | |

---

### 4. GET /api/setup/carCategories

**Description**: Returns all car category codes (SIPP - Standard Interline Passenger Procedure codes).

**Response Statistics**:
- **Total Categories**: 803 unique SIPP codes

**SIPP Code Breakdown by First Letter**:

| Letter | Category | Count |
|--------|----------|-------|
| S | Special | 81 |
| C | Compact | 79 |
| X | Special | 68 |
| I | Intermediate | 69 |
| E | Economy | 63 |
| F | Fullsize | 65 |
| D | Intermediate/Midsize | 30 |
| P | Premium | 41 |
| M | Mini | 42 |
| L | Premium | 47 |
| J | Sporty | 36 |
| G | Fullsize/Intermediate | 32 |
| O | Other | 45 |
| R | Standard | 27 |
| W | Wagon/Estate | 25 |
| U | Utility | 23 |
| H | Compact | 14 |
| N | Economy | 16 |
| T | Truck | 18 |
| V | Van | 12 |
| Y | 2-door | 15 |
| Q | Quad | 11 |
| Z | Special/Convertible | 10 |
| K | Commercial | 8 |
| B | Bus | 7 |
| A | Adventure | 5 |

**SIPP Code Structure**:
- **1st Letter**: Vehicle Category (M=Mini, E=Economy, C=Compact, I=Intermediate, S=Standard, F=Fullsize, P=Premium, etc.)
- **2nd Letter**: Doors/Size (B=2/3 door, C=2/4 door, D=4/5 door, etc.)
- **3rd Letter**: Transmission/Drive (M=Manual, A=Automatic)
- **4th Letter**: Fuel/AC (N=No AC, R=AC)

**Sample SIPP Codes**:
```
ECAR - Economy 2/4 door Automatic A/C
CCAR - Compact 2/4 door Automatic A/C
ICAR - Intermediate 2/4 door Automatic A/C
SCAR - Standard 2/4 door Automatic A/C
FCAR - Fullsize 2/4 door Automatic A/C
PCAR - Premium 2/4 door Automatic A/C
```

---

### 5. GET /api/setup/provider/{providerCode}

**Description**: Returns complete provider information including locations, services, offices, and working hours.

**Note**: Only works for providers you have access to. The "demo" provider works with current credentials.

**Example Response Structure** (for "demo" provider):

```json
{
    "Code": "demo",
    "Name": "Demo",
    "Countries": ["GR", "HR"],
    "LogoUrl": null,
    "Services": [...],
    "Locations": [...],
    "Offices": [...],
    "Currencies": "EUR,HRK,USD",
    "Languages": "EN,HR"
}
```

**Response Fields**:

| Field | Type | Description |
|-------|------|-------------|
| Code | string | Unique provider identifier |
| Name | string | Provider display name |
| Countries | array | List of country codes where provider operates |
| LogoUrl | string/null | Provider logo image URL |
| Services | array | List of available services for this provider |
| Locations | array | List of pickup/dropoff locations |
| Offices | array | List of office locations with details |
| Currencies | string | Accepted currencies (comma-separated) |
| Languages | string | Supported languages (comma-separated) |

**Office Object Structure**:

```json
{
    "OfficeCode": "ATHP",
    "OfficeId": 910,
    "LocationCode": "GR-ATH-PT",
    "Address": "Piraeus port BB",
    "Town": "Piraeus",
    "PostalCode": "18450",
    "Latitude": null,
    "Longitude": null,
    "Tel": "+302104172675",
    "Email": "piraeus-port@renteon.com",
    "IsPickupOffice": true,
    "IsDropOffOffice": true,
    "LocationType": "Port",
    "RegularWorkingTimes": [...],
    "HolidayWorkingTimes": [],
    "SpecialWorkingTimes": []
}
```

**Working Times Structure**:

```json
{
    "DayOfWeekIndex": 0,
    "DayOfWeekName": "Sunday",
    "HourFrom": 8,
    "MinuteFrom": 0,
    "HourTo": 20,
    "MinuteTo": 0,
    "HourFrom2": null,
    "MinuteFrom2": null,
    "HourTo2": null,
    "MinuteTo2": null,
    "IsWorking": true,
    "IsWorkingNonStop": null
}
```

---

## Data Catalog

### Master Data Summary

| Data Type | Source | Count | Endpoint |
|-----------|--------|-------|----------|
| Locations | Global | 1,521 | /api/setup/locations |
| Services | Global | 100 | /api/setup/services |
| Providers | Global | 92 | /api/setup/providers |
| Car Categories | Global | 803 | /api/setup/carCategories |
| Provider Details | Per Provider | Varies | /api/setup/provider/{code} |

### Data Relationships

```
Providers (92)
    │
    ├──→ Countries (58 total)
    │
    ├──→ Locations (1,521)
    │     │
    │     └──→ Offices (with working hours, contact info)
    │
    ├──→ Services (100)
    │     ├──→ Equipment (23)
    │     ├──→ Fees (27)
    │     ├──→ Insurance (35)
    │     └──→ Other (15)
    │
    └──→ Car Categories (803 SIPP codes)
```

---

## SIPP Car Categories Reference

### Category Letters (First Position)

| Code | Category | Examples |
|------|----------|----------|
| M | Mini | MBAR, MBMR, MCMR |
| N | Economy | NDAR, NTAR, NTMR |
| E | Economy | ECAR, ECAV, ECMR |
| H | Compact | HDMR, HQMD |
| C | Compact | CCAR, CCMR, CDAR |
| D | Intermediate/Midsize | DDAR, DDMR, DFAR |
| S | Standard | SCAR, SDAR, SFAR |
| I | Intermediate | ICAR, IDAR, IFAR |
| R | Standard | RDAR, RFAR |
| F | Fullsize | FCAR, FDAR, FFAR |
| P | Premium | PCAR, PDAR, PFAR |
| L | Premium | LDAR, LFAR, LVAR |
| J | Sporty | JDAR, JDMR, JFMR |
| X | Special | XCAR, XDAR, XFAR |
| W | Wagon/Estate | WDAR, WFAR, WVMR |
| V | Van | VVAR, VVMR |
| O | Other | OVAR, OVMR |
| U | Utility | UDAR, UFAR |
| T | Truck | DTAR, ETAR |
| Y | 2-door | CYMZ, EYMZ |
| Z | Special/Convertible | FYAZ, NYAZ |
| Q | Quad | OQDV, OQMV |
| K | Commercial | CKMD, CKMR |
| B | Bus | DBV, FBV |
| A | Adventure | DYMZ, HYMZ |

### Door Configuration (Second Position)

| Code | Description |
|------|-------------|
| B | 2/3 door |
| C | 2/4 door |
| D | 4/5 door |
| V | Van/MPV |
| S | Sport/Convertible |
| W | Wagon |
| L | Limousine |
| T | Truck |
| F | Four wheel drive |
| P | Pickup |
| N | Unknown/Not specified |

### Transmission (Third Position)

| Code | Description |
|------|-------------|
| M | Manual |
| A | Automatic |
| C | Semi-automatic |
| N | Not specified |

### Fuel/AC (Fourth Position)

| Code | Description |
|------|-------------|
| N | No A/C |
| R | A/C |
| D | Diesel |
| V | Electric/Hybrid |
| L | LPG |
| S | Hybrid |

---

## Configuration Examples

### Laravel Configuration

**.env file**:
```env
RENTEON_USERNAME=vrooem.agg.api
RENTEON_PASSWORD=T45h-G11!r$jj76
RENTEON_BASE_URL=https://aggregator.renteon.com
RENTEON_PROVIDER_CODE=demo
```

**config/services.php**:
```php
'renteon' => [
    'username' => env('RENTEON_USERNAME'),
    'password' => env('RENTEON_PASSWORD'),
    'base_url' => env('RENTEON_BASE_URL'),
    'provider_code' => env('RENTEON_PROVIDER_CODE', 'demo'),
],
```

### PHP Example Using Laravel HTTP Client

```php
use Illuminate\Support\Facades\Http;

class RenteonService
{
    private $baseUrl;
    private $username;
    private $password;

    public function __construct()
    {
        $this->baseUrl = config('services.renteon.base_url');
        $this->username = config('services.renteon.username');
        $this->password = config('services.renteon.password');
    }

    public function getLocations()
    {
        return Http::withBasicAuth($this->username, $this->password)
            ->timeout(30)
            ->acceptJson()
            ->get("{$this->baseUrl}/api/setup/locations")
            ->json();
    }

    public function getServices()
    {
        return Http::withBasicAuth($this->username, $this->password)
            ->timeout(30)
            ->acceptJson()
            ->get("{$this->baseUrl}/api/setup/services")
            ->json();
    }

    public function getProviders()
    {
        return Http::withBasicAuth($this->username, $this->password)
            ->timeout(30)
            ->acceptJson()
            ->get("{$this->baseUrl}/api/setup/providers")
            ->json();
    }

    public function getCarCategories()
    {
        return Http::withBasicAuth($this->username, $this->password)
            ->timeout(30)
            ->acceptJson()
            ->get("{$this->baseUrl}/api/setup/carCategories")
            ->json();
    }

    public function getProviderDetails($providerCode)
    {
        return Http::withBasicAuth($this->username, $this->password)
            ->timeout(30)
            ->acceptJson()
            ->get("{$this->baseUrl}/api/setup/provider/{$providerCode}")
            ->json();
    }
}
```

---

## Authentication Notes

**Important**: The Renteon API requires proper authentication configuration:
- Direct curl commands may return 401 Unauthorized
- Laravel's HTTP client with `withBasicAuth()` works correctly
- Ensure proper headers are set (Accept: application/json)

---

## Next Steps for Integration

### Still Needed (Not Yet Available)

The following endpoints need to be obtained from Renteon:
1. **Vehicle Availability/Search** - Search for available cars by dates/locations
2. **Vehicle Details** - Get vehicle specs, features, images
3. **Pricing/Rates** - Get rental rates for specific vehicles
4. **Booking Creation** - Create new reservations
5. **Booking Management** - View/modify/cancel bookings
6. **Vehicle Images** - Get vehicle photos
7. **Terms & Conditions** - Provider-specific rental terms

### Recommended Actions

1. Contact Renteon support for complete API documentation
2. Obtain API keys for production/booking endpoints
3. Request sandbox/testing environment for full integration
4. Get webhook/documentation for booking status updates

---

## Support & Contact

- **API Base URL**: https://aggregator.renteon.com
- **Documentation**: (Request from Renteon)
- **Support Email**: (Request from Renteon)

---

**Document Version**: 1.0
**Last Updated**: December 23, 2025
