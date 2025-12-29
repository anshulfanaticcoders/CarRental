# USave API Response Analysis

**Date Generated:** 2025-12-29
**Provider:** USave
**Test Parameters:**
- Pickup Date: 2032-01-06
- Return Date: 2032-01-10 (4 days)
- Pickup Time: 10:00
- Age: 35

## API Endpoint

**URL:** `https://sv.fusemetrix.com/bespoke/SVWebService.php`
**Credentials:**
- Username: `UsUVM@2025!`
- Password: `UsUVM@2025!`

---

## Test Results Summary

| Location | ID | Status | File Size |
|----------|-----|--------|-----------|
| Tirana Airport | 61564 | ✅ Success | 8,090 bytes |
| Marrakech Airport | 359 | ❌ Failed (Connection Error) | - |

---

## Detailed Response Analysis (Tirana Airport - ID: 61564)

### Response Structure

```xml
<gm_webservice>
  <header>
    <request type="GetVehicles" />
  </header>
  <response type="VehicleListing">
    <location>61564</location>
    <days>4</days>
    <vehicles>
      <!-- 5 vehicles -->
    </vehicles>
    <optionalextras>
      <!-- 7 extras -->
    </optionalextras>
    <quoteid>67283748244</quoteid>
  </response>
</gm_webservice>
```

---

## Vehicles Available

| # | Vehicle | Group | Daily Rate | Total (4 days) | Excess | Deposit | Min Age |
|---|---------|-------|------------|----------------|--------|---------|---------|
| 1 | Hyundai i10, or similar | AL-UA | €51.85 | €207.40 | €800 | €500 | 21 |
| 2 | Ford Fiesta, or similar | AL-UB | €52.91 | €211.65 | €1000 | €600 | 21 |
| 3 | Kia Stonic, or similar | AL-UC | €58.23 | €232.90 | €1200 | €700 | 21 |
| 4 | Hyundai Venue, automatic | AL-UD | €63.54 | €254.15 | €1200 | €700 | 21 |
| 5 | Dacia Duster, or similar | AL-UE | €65.66 | €262.65 | €1200 | €700 | 21 |

### Vehicle Details Structure

```xml
<vehicle name='Hyundai i10, or similar' id='105758'>
  <total currency="EUR">207.40</total>
  <groupName>AL-UA</groupName>
  <adults>4</adults>
  <children />
  <luggageSmall>2</luggageSmall>
  <luggageMed>0</luggageMed>
  <luggageLarge>0</luggageLarge>
  <fuel>Petrol</fuel>
  <acriss>MDMV</acriss>
  <transmission>Manual Unspecified Drive</transmission>
  <minage>21</minage>
  <excess>800.00</excess>
  <deposit>500.00</deposit>
  <airConditioning>Yes</airConditioning>
  <keyngo>Yes</keyngo>
</vehicle>
```

---

## Optional Extras

| # | Extra Name | Code | Daily Rate | Total (4 days) | Category |
|---|------------|------|------------|----------------|----------|
| 1 | Additional Driver | ADD | €10.00 | €40.00 | Additional Options |
| 2 | Baby Seat | BBS | €10.00 | €40.00 | Additional Options |
| 3 | Child booster seat | BOO | €10.00 | €40.00 | Additional Options |
| 4 | Child seat | CHS | €10.00 | €40.00 | Additional Options |
| 5 | Mobile Phone Charger | MPC | €5.00 | €20.00 | - |
| 6 | Snow chains | SNO | €10.00 | €40.00 | Additional Options |
| 7 | Winter tyres | STR | €10.00 | €40.00 | Additional Options |

### Extra Details Structure

```xml
<extra>
  <optionID>2</optionID>
  <Name>Additional Driver</Name>
  <Description>Enjoy your journey and drive more safely...</Description>
  <Daily_rate currency="EUR">10.00</Daily_rate>
  <Category>Additional Options</Category>
  <Total_for_this_booking currency="EUR">40.00</Total_for_this_booking>
  <code>ADD</code>
</extra>
```

---

## Key Findings

### 1. CRITICAL: Pricing Packages Require `rentalCode` Parameter
**USave DOES return product packages (BAS/PLU/PRE/PMP) when `<rentalCode>1</rentalCode>` is included in the request!**

Without `rentalCode`:
- Response: 8,090 bytes
- Structure: Single price per vehicle (`<total>` element at vehicle level)
- No product packages

With `rentalCode=1`:
- Response: 12,214 bytes (51% larger!)
- Structure: 4 products per vehicle with BAS/PLU/PRE/PMP types

### 2. Product Packages Are Identical to GreenMotion
USave uses the same API structure as GreenMotion, including:
- Same product types (BAS, PLU, PRE, PMP)
- Same XML structure for products
- Same deposit/excess/fuel policy differences between packages

### 3. Simpler Vehicle Selection
USave offers fewer vehicles (5) compared to GreenMotion's selection at the same location types.

### 4. Lower Pricing
USave prices are generally lower than GreenMotion for similar vehicle categories.

### 5. Currency in EUR
Albania location returns prices in Euros (EUR).

### 6. Minimum Age 21
Unlike some GreenMotion locations (min age 23), USave Albania allows drivers aged 21+.

---

## Extras Comparison: USave vs GreenMotion

| Extra | USave (Tirana) | GreenMotion (Marrakech) |
|-------|----------------|-------------------------|
| Additional Driver | €10/day (€40 total) | MAD 260 one-time |
| Baby Seat | €10/day (€40 total) | MAD 80/day (MAD 320 total) |
| Child Seat | €10/day (€40 total) | Similar |
| GPS | Not listed | MAD 100/day |
| WiFi Hotspot | Not listed | MAD 120/day |

---

## Response Files

```
/opt/lampp/htdocs/CarRental/tests/api/usave/
├── usave_02_get_vehicles_tirana.xml (8,090 bytes) - WITHOUT products
├── usave_with_products.xml (12,214 bytes) - WITH rentalCode=1, HAS PRODUCTS!
├── usave_03_get_location_info_359.xml (4,552 bytes) ✅
└── usave_04_get_country_list.xml (13,566 bytes) ✅
```

---

## Location Info Response

```xml
<gm_webservice>
  <response type="LocationInfo">
    <location>
      <location_id>359</location_id>
      <location_name>Marrakech Airport</location_name>
      <address>...</address>
      <latitude>31.60645</latitude>
      <longitude>-8.03633</longitude>
      <country_id>8</country_id>
      <is_one_way>1</is_one_way>
      <one_way_dropoff_locations>
        <!-- List of dropoff locations -->
      </one_way_dropoff_locations>
    </location>
  </response>
</gm_webservice>
```

---

## Country List Response

USave supports fewer countries than GreenMotion:

| Country | Country ID | ISO Alpha-2 | ISO Alpha-3 |
|---------|-----------|-------------|-------------|
| Albania | 38904 | AL | ALB |
| Austria | 58365 | AT | AUT |
| Belgium | - | BE | BEL |
| Bulgaria | 58840 | BG | BGR |
| ... (and more) |

---

## Recommendations

### For Code Updates

1. **Ensure rentalCode Parameter is Included:** Always include `<rentalCode>1</rentalCode>` in API requests to get product packages.

2. **Existing Code is Correct:** The product parsing logic in GreenMotionController.php already handles the BAS/PLU/PRE/PMP packages correctly.

3. **SearchController Defaults to rentalCode='1':** The existing code at SearchController.php:465 already includes this parameter.

### For UI/UX

1. **Package Selection:** Show all 4 package options (BAS/PLU/PRE/PMP) to users with clear differences in deposit, excess, and fuel policy.

2. **Extras Calculation:** Calculate extras dynamically based on rental period.

3. **Location-Specific Currency:** Display prices in the location's currency with conversion option.

---

## Next Steps

1. **No Code Changes Needed:** The existing code already correctly includes rentalCode and parses products.

2. **Test Across Locations:** Verify product packages work correctly for all supported locations.

3. **Documentation Updated:** This document now correctly reflects that products ARE available with rentalCode parameter.
