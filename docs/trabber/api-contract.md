# Trabber Car Hire API Contract

Trabber is implemented as a separate partner integration. It does not share Skyscanner controllers, services, config, quote storage, tracking, or reporting. Search inventory includes Vrooem internal cars and gateway-backed external provider cars when `TRABBER_INVENTORY_SCOPE=mixed`.

## Authentication

Send the configured API key on every feed request:

```http
x-api-key: <provided separately>
```

## Locations

```http
GET /api/trabber/locations
```

Response:

```json
{
  "locations": [
    {
      "id": "123",
      "name": "Dubai Airport (DXB)",
      "iata": "DXB",
      "city": "Dubai",
      "country": "United Arab Emirates",
      "country_code": "AE",
      "latitude": 25.251369,
      "longitude": 55.347204,
      "location_type": "airport"
    }
  ]
}
```

## Search

```http
POST /api/trabber/car-hire/search
```

Request:

```json
{
  "pickup": {
    "iata": "DXB"
  },
  "dropoff": {
    "iata": "DXB"
  },
  "pickup_date_time": "2026-06-15 09:00:00",
  "dropoff_date_time": "2026-06-18 09:00:00",
  "currency": "EUR",
  "language": "en",
  "user_country": "AE",
  "driver_age": 35
}
```

Location matching supports:

- `iata`
- `latitude` and `longitude`
- `unified_location_id`
- `vendor_location_id`
- `geoname_id` when a Vrooem location code is mapped to that Geonames id

Response:

```json
{
  "offers": [
    {
      "offer_id": "2ef21ef6-98d2-45ff-b51c-545b56dcb7d9",
      "vehicle_name": "Toyota Yaris",
      "supplier_name": "Airport Fleet Co",
      "sipp": "ECAR",
      "price": 149.49,
      "currency": "EUR",
      "image_url": "https://example.com/yaris.jpg",
      "inclusions": [
        "Free eSIM included"
      ],
      "free_esim_included": true,
      "applied_offers": [
        {
          "id": 1,
          "name": "Free E-Sim",
          "slug": "free-e-sim",
          "title": "Free E-Sim",
          "description": "Free eSIM with every booking",
          "effect_type": "free_esim",
          "effect_payload": {
            "included": true
          },
          "discount_amount": 0
        }
      ],
      "vehicle": {
        "display_name": "Toyota Yaris",
        "brand": "Toyota",
        "model": "Yaris",
        "category": "economy",
        "image_url": "https://example.com/yaris.jpg",
        "supplier_name": "Airport Fleet Co",
        "supplier_code": "internal",
        "sipp_code": "ECAR",
        "transmission": "automatic",
        "fuel_type": "petrol",
        "air_conditioning": true,
        "seats": 5,
        "doors": 4,
        "luggage": {
          "large": 2
        }
      },
      "specs": {
        "sipp_code": "ECAR",
        "transmission": "automatic",
        "fuel": "petrol",
        "air_conditioning": true,
        "seating_capacity": 5,
        "doors": 4,
        "luggage_large": 2
      },
      "pricing": {
        "currency": "EUR",
        "total_price": 149.49,
        "price_per_day": 49.83,
        "deposit_amount": 150,
        "deposit_currency": "EUR"
      },
      "policies": {
        "mileage_policy": "limited",
        "mileage_limit_km": 300,
        "mileage": {
          "policy": "limited",
          "allowance": 300,
          "unit": "km",
          "period": "per_day"
        },
        "fuel_policy": "Full to Full",
        "cancellation": {
          "available": true,
          "days_before_pickup": 2
        }
      },
      "pickup_location_details": {
        "provider_location_id": "123",
        "name": "Dubai Airport (DXB)",
        "location_type": "airport",
        "iata": "DXB",
        "city": "Dubai",
        "country": "United Arab Emirates",
        "country_code": "AE",
        "address": "Dubai Airport Terminal 1",
        "latitude": 25.251369,
        "longitude": 55.347204,
        "at_airport": true,
        "in_terminal": true,
        "requires_shuttle": false
      },
      "dropoff_location_details": {
        "provider_location_id": "123",
        "name": "Dubai Airport (DXB)",
        "location_type": "airport",
        "iata": "DXB",
        "city": "Dubai",
        "country": "United Arab Emirates",
        "country_code": "AE",
        "address": "Dubai Airport Terminal 1",
        "latitude": 25.251369,
        "longitude": 55.347204,
        "at_airport": true,
        "in_terminal": true,
        "requires_shuttle": false
      },
      "security_deposit": {
        "amount": 150,
        "currency": "EUR"
      },
      "capacity": {
        "seats": 5,
        "bags": 2,
        "luggage": {
          "large": 2
        }
      },
      "mileage": {
        "policy": "limited",
        "allowance": 300,
        "unit": "km",
        "period": "per_day"
      },
      "coverages": {
        "cdw": {
          "type": "CDW",
          "included": true,
          "name": "Collision Damage Waiver",
          "excess_amount": 1000,
          "currency": "EUR"
        },
        "tp": {
          "type": "TP",
          "included": true,
          "name": "Third Party Liability",
          "currency": "EUR"
        },
        "tw": {
          "type": "TW",
          "included": true,
          "name": "Theft Waiver",
          "excess_amount": 1200,
          "currency": "EUR"
        }
      },
      "fuel_policy": "Full to Full",
      "mileage_policy": "limited",
      "cancellation_policy": {
        "available": true,
        "days_before_pickup": 2
      },
      "deeplink_url": "https://vrooem.com/api/trabber/redirect?offer_id=..."
    }
  ],
  "meta": {
    "source": "trabber",
    "inventory_scope": "mixed",
    "pickup_location_id": "123",
    "dropoff_location_id": "123",
    "currency": "EUR",
    "language": "en",
    "user_country": "AE",
    "offer_count": 1
  }
}
```

Fuel policies are normalized to readable labels before they are sent to Trabber. Example supplier raw codes include `SL` = `Same Level`, meaning the customer returns the vehicle with the same fuel level as pickup.

When Vrooem has an active search offer such as free eSIM, every Trabber offer includes `free_esim_included`, the normalized `applied_offers` list, and a readable inclusion line. These fields are additive; existing offer fields and deeplink behavior remain unchanged.

Trabber offers also include Skyscanner-style rich data blocks for vehicle specs, customer-facing pricing, pickup/drop-off office details, deposit, capacity, mileage allowance, and known coverages. Coverage values are sent only when Vrooem has reliable supplier/internal data for that offer.

Trabber should append `clickid` to the `deeplink_url`.

Example:

```text
https://vrooem.com/api/trabber/redirect?offer_id=2ef21ef6-98d2-45ff-b51c-545b56dcb7d9&clickid=TRABBER-CLICK-123
```

## Attribution

- Partner source: `trabber`
- Click parameter: `clickid`
- Attribution: last-click
- Attribution lifetime: 90 days
- Commission: 5% of total booking amount

The redirect stores the latest Trabber click in the browser session/cookie and sends the traveller to the Vrooem dedicated offer page for the selected vehicle.

## Confirmed Reporting Details

- Report recipient: `reports@trabber.com`
- Filename pattern: no special requirements
- Logo delivery email: `ofrias@trabber.com`
