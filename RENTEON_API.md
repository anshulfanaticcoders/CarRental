# Renteon Aggregator API (Demo)

Base URL: https://aggregator.renteon.com
Auth: HTTP Basic (username/password)
Provider code for testing: demo

Notes
- Dates are ISO 8601 local time strings WITHOUT time zone designator (e.g. 2026-02-10T10:00:00).
- PriceDate is UTC ISO 8601 and can be sent to lock pricing (recommended).
- Currency uses ISO 4217 (e.g. EUR).

## Setup endpoints

### GET /api/setup/providers
Lists available providers.

Sample curl

```bash
curl -u "$RENTEON_USERNAME:$RENTEON_PASSWORD" \
  "https://aggregator.renteon.com/api/setup/providers"
```

Example response (truncated)

```json
[
  {"Code":"demo","Name":"Demo","LogoUrl":null},
  {"Code":"LetsDrive","Name":"LetsDrive","LogoUrl":null}
]
```

### GET /api/setup/provider/{providerCode}
Returns the full set of data for a provider (locations, offices, services, car categories, pricelists, etc.).

Sample curl

```bash
curl -u "$RENTEON_USERNAME:$RENTEON_PASSWORD" \
  "https://aggregator.renteon.com/api/setup/provider/demo"
```

Key response sections (demo)
- Connectors: [{"Id":1,"Name":"Demo Connector S4","Url":"https://democonnector.s4.renteon.com"}]
- Pricelists: [{"Code":"CP-AG-01","IsPrepaid":true},{"Code":"CP-WI-01","IsPrepaid":false}]
- CarCategories (SIPP): CDAR, CDMR, FVMR, IDAR, IDMR, LVMR, MDAR, MDMR, SDAD, XKMD, IKMD
- Offices (use OfficeId for booking):
  - OfficeId 1, OfficeCode ZGD, LocationCode HR-ZAG-DT, ConnectorId 1
  - OfficeId 4, OfficeCode DBA, LocationCode HR-DUB-DBV, ConnectorId 1

### GET /api/setup/locations
Lists supported location codes.

Sample curl

```bash
curl -u "$RENTEON_USERNAME:$RENTEON_PASSWORD" \
  "https://aggregator.renteon.com/api/setup/locations"
```

LocationModel fields
- Code, CountryCode, Name, Type, Category, Path

Example locations (from demo provider data)
- HR-ZAG-DT (Zagreb Downtown)
- HR-DUB-DBV (Dubrovnik Airport)
- GR-ATH-PT (Athens Port)

### GET /api/setup/services
Lists services (fees, equipment, insurance, etc.) available across providers.

```bash
curl -u "$RENTEON_USERNAME:$RENTEON_PASSWORD" \
  "https://aggregator.renteon.com/api/setup/services"
```

### GET /api/setup/carCategories
Lists car categories (SIPP codes).

```bash
curl -u "$RENTEON_USERNAME:$RENTEON_PASSWORD" \
  "https://aggregator.renteon.com/api/setup/carCategories"
```

## Availability (search)

### POST /api/bookings/availability
Searches available cars and prices.

Required fields
- Prepaid (bool)
- IncludeOnRequest (bool)
- PickupLocation (location code)
- DropOffLocation (location code)
- PickupDate, DropOffDate (local ISO 8601 without TZ)
- Currency

Recommended fields
- Providers: [{"Code":"demo"}] to filter by provider
- Drivers: [{"DriverAge":35}]

Sample curl

```bash
curl -u "$RENTEON_USERNAME:$RENTEON_PASSWORD" \
  "https://aggregator.renteon.com/api/bookings/availability" \
  -H "Content-Type: application/json" \
  -d '{
    "Prepaid": true,
    "IncludeOnRequest": false,
    "Providers": [{"Code":"demo"}],
    "PickupLocation": "HR-ZAG-DT",
    "DropOffLocation": "HR-ZAG-DT",
    "PickupDate": "2026-02-10T10:00:00",
    "DropOffDate": "2026-02-13T10:00:00",
    "Currency": "EUR",
    "Drivers": [{"DriverAge": 35}]
  }'
```

Example response (single record, trimmed)

```json
{
  "Provider": "demo",
  "ConnectorId": 1,
  "CarCategory": "MDMR",
  "ModelName": "VW Up",
  "Amount": 30.0,
  "Currency": "EUR",
  "PricelistId": 28,
  "PricelistCode": "CP-AG-01",
  "PriceDate": "2026-01-19T10:34:57.4329655Z",
  "PickupOfficeId": 1,
  "DropOffOfficeId": 1,
  "PassengerCapacity": 4,
  "BigBagsCapacity": 2,
  "SmallBagsCapacity": 3,
  "NumberOfDoors": 6,
  "CarModelImageURL": "https://democonnector.s4.renteon.com/..."
}
```

Frontend fields for search results
- Name + class: ModelName, CarCategory (SIPP)
- Image: CarModelImageURL
- Price: Amount, Currency, OriginalAmount, DiscountPercentage
- Capacity: PassengerCapacity, BigBagsCapacity, SmallBagsCapacity, NumberOfDoors
- Booking linkage: ConnectorId, PickupOfficeId, DropOffOfficeId, PricelistId, PriceDate, Prepaid

## Booking flow

### POST /api/bookings/create
Calculates pricing + returns unsaved booking with services list.

Required fields
- ConnectorId, CarCategory, PickupOfficeId, DropOffOfficeId
- PickupDate, DropOffDate
- PricelistId, Currency
- Prepaid (bool)

Sample curl

```bash
curl -u "$RENTEON_USERNAME:$RENTEON_PASSWORD" \
  "https://aggregator.renteon.com/api/bookings/create" \
  -H "Content-Type: application/json" \
  -d '{
    "ConnectorId": 1,
    "CarCategory": "MDMR",
    "PickupOfficeId": 1,
    "DropOffOfficeId": 1,
    "PickupDate": "2026-02-10T10:00:00",
    "DropOffDate": "2026-02-13T10:00:00",
    "PricelistId": 28,
    "Currency": "EUR",
    "Prepaid": true,
    "PriceDate": "2026-01-19T10:34:57.4329655Z",
    "Drivers": [{"DriverAge": 35}]
  }'
```

Key response fields
- Totals[] (pay now/pay later), Total, Currency
- Services[] (mandatory + optional add-ons)
- PickupOffice, DropOffOffice
- OnlineCheckinUrl (if provided)
- ConnectorId, Provider, CarCategory

### POST /api/bookings/calculate
Recalculate booking totals (same model as save).

```bash
curl -u "$RENTEON_USERNAME:$RENTEON_PASSWORD" \
  "https://aggregator.renteon.com/api/bookings/calculate" \
  -H "Content-Type: application/json" \
  -d '{ ...BookingInModel... }'
```

### POST /api/bookings/save
Saves the booking.

Required fields (BookingInModel)
- ConnectorId, CarCategory, PickupOfficeId, DropOffOfficeId
- PickupDate, DropOffDate
- PricelistId, Currency, Prepaid

Recommended
- ClientName, ClientEmail, ClientPhone
- Services[] with ServiceId + IsSelected + Quantity
- PriceDate (from availability) to lock price

Sample curl

```bash
curl -u "$RENTEON_USERNAME:$RENTEON_PASSWORD" \
  "https://aggregator.renteon.com/api/bookings/save" \
  -H "Content-Type: application/json" \
  -d '{
    "ConnectorId": 1,
    "CarCategory": "MDMR",
    "PickupOfficeId": 1,
    "DropOffOfficeId": 1,
    "PickupDate": "2026-02-10T10:00:00",
    "DropOffDate": "2026-02-13T10:00:00",
    "PricelistId": 28,
    "Currency": "EUR",
    "Prepaid": true,
    "ClientName": "John Doe",
    "ClientEmail": "john@example.com",
    "ClientPhone": "+385921234567",
    "Services": [
      {"ServiceId": 80, "IsSelected": true, "Quantity": 1}
    ]
  }'
```

### POST /api/bookings/open
Fetch a booking by connector + number.

```bash
curl -u "$RENTEON_USERNAME:$RENTEON_PASSWORD" \
  "https://aggregator.renteon.com/api/bookings/open" \
  -H "Content-Type: application/json" \
  -d '{"ConnectorId":1,"Number":"2016-ZS-4568"}'
```

### POST /api/bookings/openWithEmailOrPhone
Validates via email/phone (phone without leading +).

```bash
curl -u "$RENTEON_USERNAME:$RENTEON_PASSWORD" \
  "https://aggregator.renteon.com/api/bookings/openWithEmailOrPhone" \
  -H "Content-Type: application/json" \
  -d '{"ConnectorId":1,"Number":"2016-ZS-4568","EmailOrPhone":"385921234567"}'
```

### DELETE /api/bookings/cancel
Cancel a booking by connector + number.

```bash
curl -u "$RENTEON_USERNAME:$RENTEON_PASSWORD" \
  -X DELETE "https://aggregator.renteon.com/api/bookings/cancel" \
  -H "Content-Type: application/json" \
  -d '{"ConnectorId":1,"Number":"2016-ZS-4568"}'
```

## Test booking data used

Availability request (demo)
- Pickup/DropOff: HR-ZAG-DT
- PickupDate: 2026-02-10T10:00:00
- DropOffDate: 2026-02-13T10:00:00
- DriverAge: 35

Availability response (sample)
- ConnectorId: 1
- CarCategory: MDMR
- ModelName: VW Up
- Price: 30.00 EUR
- PricelistId: 28 (CP-AG-01)
- PickupOfficeId / DropOffOfficeId: 1
- PriceDate: 2026-01-19T10:34:57.4329655Z

Booking create response
- Total: 30.00 EUR
- Services list provided for add-ons selection

## Frontend mapping notes

Search result card
- Provider, ModelName, CarCategory
- CarModelImageURL
- Amount + Currency (show OriginalAmount + DiscountPercentage if present)
- PassengerCapacity, BigBagsCapacity, SmallBagsCapacity, NumberOfDoors
- DepositAmount/DepositCurrency, ExcessAmount/ExcessTheftAmount

Booking payload
- Required: ConnectorId, CarCategory, PickupOfficeId, DropOffOfficeId, PickupDate, DropOffDate, PricelistId, Currency, Prepaid
- Recommended: PriceDate (from availability), Drivers, ClientName/Email/Phone
- Extras: Services[] (ServiceId + IsSelected + Quantity)
