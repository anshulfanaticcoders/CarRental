# Adobe Car Rental API Documentation - Complete Endpoint Reference

## Overview
This document provides comprehensive documentation for all Adobe Car Rental API endpoints. All APIs have been tested and verified working.

**Base URL:** `https://adobecar.cr:42800`
**Authentication:** Bearer Token (required for all endpoints except Auth/Login)
**Protocol:** HTTPS only

## Authentication Flow

### 1. POST /Auth/Login - Get Bearer Token
**Endpoint:** `POST https://adobecar.cr:42800/Auth/Login`

**Request Body:**
```json
{
  "userName": "Z11338",
  "password": "11338"
}
```

**Sample Request:**
```bash
curl -X POST https://adobecar.cr:42800/Auth/Login \
  -H "Content-Type: application/json" \
  -d '{"userName": "Z11338", "password": "11338"}' \
  -k
```

**Sample Response:**
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJuaWNrbmFtZSI6IkFkbWluIiwibmFtZSI6IloxMTMzOCIsImp0aSI6Ij...[truncated]...",
  "expiration": "2025-11-28T11:42:44Z"
}
```

## Office Locations

### 2. GET /Offices - Get Office List
**Endpoint:** `GET https://adobecar.cr:42800/Offices`

**Headers Required:**
```
Authorization: Bearer <token>
```

**Sample Request:**
```bash
curl -X GET "https://adobecar.cr:42800/Offices" \
  -H "Authorization: Bearer <your-token-here>" \
  -k
```

## Vehicle Availability & Pricing

### 3. GET /Client/GetAvailabilityWithPrice - Get Available Vehicles
**Endpoint:** `GET https://adobecar.cr:42800/Client/GetAvailabilityWithPrice`

**Required Parameters:**
- `pickupoffice`: Office code (e.g., "SJO")
- `returnoffice`: Office code
- `startdate`: Date in format "YYYY-MM-DD HH:MM" (e.g., "2025-12-15 09:00")
- `enddate`: Date in format "YYYY-MM-DD HH:MM" (e.g., "2025-12-18 09:00")
- `customerCode`: "Z11338"

**Optional Parameters:**
- `promotionCode`: Discount code

**Sample Request:**
```bash
curl -X GET "https://adobecar.cr:42800/Client/GetAvailabilityWithPrice?pickupoffice=SJO&returnoffice=SJO&startdate=2025-12-15%2009:00&enddate=2025-12-18%2009:00&customerCode=Z11338" \
  -H "Authorization: Bearer <your-token-here>" \
  -k
```

**Sample Response:**
```json
[
  {
    "category": "n",
    "pli": 40.68,
    "ldw": 15.82,
    "spp": 20.34,
    "model": "Suzuki Swift Dzire ST or similar",
    "photo": "https://adobe-media.s3.amazonaws.com/mail/cotizador/n.jpg",
    "passengers": 4,
    "manual": true,
    "tdr": 80.31,
    "dro": 0.00,
    "type": "SEDAN",
    "order": 1,
    "traction": "4X2",
    "doors": 4
  },
  {
    "category": "w",
    "pli": 40.68,
    "ldw": 15.82,
    "spp": 20.34,
    "model": "Hyundai Grand i10 AT or similar",
    "photo": "https://adobe-media.s3.amazonaws.com/mail/cotizador/w.jpg",
    "passengers": 4,
    "manual": false,
    "tdr": 90.43,
    "dro": 0.00,
    "type": "SEDAN",
    "order": 2,
    "traction": "4X2",
    "doors": 4
  }
]
```

## Vehicle Protections & Extras

### 4. GET /Client/GetCategoryWithFare - Get Protections & Extras
**Endpoint:** `GET https://adobecar.cr:42800/Client/GetCategoryWithFare`

**Required Parameters:**
- `pickupoffice`: Office code (e.g., "SJO")
- `returnoffice`: Office code
- `category`: Vehicle category (e.g., "n", "w", "p", etc.)
- `startdate`: Date in format "YYYY-MM-DD HH:MM"
- `enddate`: Date in format "YYYY-MM-DD HH:MM"
- `customerCode`: "Z11338"
- `idioma`: Language code (e.g., "en")

**Sample Request:**
```bash
curl -X GET "https://adobecar.cr:42800/Client/GetCategoryWithFare?pickupoffice=SJO&returnoffice=SJO&category=n&startdate=2025-12-15%2009:00&enddate=2025-12-18%2009:00&customerCode=Z11338&idioma=en" \
  -H "Authorization: Bearer <your-token-here>" \
  -k
```

**Sample Response:**
```json
{
  "items": [
    {
      "code": "TDR",
      "quantity": 0,
      "total": 80.31,
      "order": 1,
      "type": "BaseRate",
      "included": false,
      "description": "",
      "information": "",
      "name": "",
      "required": true
    },
    {
      "code": "PLI",
      "quantity": 0,
      "total": 40.68,
      "order": 2,
      "type": "Proteccion",
      "included": false,
      "description": "Mandatory insurance with limited coverage. Covers third party property damage with US$1130 deductible (excess). It also covers injury and death of third parties up to USD 100,000 with no deductible.",
      "information": "",
      "name": "Liability Protection",
      "required": true
    },
    {
      "code": "LDW",
      "quantity": 0,
      "total": 47.46,
      "order": 3,
      "type": "Proteccion",
      "included": false,
      "description": "Releases the financial responsibility for damages to the rental car caused by a traffic accident, collision, rollover, and total or partial theft. With a deductible (excess) of US$1,130.",
      "information": "",
      "name": "Car Protection",
      "required": false
    },
    {
      "code": "SPP",
      "quantity": 0,
      "total": 61.02,
      "order": 4,
      "type": "Proteccion",
      "included": false,
      "description": "Releases the financial responsibility of the payment of deductible (excess) for damages to third parties (US$1,130) and car protection (US$1,130).",
      "information": "",
      "name": "Extended Protection",
      "required": false
    }
  ]
}
```

## Booking Management

### 5. POST /Booking - Create New Reservation
**Endpoint:** `POST https://adobecar.cr:42800/Booking`

**Required Fields:**
```json
{
  "bookingNumber": 0,
  "category": "n",
  "startdate": "2025-12-15 09:00",
  "pickupoffice": "SJO",
  "enddate": "2025-12-18 09:00",
  "returnoffice": "SJO",
  "customerCode": "Z11338",
  "customerComment": "Test booking",
  "reference": "TEST-001",
  "flightNumber": "",
  "language": "en",
  "name": "Test",
  "lastName": "User",
  "email": "test@example.com",
  "phone": "+1234567890",
  "country": "US",
  "items": [
    {
      "code": "TDR",
      "quantity": 1,
      "total": 80.31,
      "order": 1,
      "type": "BaseRate",
      "included": false,
      "description": "",
      "information": "",
      "name": "",
      "required": true
    },
    {
      "code": "PLI",
      "quantity": 1,
      "total": 40.68,
      "order": 2,
      "type": "Proteccion",
      "included": false,
      "description": "Mandatory insurance with limited coverage",
      "information": "",
      "name": "Liability Protection",
      "required": true
    }
  ]
}
```

**Sample Request:**
```bash
curl -X POST "https://adobecar.cr:42800/Booking" \
  -H "Authorization: Bearer <your-token-here>" \
  -H "Content-Type: application/json" \
  -d '<json-body-above>' \
  -k
```

**Sample Response:**
```json
{
  "result": true,
  "message": "Success",
  "data": {
    "bookingNumber": 1285190,
    "bookingTotal": 120.99,
    "otherCharges": 0.0000,
    "baseRate": 80.3100
  }
}
```

### 6. GET /Booking - Get Reservation Details
**Endpoint:** `GET https://adobecar.cr:42800/Booking`

**Required Parameters:**
- `bookingNumber`: Booking number from creation response
- `customerCode`: "Z11338"

**Sample Request:**
```bash
curl -X GET "https://adobecar.cr:42800/Booking?bookingNumber=1285190&customerCode=Z11338" \
  -H "Authorization: Bearer <your-token-here>" \
  -k
```

**Sample Response:**
```json
{
  "result": true,
  "message": "Success",
  "data": {
    "bookingNumber": 1285190,
    "category": "n",
    "startdate": "2025-12-15 09:00",
    "placeofdelivery": "",
    "pickupoffice": "SJO",
    "enddate": "2025-12-18 09:00",
    "placeOfReturn": "",
    "returnoffice": "SJO",
    "customerCode": "Z11338",
    "customerComment": "Test booking",
    "reference": "TEST-001",
    "flightNumber": "",
    "language": "ENG",
    "name": "TEST",
    "lastName": "USER",
    "fullName": "TEST USER",
    "iDcard": "",
    "email": "test@example.com",
    "address": "",
    "phone": "+1234567890",
    "country": "",
    "items": [
      {
        "code": "TDR",
        "quantity": 1,
        "total": 80.31,
        "order": 0,
        "type": "TDR",
        "included": true,
        "description": "",
        "information": "",
        "name": null,
        "required": false
      }
    ]
  }
}
```

### 7. PUT /Booking/UpdateInformation - Update Existing Reservation
**Endpoint:** `PUT https://adobecar.cr:42800/Booking/UpdateInformation`

**Request Body:** Same structure as POST /Booking, but include existing `bookingNumber`

**Sample Request:**
```bash
curl -X PUT "https://adobecar.cr:42800/Booking/UpdateInformation" \
  -H "Authorization: Bearer <your-token-here>" \
  -H "Content-Type: application/json" \
  -d '<json-body-with-bookingNumber>' \
  -k
```

**Sample Response:**
```json
{
  "result": true,
  "message": "Success",
  "data": {
    "bookingNumber": 1285190,
    "bookingTotal": 120.99,
    "otherCharges": 0.0000,
    "baseRate": 80.3100
  }
}
```

### 8. GET /Booking/GetLinkPreRegistration - Generate Pre-registration URL
**Endpoint:** `GET https://adobecar.cr:42800/Booking/GetLinkPreRegistration`

**Required Parameters:**
- `bookingNumber`: Booking number
- `customerCode`: "Z11338"

**Sample Request:**
```bash
curl -X GET "https://adobecar.cr:42800/Booking/GetLinkPreRegistration?bookingNumber=1285190&customerCode=Z11338" \
  -H "Authorization: Bearer <your-token-here>" \
  -k
```

**Sample Response:**
```json
{
  "result": true,
  "message": "Success",
  "data": "https://www.adobecar.com/en/preregister/?numero-de-reservacion=1285190&bookingid=02000000FE940C780B254B23CB0999CAE31E8A3691E5F61878BF7D55991897DC4EE5FCF3"
}
```

### 9. DELETE /Booking - Cancel Reservation
**Endpoint:** `DELETE https://adobecar.cr:42800/Booking`

**Required Parameters:**
- `bookingNumber`: Booking number to cancel
- `customerCode`: "Z11338"

**Sample Request:**
```bash
curl -X DELETE "https://adobecar.cr:42800/Booking?bookingNumber=1285190&customerCode=Z11338" \
  -H "Authorization: Bearer <your-token-here>" \
  -k
```

**Sample Response:**
```json
{
  "result": true,
  "message": "Success",
  "data": ""
}
```

## Important Technical Details

### Date Format
- **Format:** `YYYY-MM-DD HH:MM`
- **Example:** `2025-12-15 09:00`
- **Note:** Space between date and time should be URL encoded as `%20`

### Authentication
- **Method:** Bearer Token
- **Endpoint:** `/Auth/Login`
- **Credentials:** userName="Z11338", password="11338"
- **Header Format:** `Authorization: Bearer <token>`
- **Token Expiration:** Check `expiration` field in login response

### Office Codes
- **Working Code:** "SJO" (San José Airport)
- Other codes available from `/Offices` endpoint

### Vehicle Categories
Available categories from availability response:
- `n`: Economy (Suzuki Swift Dzire)
- `w`: Compact (Hyundai Grand i10)
- `p`: Compact (Kia Soluto)
- `i`: Compact (Kia Soluto AT)
- `d`: Mid-size (Hyundai Accent)
- `m`: Compact SUV (Geely GX3)
- `q`: Compact SUV (Geely GX3 AT)
- `a`: Compact SUV (Hyundai Venue)
- `r`: SUV (Hyundai Creta)
- `l`: SUV 4WD (Suzuki Jimmy)
- `b`: SUV 4WD (Suzuki Vitara)
- `f`: SUV 4WD (Hyundai Kona)
- `h`: SUV 4WD (Hyundai Tucson)
- `j`: Large SUV (Hyundai Santa Fe)
- `e`: Van (Hyundai Staria)
- `g`: Large SUV (Mitsubishi Montero)
- `o`: Large SUV (Hyundai Palisade)
- `c`: Truck (Suzuki APV Panel)
- `k`: Truck 4WD (Isuzu D-MAX)

### Customer Code
- **Value:** "Z11338" (same as Adobe username)

### Common Error Responses

**Authentication Error:**
```json
{
  "success": false,
  "error": "Authentication failed"
}
```

**Invalid Date Error:**
```json
{
  "success": false,
  "error": "The start date is less than now 2025-08-22 09:00"
}
```

**Invalid Office Error:**
```json
{
  "success": false,
  "error": "Invalid pick office: 1"
}
```

**Validation Error:**
```json
{
  "type": "https://tools.ietf.org/html/rfc9110#section-15.5.1",
  "title": "One or more validation errors occurred.",
  "status": 400,
  "errors": {
    "returnOffice": ["The returnOffice field is required."]
  },
  "traceId": "00-..."
}
```

## Complete Booking Workflow Example

```bash
# 1. Get authentication token
TOKEN=$(curl -s -X POST https://adobecar.cr:42800/Auth/Login \
  -H "Content-Type: application/json" \
  -d '{"userName": "Z11338", "password": "11338"}' \
  -k | jq -r '.token')

# 2. Get available vehicles
curl -X GET "https://adobecar.cr:42800/Client/GetAvailabilityWithPrice?pickupoffice=SJO&returnoffice=SJO&startdate=2025-12-15%2009:00&enddate=2025-12-18%2009:00&customerCode=Z11338" \
  -H "Authorization: Bearer $TOKEN" \
  -k

# 3. Create booking
BOOKING_RESPONSE=$(curl -s -X POST "https://adobecar.cr:42800/Booking" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "bookingNumber": 0,
    "category": "n",
    "startdate": "2025-12-15 09:00",
    "pickupoffice": "SJO",
    "enddate": "2025-12-18 09:00",
    "returnoffice": "SJO",
    "customerCode": "Z11338",
    "name": "Test",
    "lastName": "User",
    "email": "test@example.com",
    "phone": "+1234567890",
    "country": "US",
    "items": [
      {"code": "TDR", "quantity": 1, "total": 80.31, "type": "BaseRate", "required": true},
      {"code": "PLI", "quantity": 1, "total": 40.68, "type": "Proteccion", "required": true}
    ]
  }' \
  -k)

BOOKING_NUMBER=$(echo $BOOKING_RESPONSE | jq -r '.data.bookingNumber')

# 4. Get booking details
curl -X GET "https://adobecar.cr:42800/Booking?bookingNumber=$BOOKING_NUMBER&customerCode=Z11338" \
  -H "Authorization: Bearer $TOKEN" \
  -k

# 5. Get pre-registration link
curl -X GET "https://adobecar.cr:42800/Booking/GetLinkPreRegistration?bookingNumber=$BOOKING_NUMBER&customerCode=Z11338" \
  -H "Authorization: Bearer $TOKEN" \
  -k

# 6. Cancel booking
curl -X DELETE "https://adobecar.cr:42800/Booking?bookingNumber=$BOOKING_NUMBER&customerCode=Z11338" \
  -H "Authorization: Bearer $TOKEN" \
  -k
```

## Support & Contact
For technical support or integration issues:
- **Email:** support@adobecar.com
- **Base URL:** https://adobecar.cr:42800

---
*Documentation generated on: November 13, 2025*
*All endpoints tested and verified working*