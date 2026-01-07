# Adobe Cars API Documentation

## Overview

Adobe Rent a Car provides a REST API for vehicle rental operations in Costa Rica.

**Base URL**: `https://adobecar.cr:42800`

**API Type**: RESTful JSON

**Authentication**: Bearer Token

**Test Credentials**:
- Username: `Z11338`
- Password: `11338`
- Customer Code: `Z11338`

---

## Authentication

### POST /Auth/Login

Obtains an access token required for all subsequent API calls.

**Request**:
```json
POST https://adobecar.cr:42800/Auth/Login
Content-Type: application/json

{
    "userName": "Z11338",
    "password": "11338"
}
```

**Response**:
```json
{
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Notes**:
- Token is valid for 60 minutes
- Recommended to cache token for 55 minutes
- Include token in subsequent requests via `Authorization: Bearer {token}` header

---

## Endpoints

### 1. GET /Offices

Retrieves all office locations.

**Request**:
```http
GET https://adobecar.cr:42800/Offices
Authorization: Bearer {token}
```

**Response**: Array of office objects

**Office Object Structure**:
```json
{
    "code": "OCO",
    "name": "Aeropuerto San Jose [SJO]",
    "schedule": ["05:00", "22:00"],
    "telephones": ["506 2442 2422"],
    "coordinates": ["10.004130", "-84.185504"],
    "address": "Adobe Rent a Car, Route 3, Río Segundo, Alajuela",
    "deploymentName": "SJO - Juan Santamaria Int. Airport",
    "order": 1,
    "visible": true,
    "atAirport": true,
    "region": "GAM"
}
```

**Available Offices** (16 total):
| Code | Name | Region | Airport |
|------|------|--------|---------|
| OCO | San Jose [SJO] | GAM | Yes |
| LIB | Liberia [LIR] | Guanacaste | Yes |
| SJO | San Jose Centro | GAM | No |
| SJ1 | San Pedro | GAM | No |
| CAR | Cartago | GAM | No |
| HRD | Heredia Centro | GAM | No |
| GRE | Grecia | GAM | No |
| CQ | Ciudad Quesada | Norte | No |
| LIM | Limón | Caribe | No |
| XQP | Quepos / Manuel Antonio | Pacifico Central | No |
| TNO | Tamarindo / Flamingo | Guanacaste | No |
| UVT | Uvita | Pacifico Sur | No |
| HFT | Puntarenas | Pacifico Central | No |
| PTV | Puerto Viejo | Caribe | No |
| GUA | Guapiles | Caribe | No |
| LDT | Liberia Centro | Guanacaste | No |

---

### 2. GET /Client/GetAvailabilityWithPrice

Retrieves available vehicles for given location and dates.

**Request**:
```http
GET https://adobecar.cr:42800/Client/GetAvailabilityWithPrice
Authorization: Bearer {token}

Query Parameters:
- pickupOffice: string (required) - Office code
- returnOffice: string (required) - Office code
- startDate: string (required) - Format: "YYYY-MM-DD HH:MM"
- endDate: string (required) - Format: "YYYY-MM-DD HH:MM"
- customerCode: string (required) - "Z11338"
- promotionCode: string (optional) - Promo code
```

**Important**: Date format MUST include time component: `YYYY-MM-DD HH:MM`

**Example Request**:
```http
GET /Client/GetAvailabilityWithPrice?pickupOffice=OCO&returnOffice=OCO&startDate=2026-03-11+10:00&endDate=2026-03-20+10:00&customerCode=Z11338
```

**Response**: Array of vehicle objects

**Vehicle Object Structure**:
```json
{
    "category": "n",
    "model": "Kia Picanto AT or similar",
    "photo": "https://adobe-media.s3.amazonaws.com/mail/cotizador/n.jpg",
    "passengers": 4,
    "manual": true,
    "doors": 4,
    "type": "SEDAN",
    "traction": "4X2",
    "order": 1,
    "pli": 122.04,
    "ldw": 15.82,
    "spp": 20.34,
    "tdr": 607.14,
    "dro": 0
}
```

**Field Descriptions**:
| Field | Type | Description |
|-------|------|-------------|
| category | string | Vehicle category code (n, w, p, i, d, m, q, a, r, l, b, f, v, h, j, e, g) |
| model | string | Vehicle model name |
| photo | string | URL to vehicle image |
| passengers | int | Number of passengers |
| manual | boolean | true = manual transmission, false = automatic |
| doors | int | Number of doors |
| type | string | Vehicle type (SEDAN, SUV, VAN) |
| traction | string | 4X2, 4X4, or 4x2 |
| order | int | Display order |
| pli | float | Liability Protection price |
| ldw | float | Car Protection (LDW) price |
| spp | float | Extended Protection (SPP) price |
| tdr | float | Total daily rate |
| dro | float | Drop-off fee (usually 0) |

**Vehicle Categories** (17 total):
| Code | Model | Passengers | Type | Transmission | Traction |
|------|-------|------------|------|--------------|----------|
| n | Kia Picanto | 4 | SEDAN | Manual | 4X2 |
| w | Hyundai Grand i10 | 4 | SEDAN | Auto | 4X2 |
| p | Kia Soluto | 4 | SEDAN | Manual | 4X2 |
| i | Kia Soluto | 4 | SEDAN | Auto | 4X2 |
| d | Hyundai Accent | 5 | SEDAN | Auto | 4X2 |
| m | Geely GX3 | 4 | SUV | Manual | 4X2 |
| q | Jetour X50 | 4 | SUV | Manual | 4X2 |
| a | Hyundai Venue | 4 | SUV | Auto | 4X2 |
| r | Hyundai Creta | 4 | SUV | Auto | 4X2 |
| l | Suzuki Jimmy | 4 | SUV | Auto | 4X4 |
| b | Suzuki Vitara | 4 | SUV | Auto | 4X4 |
| f | Hyundai Kona | 5 | SUV | Auto | 4X4 |
| v | Jetour X70 | 7 | SUV | Manual | 4x2 |
| h | Hyundai Tucson | 5 | SUV | Auto | 4X4 |
| j | Hyundai Santa Fe | 5 | SUV | Auto | 4X4 |
| e | Hyundai Staria | 7 | VAN | Auto | 4X2 |
| g | Mitsubishi Montero | 5 | SUV | Auto | 4X4 |

---

### 3. GET /Client/GetCategoryWithFare

Retrieves protections and extras for a specific vehicle category and dates.

**Request**:
```http
GET https://adobecar.cr:42800/Client/GetCategoryWithFare
Authorization: Bearer {token}

Query Parameters:
- pickupOffice: string (required)
- returnOffice: string (required)
- category: string (required) - Vehicle category code
- startDate: string (required) - Format: "YYYY-MM-DD HH:MM"
- endDate: string (required) - Format: "YYYY-MM-DD HH:MM"
- customerCode: string (required) - "Z11338"
- idioma: string (required) - "en" for English
```

**Response**:
```json
{
    "items": [
        {
            "code": "PLI",
            "name": "Liability Protection",
            "type": "Proteccion",
            "description": "Mandatory insurance with limited coverage...",
            "quantity": 0,
            "total": 213.57,
            "order": 2,
            "included": false,
            "required": true,
            "information": ""
        }
    ]
}
```

**Protections** (Proteccion):
| Code | Name | Required | Description |
|------|------|----------|-------------|
| PLI | Liability Protection | Yes | Mandatory insurance - third party property damage, $1130 deductible |
| LDW | Car Protection | No | Collision/roll-over/theft protection - $1,130 deductible |
| SPP | Extended Protection | No | Zero deductible, $2M coverage, vandalism/tires/assistance included |

**Extras** (Adicionales):
| Code | Name | Price | Description |
|------|------|-------|-------------|
| BOO | Booster Seat | $10.17 | For children under 12 |
| BSS | Baby Seat | $30.51 | Baby seat |
| GPS | GPS | $91.53 | GPS navigation |
| WIF | WiFi Router | $101.70 | Mobile WiFi hotspot |
| RAK | Roof Racks | $0 | Roof racks (free) |
| HIE | Cooler | $0 | Free cooler (subject to availability) |
| CAD | (unnamed) | $40.68 | - |

**Item Object Fields**:
| Field | Type | Description |
|-------|------|-------------|
| code | string | Item code |
| name | string | Display name |
| type | string | "Proteccion" or "Adicionales" |
| description | string | Detailed description |
| quantity | int | Quantity selected |
| total | float | Total price for rental period |
| order | int | Display order |
| included | boolean | Whether included in base rate |
| required | boolean | Whether mandatory (PLI is required=true) |
| information | string | Additional info |

---

### 4. POST /Booking

Creates a new booking.

**Request**:
```json
POST https://adobecar.cr:42800/Booking
Authorization: Bearer {token}

{
    "pickupOffice": "OCO",
    "returnOffice": "OCO",
    "pickupDate": "2026-03-11 10:00",
    "returnDate": "2026-03-20 10:00",
    "category": "ECMR",
    "customerCode": "Z11338",
    "customerName": "Test Customer",
    "flightNumber": "",
    "comment": "API Test"
}
```

**Response** (Success):
```json
{
    "result": true,
    "data": {
        "bookingNumber": "ABC12345"
    }
}
```

**Response** (Error):
```json
{
    "result": false,
    "error": "Invalid date"
}
```

**Note**: Date format must be `YYYY-MM-DD HH:MM`. Missing time component causes error.

---

### 5. GET /Booking

Retrieves booking details.

**Request**:
```http
GET https://adobecar.cr:42800/Booking?bookingNumber={bookingNumber}&customerCode=Z11338
Authorization: Bearer {token}
```

**Response**: Booking details object with selected protections and extras

---

## API Characteristics

### Date Format Requirements
**CRITICAL**: All date parameters MUST include time component in format: `YYYY-MM-DD HH:MM`

Examples:
- Correct: `2026-03-11 10:00`
- Incorrect: `2026-03-11` (will return "Invalid date" error)

URL encode the space as `+` or `%20`:
- `2026-03-11+10:00` or `2026-03-20%2010:00`

### HTTP Methods
- Authentication: `POST`
- Offices: `GET`
- Vehicle Availability: `GET`
- Protections/Extras: `GET`
- Create Booking: `POST`
- Get Booking Details: `GET`

### Error Handling
Common errors:
| HTTP Code | Error | Cause |
|-----------|-------|-------|
| 401 | Unauthorized | Invalid/expired token |
| 405 | Method Not Allowed | Wrong HTTP method |
| 500 | Invalid date | Wrong date format (missing time) |

### Pricing Fields Explained
| Field | Full Name | Description |
|-------|-----------|-------------|
| PLI | Protection Liability Insurance | Mandatory third-party liability |
| LDW | Loss Damage Waiver | Collision/theft protection with deductible |
| SPP | Super Protection Package | Full coverage with zero deductible |
| TDR | Total Daily Rate | Base rate per day |
| DRO | Drop-off | One-way fee (usually 0 for same location) |

---

## Service Integration

**Laravel Service**: `App\Services\AdobeCarService`

**Key Methods**:
- `getAccessToken()` - Cached token retrieval (55 min)
- `getOfficeList()` - Get all locations
- `getAvailableVehicles($params)` - Search vehicles
- `getProtectionsAndExtras($location, $category, $dates)` - Get add-ons
- `getBookingDetails($bookingNumber)` - Retrieve booking info
- `updateAdobeDetailsJson($location, $data)` - Cache vehicle data
- `getCachedVehicleData($location, $ttl)` - Retrieve cached data

**Caching**: Uses `abobecardetails.json` in public path for vehicle data caching

---

## Test Output Files

The following JSON files contain actual API responses for reference:

- `tests/api/adobe_offices.json` - 16 office locations
- `tests/api/adobe_availability.json` - 17 vehicle categories
- `tests/api/adobe_category.json` - Protections and extras (10 items)
- `tests/api/adobe_booking.json` - Booking attempt results
- `tests/api/adobe_booking_details.json` - Booking details

---

## Usage Notes

1. **Always include time in dates** - API rejects dates without time component
2. **Token caching** - Cache token for 55 minutes to reduce login calls
3. **Customer code** - Always use `Z11338` for this integration
4. **Language** - Set `idioma=en` for English responses in GetCategoryWithFare
5. **Office codes** - Use codes from `/Offices` endpoint (OCO, LIB, etc.)
6. **Vehicle categories** - Use lowercase single-letter codes (n, w, p, etc.)
