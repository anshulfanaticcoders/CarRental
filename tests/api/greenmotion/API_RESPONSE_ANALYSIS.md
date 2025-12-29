# GreenMotion/USave API Response Analysis

**Date Generated:** 2025-12-29
**Test Parameters:**
- Pickup Date: 2032-01-06
- Return Date: 2032-01-10 (4 days)
- Pickup Time: 10:00
- Age: 35

## CRITICAL FINDING: Products/Pricing Packages Require `rentalCode` Parameter

### Expected Structure (from GreenMotionController.php)
The code expects products/pricing packages with types:
- **BAS** - Basic
- **PLU** - Plus
- **PRE** - Premium
- **PMP** - Premium Plus

### How to Get Product/Pricing Packages

**IMPORTANT:** The API only returns products when the `<rentalCode>1</rentalCode>` parameter is included in the request!

Without `rentalCode`:
- Response: 31,075 bytes
- Structure: Single price per vehicle (`<total>` element at vehicle level)
- No product packages

With `rentalCode=1`:
- Response: 53,454 bytes (72% larger!)
- Structure: 4 products per vehicle with BAS/PLU/PRE/PMP types
- Each product has different deposit, excess, and fuel policy

### Sample Request WITH Products

```xml
<?xml version="1.0" encoding="utf-8"?>
<gm_webservice>
    <header>
        <username>GmVEM@2025!</username>
        <password>GmVEM@2025!</password>
        <version>1.5</version>
    </header>
    <request type="GetVehicles">
        <location_id>359</location_id>
        <start_date>2032-01-06</start_date>
        <start_time>10:00</start_time>
        <end_date>2032-01-10</end_date>
        <end_time>10:00</end_time>
        <age>35</age>
        <rentalCode>1</rentalCode>  <!-- KEY PARAMETER -->
    </request>
</gm_webservice>
```

### Product Structure with rentalCode=1

```xml
<vehicle name='Hyundai I10, or similar' id='95539'>
  <product type='BAS'>
    <total currency="MAD">1020.00</total>
    <deposit>17000.00</deposit>
    <excess>17000.00</excess>
    <fuelpolicy>SL</fuelpolicy>
    <purpose>120</purpose>
    <minage>23</minage>
  </product>
  <product type='PLU'>
    <total currency="MAD">1020.00</total>
    <deposit>8500.00</deposit>
    <excess>8500.00</excess>
    <fuelpolicy>SL</fuelpolicy>
    <purpose>120</purpose>
    <minage>23</minage>
  </product>
  <product type='PRE'>
    <total currency="MAD">1020.00</total>
    <deposit>4250.00</deposit>
    <excess>4250.00</excess>
    <fuelpolicy>FF</fuelpolicy>
    <purpose>116</purpose>
    <minage>23</minage>
  </product>
  <product type='PMP'>
    <total currency="MAD">1020.00</total>
    <deposit>2000.00</deposit>
    <excess>2000.00</excess>
    <fuelpolicy>FF</fuelpolicy>
    <purpose>116</purpose>
    <debitcard>Y</debitcard>
    <minage>23</minage>
  </product>
  <groupName>MR-A</groupName>
  <!-- ... other vehicle properties ... -->
</vehicle>
```

### Product Package Differences

| Package | Total Price | Deposit | Excess | Fuel Policy | Debit Card | Key Benefit |
|---------|-------------|---------|--------|-------------|------------|-------------|
| **BAS** (Basic) | Same | Highest | Highest | SL (Like for Like) | No | Lowest base rate |
| **PLU** (Plus) | Same | Lower | Lower | SL (Like for Like) | No | Reduced liability |
| **PRE** (Premium) | Same | Even Lower | Even Lower | FF (Free Fuel) | No | Low excess + free fuel |
| **PMP** (Premium Plus) | Same | Lowest | Zero or lowest | FF (Free Fuel) | Yes | Zero excess + debit card + free fuel |

**Note:** All packages have the SAME total price for Marrakech. The difference is in deposit, excess, and fuel policy. (Other locations like Dubai have different prices per package.)

---

## Actual API Response Structure

### GetVehicles Response Structure

```xml
<gm_webservice>
  <header>
    <request type="GetVehicles" />
  </header>
  <response type="VehicleListing">
    <location>359</location>
    <days>4</days>
    <vehicles>
      <!-- Vehicle list -->
    </vehicles>
    <optionalextras>
      <!-- Extras list -->
    </optionalextras>
  </response>
</gm_webservice>
```

### Vehicle Element Structure

| Field | Example | Description |
|-------|---------|-------------|
| `name` | "Hyundai I10, or similar" | Vehicle name |
| `id` | "95539" | Vehicle ID |
| `image` | (URL encoded) | Vehicle image URL |
| `total` | "1020.00" | Total price |
| `currency` | "MAD" | Currency code (MAD=Moroccan Dirham, EUR=Euro) |
| `groupName` | "MR-A" | Vehicle group code |
| `adults` | "4" | Number of adults |
| `luggageSmall/Med/Large` | "0/1/0" | Luggage capacity |
| `fuel` | "Petrol" | Fuel type |
| `acriss` | "MDMR" | ACRISS code |
| `transmission` | "Manual Unspecified Drive" | Transmission type |
| `minage` | "23" | Minimum driver age |
| `excess` | "17000.00" | Excess liability amount |
| `deposit` | "17000.00" | Security deposit |
| `airConditioning` | "Yes" | Has AC |
| `keyngo` | "Yes" | Key'n'Go available |

### Optional Extras Structure

```xml
<extra>
  <optionID>2</optionID>
  <Name>Additional Driver</Name>
  <Description>Additional Driver</Description>
  <Daily_rate currency="MAD">260.00</Daily_rate>
  <Category>Additional Options</Category>
  <Total_for_this_booking currency="MAD">260.00</Total_for_this_booking>
  <Choices />
  <code>ADD</code>
</extra>
```

| Field | Description |
|-------|-------------|
| `optionID` | Extra ID (integer) |
| `Name` | Display name |
| `Description` | Detailed description |
| `Daily_rate` | Price per day |
| `Total_for_this_booking` | Total for rental period |
| `Category` | Extra category |
| `code` | Short code (ADD, BBS, etc.) |

---

## Sample GreenMotion Response (Marrakech Airport)

**Location ID:** 359
**Currency:** MAD (Moroccan Dirham)
**Vehicles Found:** 25

### Vehicle Examples:

| Vehicle | Group | Price (4 days) | Excess | Deposit |
|---------|-------|----------------|--------|---------|
| Hyundai I10 | MR-A | MAD 1,020 | MAD 17,000 | MAD 17,000 |
| Dacia Sandero | MR-B | MAD 1,122 | MAD 17,000 | MAD 17,000 |
| Toyota Yaris | MR-C | MAD 1,382 | MAD 20,000 | MAD 20,000 |
| Dacia Duster | MR-I | MAD 2,660 | MAD 30,000 | MAD 30,000 |
| Range Rover Evoque | MR-S | MAD 4,848 | MAD 95,000 | MAD 95,000 |

### Extras Available:

| Extra | Code | Daily Rate | Total (4 days) |
|-------|------|------------|----------------|
| Additional Driver | ADD | MAD 260 | MAD 260 |
| Baby Seat | BBS | MAD 80 | MAD 320 |
| Car roof box | CRB | MAD 190 | MAD 760 |
| Car wifi hotspot | - | MAD 120 | MAD 480 |
| GPS Navigation | GPS | MAD 100 | MAD 400 |

---

## Sample USave Response (Tirana Airport)

**Location ID:** 61564
**Currency:** EUR (Euro)
**Vehicles Found:** 5

### Vehicle Examples:

| Vehicle | Group | Price (4 days) | Excess | Deposit |
|---------|-------|----------------|--------|---------|
| Hyundai i10 | AL-UA | EUR 207.40 | EUR 800 | EUR 500 |
| Ford Fiesta | AL-UB | EUR 211.65 | EUR 1000 | EUR 600 |
| Kia Stonic | AL-UC | EUR 232.90 | EUR 1200 | EUR 700 |
| Hyundai Venue Auto | AL-UD | EUR 254.15 | EUR 1200 | EUR 700 |
| Dacia Duster | AL-UE | EUR 262.65 | EUR 1200 | EUR 700 |

### Extras Available:

| Extra | Code | Daily Rate | Total (4 days) |
|-------|------|------------|----------------|
| Additional Driver | ADD | EUR 10 | EUR 40 |
| Baby Seat | BBS | EUR 10 | EUR 40 |
| Child booster seat | BOO | EUR 10 | EUR 40 |
| Child seat | CHS | EUR 10 | EUR 40 |
| Mobile Phone Charger | MPC | EUR 5 | EUR 20 |
| Snow chains | SNO | EUR 10 | EUR 40 |
| Winter tyres | STR | EUR 10 | EUR 40 |

---

## Comparison: GreenMotion vs USave

| Aspect | GreenMotion | USave |
|--------|-------------|-------|
| API URL | gmvrl.fusemetrix.com | sv.fusemetrix.com |
| Response Structure | Identical | Identical |
| Currency | Varies by location | Varies by location |
| Vehicle Count | Higher (25 vehicles) | Lower (5 vehicles) |
| Extras | More options available | Fewer options available |

---

## API Request Parameters

### Required Parameters:
- `location_id` - Location ID (integer)
- `start_date` - Pickup date (YYYY-MM-DD)
- `start_time` - Pickup time (HH:MM)
- `end_date` - Return date (YYYY-MM-DD)
- `end_time` - Return time (HH:MM)
- `age` - Driver age (integer)

### Optional Parameters:
- `rentalCode` - **CRITICAL:** Set to `1` to get product packages (BAS/PLU/PRE/PMP). Without this, only single price returned.
- `promocode` - Promo code
- `dropoff_location_id` - Different return location
- `currency` - Currency code
- `fuel` - Fuel preference
- `userid` - User ID
- `username` - Username
- `language` - Language code
- `full_credit` - Full credit option

---

## API Endpoint URLs

| Provider | URL |
|----------|-----|
| GreenMotion | `https://gmvrl.fusemetrix.com/bespoke/GMWebService.php` |
| USave | `https://sv.fusemetrix.com/bespoke/SVWebService.php` |

---

## Credentials (from .env)

```
GREENMOTION_URL=https://gmvrl.fusemetrix.com/bespoke/GMWebService.php
GREENMOTION_USERNAME=GmVEM@2025!
GREENMOTION_PASSWORD=GmVEM@2025!

USAVE_URL=https://sv.fusemetrix.com/bespoke/SVWebService.php
USAVE_USERNAME=UsUVM@2025!
USAVE_PASSWORD=UsUVM@2025!
```

---

## Response Files Location

All XML response files are saved in:
```
/opt/lampp/htdocs/CarRental/tests/api/
├── greenmotion/
│   ├── greenmotion_01_get_vehicles_marrakech.xml (31,075 bytes) - WITHOUT products
│   ├── greenmotion_with_products.xml (53,454 bytes) - WITH rentalCode=1, HAS PRODUCTS!
│   ├── greenmotion_02_get_vehicles_tirana.xml (20,295 bytes)
│   ├── greenmotion_04_get_location_info_359.xml (4,558 bytes)
│   ├── greenmotion_05_get_country_list.xml (20,358 bytes)
│   ├── greenmotion_06_get_terms_morocco.xml (321 bytes)
│   ├── greenmotion_07_get_region_morocco.xml (197 bytes)
│   └── greenmotion_08_get_service_areas_morocco.xml (1,668 bytes)
└── usave/
    ├── usave_02_get_vehicles_tirana.xml (8,090 bytes) - WITHOUT products
    ├── usave_with_products.xml (12,214 bytes) - WITH rentalCode=1, HAS PRODUCTS!
    ├── usave_03_get_location_info_359.xml (4,552 bytes)
    └── usave_04_get_country_list.xml (13,566 bytes)
```

---

## Code Implications

### GreenMotionController.php lines 23-95
The code correctly contains logic to parse `vehicle->product` elements with types (BAS, PLU, PRE, PMP).

**The parsing code is CORRECT** - it will work when `rentalCode=1` is passed to the API.

### How the Code Currently Works

In `SearchController.php` line 465:
```php
'rentalCode' => $validated['rentalCode'] ?? '1',
```

The SearchController **defaults rentalCode to '1'**, so product packages are normally returned.

In `GreenMotionService.php` line 64:
```php
$rentalCodeXml = isset($options['rentalCode']) ? '<rentalCode>' . $options['rentalCode'] . '</rentalCode>' : '';
```

The rentalCode parameter is properly passed through to the API request.

### Recommendation

**Ensure all API calls include `<rentalCode>1</rentalCode>`** to get the full product package options.

If `rentalCode` is omitted, the products array will be empty and the Vue component's `availablePackages` will be empty, meaning no package selection options will be shown to users.

---

## Location IDs Tested

| Location | GreenMotion ID | USave ID | Status |
|----------|----------------|----------|--------|
| Marrakech Airport | 359 | 359 | GreenMotion OK, USave Failed |
| Tirana Airport | 53981 | 61564 | Both OK |
| Default Location | 61627 | N/A | OK |

---

## Notes

1. **CRITICAL - rentalCode Parameter:** The API only returns product packages (BAS/PLU/PRE/PMP) when `<rentalCode>1</rentalCode>` is included in the request. Without this parameter, only a single price is returned.

2. **Date Requirements:** API requires dates at least 2 hours in the future. Using 2032 dates ensures API compatibility.

3. **Currency:** Each location has its own currency (MAD for Morocco, EUR for Albania, USD for Dubai, etc.)

4. **Product Package Pricing:**
   - Marrakech: All packages have the SAME total price, differences are in deposit/excess/fuel policy
   - Dubai: Each package has DIFFERENT total prices
   - The pricing model varies by location

5. **Extras Structure:** Extras are returned with daily rates and total for booking period.

6. **Vehicle Image URLs:** Images are URL-encoded and require decoding before use.

7. **SearchController defaults rentalCode to '1':** The existing code at SearchController.php:465 already includes the rentalCode parameter with a default value of '1', so product packages should normally be returned in the application.
