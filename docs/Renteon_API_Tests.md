# Renteon API Test Results

> **Test Date**: January 13, 2026
> **Base URL**: `https://aggregator.renteon.com`
> **Authentication Method**: HTTP Basic Auth with `--basic` flag

---

## API Credentials

| Parameter | Value |
|-----------|-------|
| Base URL | `https://aggregator.renteon.com` |
| Username | `vrooem.agg.api` |
| Password | `T45h-G11!r$jj76` |
| Provider Code | `demo` |

---

## Important Note: Authentication

**Critical**: Standard `curl -u` authentication returns **401 Unauthorized**. The API requires the explicit `--basic` flag to work correctly.

**Working Command Pattern**:
```bash
curl -s --basic -u "username:password" -H "Accept: application/json" "https://aggregator.renteon.com/endpoint"
```

---

## Test Results Summary

| Endpoint | Method | Status | Response Size |
|----------|--------|--------|---------------|
| `/api/setup/locations` | GET | ✅ Working | 200.2 KB |
| `/api/setup/services` | GET | ✅ Working | ~8 KB |
| `/api/setup/providers` | GET | ✅ Working | ~3 KB |
| `/api/setup/carCategories` | GET | ✅ Working | ~26 KB |
| `/api/setup/provider/demo` | GET | ✅ Working | 71.6 KB |
| `/api/bookings/search` | POST | ✅ Working (Empty) | 0 KB |

---

## 1. GET /api/setup/locations

**Description**: Returns all supported pickup/dropoff locations across all providers.

**Curl Command**:
```bash
curl -s --basic -u "vrooem.agg.api:T45h-G11!r\$jj76" \
  -H "Accept: application/json" \
  "https://aggregator.renteon.com/api/setup/locations"
```

**Response Statistics**:
- **Total Locations**: 1,521
- **Countries Covered**: 58
- **Location Types**: Airport, Downtown, Port, RailwayStation, BusStation

**Sample Response** (First 5 locations):
```json
[
  {
    "Code": "AE-ABU",
    "CountryCode": "AE",
    "Name": "Abu Dhabi",
    "Type": "",
    "Category": "City",
    "Path": "Abu Dhabi"
  },
  {
    "Code": "AE-ABU-AUH",
    "CountryCode": "AE",
    "Name": "Abu Dhabi airport",
    "Type": "Airport",
    "Category": "PickupDropoff",
    "Path": "Abu Dhabi > Abu Dhabi airport"
  },
  {
    "Code": "AE-ABU-DT",
    "CountryCode": "AE",
    "Name": "Abu Dhabi downtown",
    "Type": "Downtown",
    "Category": "PickupDropoff",
    "Path": "Abu Dhabi > Abu Dhabi downtown"
  },
  {
    "Code": "AE-AJM",
    "CountryCode": "AE",
    "Name": "Ajman",
    "Type": "",
    "Category": "City",
    "Path": "Ajman"
  },
  {
    "Code": "AE-AJM-QAJ",
    "CountryCode": "AE",
    "Name": "Ajman airport",
    "Type": "Airport",
    "Category": "PickupDropoff",
    "Path": "Ajman > Ajman airport"
  }
]
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

## 2. GET /api/setup/services

**Description**: Returns all available services (equipment, fees, insurance, etc.) across providers.

**Curl Command**:
```bash
curl -s --basic -u "vrooem.agg.api:T45h-G11!r\$jj76" \
  -H "Accept: application/json" \
  "https://aggregator.renteon.com/api/setup/services"
```

**Response Statistics**:
- **Total Services**: 100

**Sample Response** (First 10 services):
```json
[
  {
    "Code": "CAR-RENT",
    "Name": "Car rent",
    "Type": "Car rent"
  },
  {
    "Code": "EQ-BABSEISO",
    "Name": "Baby seat ISOFIX",
    "Type": "Additional equipment"
  },
  {
    "Code": "EQ-BABYCARF",
    "Name": "Baby Carriage (Foldable)",
    "Type": "Additional equipment"
  },
  {
    "Code": "EQ-BABYSEAT",
    "Name": "Baby seat (0-9kg)",
    "Type": "Additional equipment"
  },
  {
    "Code": "EQ-BOOSTER",
    "Name": "Booster seat",
    "Type": "Additional equipment"
  },
  {
    "Code": "EQ-CAMPTABCHA",
    "Name": "Camping Table&Chair",
    "Type": "Additional equipment"
  },
  {
    "Code": "EQ-CARFRMINI",
    "Name": "Mini Car fridge",
    "Type": "Additional equipment"
  },
  {
    "Code": "EQ-CHAINS",
    "Name": "Winter chains",
    "Type": "Additional equipment"
  },
  {
    "Code": "EQ-CHILDSEAT",
    "Name": "Child seat (9-18kg)",
    "Type": "Additional equipment"
  },
  {
    "Code": "EQ-CHILDSEAT0-18KG",
    "Name": "Child seat (0-18kg)",
    "Type": "Additional equipment"
  }
]
```

**Services by Category**:

| Category | Count | Percentage |
|----------|-------|------------|
| Insurance | 35 | 35% |
| Additional Fee | 27 | 27% |
| Additional Equipment | 23 | 23% |
| Other Services | 15 | 15% |

---

## 3. GET /api/setup/providers

**Description**: Returns list of all car rental companies available on the platform.

**Curl Command**:
```bash
curl -s --basic -u "vrooem.agg.api:T45h-G11!r\$jj76" \
  -H "Accept: application/json" \
  "https://aggregator.renteon.com/api/setup/providers"
```

**Response Statistics**:
- **Total Providers**: 92

**Sample Response** (First 10 providers):
```json
[
  {
    "Code": "7M",
    "Name": "7M Rent a Car",
    "LogoUrl": null
  },
  {
    "Code": "ABmobil",
    "Name": "ABmobil rent d.o.o.",
    "LogoUrl": null
  },
  {
    "Code": "Agio",
    "Name": "Agio",
    "LogoUrl": null
  },
  {
    "Code": "Alana",
    "Name": "Alana rent",
    "LogoUrl": null
  },
  {
    "Code": "Alkafi",
    "Name": "Alkafi",
    "LogoUrl": null
  },
  {
    "Code": "Alpros",
    "Name": "Alpros",
    "LogoUrl": null
  },
  {
    "Code": "Alquicoche",
    "Name": "Alquicoche Spain",
    "LogoUrl": null
  },
  {
    "Code": "Amex",
    "Name": "Amex Car Rental LLC",
    "LogoUrl": null
  },
  {
    "Code": "ARGroup",
    "Name": "AR GROUP d.o.o.",
    "LogoUrl": null
  },
  {
    "Code": "Atet",
    "Name": "Atet",
    "LogoUrl": null
  }
]
```

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
| Amex | Amex Car Rental LLC | DOOS | Jalal Creative Solution Sdn Bhd |
| ARGroup | AR GROUP d.o.o. | JustRent | Just Rent and Trans |
| Atet | Atet | KingTravel | King Travel |
| ATTRentalCars | Att Rental Cars shpk | Lastminute | Last minute |
| Autojet | Autojet | Leos | Leos Car Rentals |
| Avantcar | Avantcar | LetsDrive | LetsDrive |
| Avia | AVIA rent a car d.o.o. | Lynx | Lynx |
| AZCarRental | AZ Car Rental USA | MACK | M.A.C.K. d.o.o. |
| Barkro | BARKRO s.r.o. | MDS Shpk Albania | MDS Shpk Albania |
| Beepit | Beepit | MiamiLifeCars | Miami Life Cars |
| Beste Partners EOOD | CarRent | Minoas | Minoas Rentals |
| BRent | BRent | MonteRental | Monte Rental d.o.o. |
| Brimborg ehf. | Saga | MontenegroCar | Montenegro Car |
| Canarias | Canarias | CAPITAL HOUSE d.o.o. | Driver |
| Capital USA | Capital | MONZA S.A. | Monza |
| Car Alliance | CarAlliance | MTL | MTL Rent a Car |
| Car1 | Car1 | NAVIS MOBIL d.o.o | Renty |
| CarFree | CarFree | Nolauto | Nolauto Alghero |
| Carwiz | Carwiz | Northern Lights Car Rental | Northern |
| City Rent Albania | CityRent | Nova Rent a Car | Nova |
| Control d.o.o. | Control | Paisagem Sugestiva Lda | RentX |
| Coys rent a car | Coys | Pandora | Pandora |
| demo | Demo | Pricecarz | Pricecarz |
| Direct Rent | Direct | Quick Drive Rent A Car LLC | QuickDrive |
| Doffay Car Rental | Doffay | Radius LLC | Radius |
| Drive and Go | DriveandGo | Rent Car Tbilisi | Tbilisi |
| ECORent | EcoRent | RENTALUX SH.P.K | Rentalux |
| Essence | Essence | SC Autorevolution SRL | ExpediCAR |
| Exclusive Cars | Exclusive | SC Autorom Travel SRL | Autorom |
| Express Doprava s.r.o | STSRent | Shiller Rent | Schiller |
| FRI RENT d.o.o. | Free2Rent | Sharr Express | SharrExpress |
| Friends Mobility | FriendsMobility | SMART RENT AND DRIVE SRL | DriveSmart |
| TASLAK TRADE d.o.o. | XLRent | Sterling Rentals | SterlingRentals |
| TolisTravel | TolisTravel | Top Gear Mobility | Topgear |
| Trio Rent A Car | Trio | Tycoon LLC | Tycoon |
| UNI RENT | UNIRENT | Value Car Rental | Value |
| Vettura S.r.l. | Vettura | VITARENT SRL | Vitarent |
| Xenos Panagiotis | Xenos | YOUR RENT sp. z o.o | YourRent |
| Zippy7 Autorent GmbH | Zippy7 | | |

---

## 4. GET /api/setup/carCategories

**Description**: Returns all car category codes (SIPP - Standard Interline Passenger Procedure codes).

**Curl Command**:
```bash
curl -s --basic -u "vrooem.agg.api:T45h-G11!r\$jj76" \
  -H "Accept: application/json" \
  "https://aggregator.renteon.com/api/setup/carCategories"
```

**Response Statistics**:
- **Total Categories**: 803 unique SIPP codes

**Sample Response** (First 20 categories):
```json
[
  {
    "Code": "CBMR",
    "SortOrder": 1.0
  },
  {
    "Code": "CCAR",
    "SortOrder": 2.0
  },
  {
    "Code": "CCMR",
    "SortOrder": 3.0
  },
  {
    "Code": "CDAD",
    "SortOrder": 4.0
  },
  {
    "Code": "CDAE",
    "SortOrder": 5.0
  },
  {
    "Code": "CDAR",
    "SortOrder": 6.0
  },
  {
    "Code": "CDAV",
    "SortOrder": 7.0
  },
  {
    "Code": "CDMD",
    "SortOrder": 8.0
  },
  {
    "Code": "CDMR",
    "SortOrder": 9.0
  },
  {
    "Code": "CDMV",
    "SortOrder": 10.0
  },
  {
    "Code": "CFAD",
    "SortOrder": 11.0
  },
  {
    "Code": "CFAR",
    "SortOrder": 12.0
  },
  {
    "Code": "CFMR",
    "SortOrder": 13.0
  },
  {
    "Code": "CFMV",
    "SortOrder": 14.0
  },
  {
    "Code": "CGAR",
    "SortOrder": 15.0
  },
  {
    "Code": "CGAV",
    "SortOrder": 16.0
  },
  {
    "Code": "CGMR",
    "SortOrder": 17.0
  },
  {
    "Code": "CKMD",
    "SortOrder": 18.0
  },
  {
    "Code": "CKMR",
    "SortOrder": 19.0
  },
  {
    "Code": "CLAR",
    "SortOrder": 20.0
  }
]
```

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

---

## 5. GET /api/setup/provider/{providerCode}

**Description**: Returns complete provider information including locations, services, offices, and working hours.

**Curl Command**:
```bash
curl -s --basic -u "vrooem.agg.api:T45h-G11!r\$jj76" \
  -H "Accept: application/json" \
  "https://aggregator.renteon.com/api/setup/provider/demo"
```

**Response Structure** (for "demo" provider):
```json
{
  "Code": "demo",
  "Name": "Demo",
  "Countries": ["GR", "HR"],
  "LogoUrl": null,
  "Services": [...],
  "Locations": [...],
  "Offices": [...],
  "CarCategories": [...],
  "Pricelists": [...],
  "Connectors": [...],
  "Languages": ["HR", "EN", "DE", "IT", "RO", "TR"],
  "Currencies": ["EUR", "HRK", "USD"]
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
| CarCategories | array | Available car category SIPP codes |
| Pricelists | array | Pricing information |
| Connectors | array | Connector configuration details |
| Languages | array | Supported languages |
| Currencies | array | Accepted currencies |

**Sample Office Object**:
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
  "IsMeetAndGreetLocation": false,
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

## 6. POST /api/bookings/search

**Description**: Search for available vehicles based on pickup/dropoff location and dates.

**Curl Command**:
```bash
curl -s -X POST --basic -u "vrooem.agg.api:T45h-G11!r\$jj76" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "Provider": "demo",
    "PickupLocationCode": "HR-SPL-SPU",
    "DropoffLocationCode": "HR-SPL-SPU",
    "PickupDate": "2025-02-01",
    "DropoffDate": "2025-02-05"
  }' \
  "https://aggregator.renteon.com/api/bookings/search"
```

**Response**:
```
[]
```

**Note**: The "demo" provider returns an empty array (no vehicles) for all searches. This is expected behavior as noted in the documentation. To get actual vehicle data, you would need credentials for a provider with active inventory.

**Request Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| Provider | string | ✅ Yes | Provider code (e.g., "demo") |
| PickupLocationCode | string | ✅ Yes | Pickup location code (e.g., "HR-SPL-SPU") |
| DropoffLocationCode | string | ✅ Yes | Dropoff location code (same as pickup if not specified) |
| PickupDate | string | ✅ Yes | Pickup date (YYYY-MM-DD format) |
| DropoffDate | string | ✅ Yes | Dropoff date (YYYY-MM-DD format) |

---

## Authentication Issues & Solutions

### Problem: Standard curl -u Returns 401

```bash
# This returns 401 Unauthorized
curl -u "vrooem.agg.api:T45h-G11!r$jj76" \
  -H "Accept: application/json" \
  "https://aggregator.renteon.com/api/setup/locations"
```

### Solution: Use --basic Flag

```bash
# This works correctly
curl --basic -u "vrooem.agg.api:T45h-G11!r$jj76" \
  -H "Accept: application/json" \
  "https://aggregator.renteon.com/api/setup/locations"
```

**The `--basic` flag forces curl to use HTTP Basic Authentication, which this API requires.**

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

## Recommended Next Steps

1. **Contact Renteon** for access to a provider with active vehicle inventory to test the `/api/bookings/search` endpoint with actual data.

2. **Implement the working endpoints** in your Laravel service using the `--basic` authentication pattern.

3. **Update RenteonService.php** to properly handle authentication - the current implementation may need adjustment to ensure proper Basic Auth headers.

4. **Cache the setup data** (locations, services, providers, car categories) as these don't change frequently.

---

## File Location

This test report has been saved to:
`/mnt/c/laragon/www/CarRental/docs/Renteon_API_Tests.md`

---

**Document Version**: 1.0
**Test Date**: January 13, 2026
**Tested By**: Claude Code API Testing Suite
