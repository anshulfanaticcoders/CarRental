# LocautoRent API Testing & Analysis

**Date Generated:** 2026-01-02
**Test Parameters:**
- Pickup Date: 2026-02-01
- Return Date: 2026-02-05 (4 days)
- Pickup Time: 10:00
- Age: 35

---

## ✅ SUCCESS: API Returning Valid Data

The LocautoRent API is **working correctly**. Earlier tests failed due to using **past dates** (2025 instead of 2026).

### Test Results Summary

| Location | Code | Response Size | Vehicles | Extras |
|----------|------|---------------|----------|--------|
| Milan Malpensa T1 | MXP | 317 bytes | 0 | 0 |
| Milan Linate | LIN | 317 bytes | 0 | 0 |
| **Bergamo Orio al Serio** | **BGY** | **76,559 bytes** | **11** | **20** ✅ |
| **Milan Central Station** | **MIT** | **76,529 bytes** | **11** | **20** ✅ |
| **Rome Termini Station** | **RMT** | **90,281 bytes** | **13** | **20** ✅ |

---

## Protection Plans (7 Options)

LocautoRent offers **7 protection/insurance options** (codes: `136`, `147`, `145`, `140`, `146`, `6`, `43`):

| Code | Name (EN/IT) | Daily Price | 4-Day Total | Description |
|------|--------------|-------------|--------------|-------------|
| `136` | **Don't Worry** | €35.99/day | €143.96 | Premium comprehensive coverage |
| `147` | **Smart Cover** | €28.79/day | €115.16 | Mid-tier coverage option |
| `145` | **Body Protection** | €14.40/day | €57.60 | Bodywork damage protection |
| `140` | **Glass and Wheels** | €9.36/day | €37.44 | Glass and tire coverage |
| `146` | **Super Theft Protection** | €9.36/day | €37.44 | Theft protection |
| `43` | **Assistenza Stradale / Roadside Plus** | €5.04/day | €20.16 | Roadside assistance |
| `6` | **Protez.Anti Infortuni / Protect. Against Injuries** | €10.08/day | €40.32 | Personal accident protection |

### XML Structure

```xml
<PricedEquip>
  <Equipment EquipType="136">
    <Description>Don't Worry</Description>
  </Equipment>
  <Charge CurrencyCode="EUR" Amount="35.99" TaxInclusive="true">
    <Calculation UnitName="Day" Quantity="4" />
  </Charge>
</PricedEquip>
```

---

## Optional Extras (8+ Options)

LocautoRent offers **8 main optional extras** (codes: `19`, `78`, `137`, `138`, `54`, `55`, `77`, `89`):

| Code | Name (EN/IT) | Daily Price | Total Price | Unit |
|------|--------------|-------------|-------------|------|
| `19` | **Navigatore satellitare / GPS** | €10.08/day | €40.32 | Per day |
| `78` | **Seggiolino per bambini / Child seat** | €16.20/day | €64.80 | Per day |
| `137` | **Guidatore Aggiuntivo / Additional Driver** | €10.08/day | €40.32 | Per day |
| `138` | **Guida in pool 3+** | €12.96/day | €51.84 | Per day |
| `54` | **Maggiorazione pneum. invernali / Winter tires fee** | €14.40/day | €57.60 | Per day |
| `55` | **Catene da neve / Snow chains** | **FREE** | €0.00 | Per day |
| `77` | **Tolleranza oraria / Time** | €28.79 | €28.79 | Per rental |
| `89` | **Bau the way** (dog carrier) | €49.90 | €49.90 | Per rental |

---

## Additional Fees (One-Way / Young Driver)

| Code | Name | Price | Unit |
|------|------|-------|------|
| `35` | Viaggio a lasciare Sardegna / Sardinia one way fee | €719.80 | Per rental |
| `23` | Viaggio a lasciare / One way fee | €86.38 | Per rental |
| `84` | Viaggio a lasciare Promo | €14.40 | Per rental |
| `87` | Viaggio a lasciare lunga tratta | **FREE** | Per rental |
| `139` | Guidatore Giovane / Young Driver 19-24 | €21.59/day | Per day |

---

## Sample Vehicle Response

```xml
<VehAvail>
  <VehAvailCore Status="Available">
    <Vehicle AirConditionInd="true"
             TransmissionType="Manual"
             PassengerQuantity="4"
             BaggageQuantity="2"
             Code="MDMR">
      <VehMakeModel ModelYear="Fiat Panda" />
      <PictureURL>https://nextrent.locautorent.com/img/car_web/b.jpg</PictureURL>
      <VehType DoorCount="5" />
    </Vehicle>
    <TotalCharge CurrencyCode="EUR" RateTotalAmount="186.60" />
    <PricedEquips>
      <!-- Protection Plans -->
      <PricedEquip>
        <Equipment EquipType="136">
          <Description>Don't Worry</Description>
        </Equipment>
        <Charge CurrencyCode="EUR" Amount="35.99" TaxInclusive="true">
          <Calculation UnitName="Day" Quantity="4" />
        </Charge>
      </PricedEquip>
      <!-- Optional Extras -->
      <PricedEquip>
        <Equipment EquipType="19">
          <Description>Navigatore satellitare / GPS</Description>
        </Equipment>
        <Charge CurrencyCode="EUR" Amount="10.08" TaxInclusive="true">
          <Calculation UnitName="Day" Quantity="4" />
        </Charge>
      </PricedEquip>
    </PricedEquips>
  </VehAvailCore>
</VehAvail>
```

---

## Vehicle List from Bergamo (BGY)

| Vehicle | SIPP Code | Price (4 days) | Transmission | Seats | Doors |
|---------|-----------|----------------|--------------|-------|-------|
| Fiat Panda | MDMR | €186.60 | Manual | 4 | 5 |
| Opel Corsa | EDMR | €196.40 | Manual | 5 | 5 |
| Volkswagen Golf | CDMR | €216.28 | Manual | 5 | 5 |
| Volkswagen T-Roc Automatic | IDAR | €439.98 | Automatic | 5 | 5 |
| Jeep Renegade | CXMR | €200.28 | Manual | 5 | 5 |
| Peugeot 3008 Automatic | IFAR | €1,208.80 | Automatic | 5 | 5 |
| Ford Focus SW | SVMR | €412.23 | Manual | 5 | 5 |
| Audi A4 Avant Automatic | LWAR | €615.41 | Automatic | 5 | 5 |
| Skoda Octavia SW Automatic | SWAR | €1,051.15 | Automatic | 5 | 5 |
| Seat Leon SW Automatic | CWAR | €998.55 | Automatic | 5 | 5 |
| Volkswagen T-Cross Automatic | CDAR | €417.98 | Automatic | 5 | 5 |

---

## API Structure

### Endpoint
**URL:** `https://nextrent.locautorent.com/webservices/nextRentOTAService.asmx`
**Protocol:** SOAP/OTA (Open Travel Alliance) XML

### Authentication
```xml
<RequestorID ID_Context="[USERNAME]" MessagePassword="[PASSWORD]"/>
```

### Request Parameters

| Parameter | Required | Example | Description |
|-----------|----------|---------|-------------|
| `locationCode` | Yes | `BGY` | Location code (predefined) |
| `PickUpDateTime` | Yes | `2026-02-01T10:00:00+01:00` | Pickup date/time |
| `ReturnDateTime` | Yes | `2026-02-05T10:00:00+01:00` | Return date/time |
| `Age` | Yes | `35` | Driver age |

---

## Response Parsing (LocautoRentService.php:348-365)

```php
// Parse extras/equipment from PricedEquips
$pricedEquips = $vehAvailCore->PricedEquips;
if ($pricedEquips) {
  $extras = [];
  foreach ($pricedEquips->PricedEquip ?? [] as $pricedEquip) {
    $equipment = $pricedEquip->Equipment;
    $charge = $pricedEquip->Charge;
    if ($equipment) {
      $extras[] = [
        'code' => (string) ($equipment['EquipType'] ?? ''),
        'description' => (string) ($equipment->Description ?? ''),
        'amount' => $charge ? (float) ($charge['Amount'] ?? 0) : 0,
        'currency' => $charge ? (string) ($charge['CurrencyCode'] ?? 'EUR') : 'EUR',
      ];
    }
  }
  $vehicle['extras'] = $extras;
}
```

---

## Extras Categorization (LocautoRentController.php:176-187)

```php
// Protection plans (insurance/coverage)
$protectionCodes = ['136', '147', '145', '140', '146', '6', '43'];

// Optional extras (GPS, child seats, etc.)
$optionalExtraCodes = ['19', '78', '137', '138', '54', '55', '77', '89'];

$protectionPlans = array_filter($allExtras, function ($extra) use ($protectionCodes) {
    return in_array($extra['code'], $protectionCodes) && $extra['amount'] > 0;
});

$optionalExtras = array_filter($allExtras, function ($extra) use ($optionalExtraCodes) {
    return in_array($extra['code'], $optionalExtraCodes) && $extra['amount'] > 0;
});
```

---

## Key Differences: LocautoRent vs GreenMotion

| Feature | GreenMotion | LocautoRent |
|---------|-------------|-------------|
| **API Protocol** | REST/XML | **SOAP/OTA XML** |
| **Packages** | YES (BAS/PLU/PRE/PMP) | **NO** - Single price only |
| **Protection Plans** | Included in packages | **7 separate options** |
| **Extras** | Yes | **Yes (20+ options)** |
| **Currency** | Varies by location | **EUR only** |
| **Locations** | Dynamic search | **100+ predefined codes** |

---

## Important Notes

1. **No Package Plans:** Unlike GreenMotion's BAS/PLU/PRE/PMP structure, LocautoRent has **one base price** per vehicle. Protection plans and extras are **add-ons** selected at booking time.

2. **All Extras Have Daily Pricing:** Most extras are priced **per day** (UnitName="Day"), not per rental. Only a few like "Bau the way" (dog carrier) are per rental.

3. **Prices Vary by Vehicle:** Protection plan prices vary by vehicle category. Higher-end vehicles have more expensive protection plans.

4. **Date Format:** Must use **future dates** relative to current date. Using past dates returns empty responses.

5. **Location Availability:** Not all location codes return data. BGY, MIT, RMT work well; MXP, LIN may be location-specific.

---

## Response Files Location

All test responses saved in:
```
/opt/lampp/htdocs/CarRental/tests/api/locauto_rent/
├── API_RESPONSE_ANALYSIS.md          (this file)
├── locauto_01_vehicles_mxp.xml       (317 bytes - empty)
├── locauto_02_vehicles_lin.xml       (317 bytes - empty)
├── locauto_03_vehicles_bgy.xml       (76,559 bytes - 11 vehicles ✅)
├── locauto_04_vehicles_mit.xml       (76,529 bytes - 11 vehicles ✅)
└── locauto_05_vehicles_rmt.xml       (90,281 bytes - 13 vehicles ✅)
```

---

## Test Command

**File:** `/opt/lampp/htdocs/CarRental/app/Console/Commands/LocautoRentTestApiCommand.php`

**Usage:** `php artisan locauto:test_api`

This command tests all LocautoRent API endpoints and saves responses for analysis.
