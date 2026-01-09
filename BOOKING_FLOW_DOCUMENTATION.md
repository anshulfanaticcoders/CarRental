# Car Rental Booking & Payment Flow Documentation

## Overview

This document explains the complete end-to-end booking and payment flow for the car rental application. The system supports multiple vehicle providers (Internal, GreenMotion, Adobe, LocautoRent, OK Mobility, Renteon, Wheelsys) with a unified SPA (Single Page Application) booking experience.

---

## Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           USER JOURNEY                                      │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  1. SEARCH RESULTS (SearchResults.vue)                                   │
│     ↓                                                                        │
│  2. SELECT VEHICLE & PACKAGE (CarListingCard.vue)                          │
│     ↓                                                                        │
│  3. CHOOSE EXTRAS (BookingExtrasStep.vue)                                 │
│     ↓                                                                        │
│  4. CHECKOUT & PAYMENT (BookingCheckoutStep.vue + StripeCheckout.vue)       │
│     ↓                                                                        │
│  5. PAYMENT PROCESSING (StripeCheckoutController.php)                        │
│     ↓                                                                        │
│  6. BOOKING CREATION (StripeBookingService.php)                             │
│     ↓                                                                        │
│  7. PROVIDER RESERVATION (Optional - for external providers)               │
│     ↓                                                                        │
│  8. SUCCESS PAGE                                                             │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Phase 1: Search Results Display

### File: `resources/js/Pages/SearchResults.vue`
**Lines:** 1-1789

**Purpose:** Main search results page that displays all available vehicles from multiple providers.

**Key Responsibilities:**
1. Display vehicle cards in grid/list view
2. Handle client-side filtering (price, brand, category, transmission, fuel, seats)
3. Manage booking state transitions
4. Display map with vehicle markers
5. Handle currency conversion

**Key State Variables:**
```javascript
// SPA Booking State
const bookingStep = ref('results'); // 'results' | 'extras' | 'checkout'
const selectedVehicle = ref(null);
const selectedPackage = ref(null);
const selectedProtectionCode = ref(null);
```

**Booking Step Handlers:**

1. **handlePackageSelection** (Lines 234-257)
   - Triggered when user selects a vehicle package
   - Stores selected vehicle, package, and protection
   - Changes `bookingStep` to 'extras'
   - Fetches location details for GreenMotion/USave vehicles

2. **handleBackToResults** (Lines 259-264)
   - Returns to results view
   - Clears selections

3. **handleProceedToCheckout** (Lines 268-273)
   - Receives booking data from extras step
   - Changes `bookingStep` to 'checkout'

**Data Flow:**
```javascript
SearchResults.vue receives props:
{
  vehicles: Object,           // Paginated vehicle collection
  okMobilityVehicles: Object, // Separate OK Mobility collection
  renteonVehicles: Object,    // Separate Renteon collection
  optionalExtras: Array,      // GreenMotion extras
  locationName: String,
  // ... other props
}
```

---

## Phase 2: Vehicle Selection & Package Choice

### File: `resources/js/Components/CarListingCard.vue`
**Lines:** 1-1465

**Purpose:** Individual vehicle card component that handles package selection for different providers.

**Key Features:**
- Image slider for internal vehicles
- Display vehicle specs (transmission, seats, doors, luggage, fuel, AC)
- Provider-specific package selection
- Protection plans modal

**Package Selection by Provider:**

1. **GreenMotion/USave** (Lines 106-109, 231-244)
   ```javascript
   const isGreenMotionOrUSave = computed(() => {
       return props.vehicle.source === 'greenmotion' || props.vehicle.source === 'usave';
   });

   // Packages: BAS (Basic), PLU (Plus), PRE (Premium), PMP (Premium Plus)
   const sortedProducts = computed(() => {
       const order = ['BAS', 'PLU', 'PRE', 'PMP'];
       return order.map(type => products.find(p => p.type === type)).filter(Boolean);
   });
   ```

2. **LocautoRent** (Lines 208-241)
   ```javascript
   // Protection plans from extras array
   const locautoProtectionPlans = computed(() => {
       const protectionCodes = ['136', '147', '145', '140', '146', '6', '43'];
       return extras.filter(extra => protectionCodes.includes(extra.code));
   });
   ```

3. **Adobe Cars** (Lines 114-197)
   ```javascript
   // Protection plans: PLI (Liability), LDW (Car), SPP (Extended)
   const adobeProtectionPlans = computed(() => {
       // Returns array of protection options
   });
   ```

4. **Internal Vehicles** (Lines 364-374)
   ```javascript
   // Uses vendor_plans and addons
   const selectInternalPackage = () => {
       emit('select-package', {
           vehicle: props.vehicle,
           package: 'BAS',
           protection_code: null,
           vendorPlans: props.vehicle.vendor_plans || [],
           addons: props.vehicle.addons || []
       });
   };
   ```

**Emitted Data Structure:**
```javascript
emit('select-package', {
    vehicle: vehicleObject,      // Full vehicle data
    package: 'BAS',              // Package type code
    protection_code: 'LDW',      // Protection plan code (optional)
    protection_amount: 15.50,    // Protection amount (optional)
    total_price: 250.00          // Calculated total
});
```

**Modal for Protection Plans** (Lines 780-945):
- Shows all available protection plans
- Displays daily price and total price
- Lists benefits/features
- Handles plan selection

---

## Phase 3: Extras Selection & Booking Summary

### File: `resources/js/Components/BookingExtrasStep.vue`
**Lines:** 1-1620

**Purpose:** Display package options, optional extras, and booking summary before checkout.

**Key Sections:**

1. **Package Upgrade Section** (Lines 676-748)
   - Shows available packages (Basic, Plus, Premium, Premium Plus)
   - Displays daily price and total price
   - Lists benefits/features
   - Handles package switching

2. **LocautoRent Protection Section** (Lines 751-835)
   - Shows Basic + 7 protection plans
   - Displays protection amount per day
   - Shows total with protection

3. **Optional Extras Section** (Lines 837-893)
   - Displays extras like GPS, child seats, etc.
   - Shows daily rate for each extra
   - Handles quantity selection
   - First 2 extras free with Premium Plus (PMP) package

4. **Booking Summary** (Lines 896-1143)
   - Sticky sidebar with:
     - Vehicle details
     - Pickup/Dropoff locations with dates
     - Vehicle specs icons
     - Price breakdown
     - Total amount
     - Payable amount (percentage based)
     - Action buttons

**Data Processing:**

```javascript
// Extras Total Calculation
const extrasTotal = computed(() => {
    let total = 0;
    for (const [id, qty] of Object.entries(selectedExtras.value)) {
        let extra = findExtraById(id);
        if (extra && !isExtraFree(id)) {
            const dailyRate = extra.daily_rate;
            total += dailyRate * props.numberOfDays * qty;
        }
    }
    return total;
});

// Grand Total Calculation
const grandTotal = computed(() => {
    const pkgPrice = parseFloat(currentProduct.value?.total || 0);
    const mandatoryExtra = isAdobeCars.value ? adobeMandatoryProtection.value : 0;
    return (pkgPrice + mandatoryExtra + extrasTotal.value).toFixed(2);
});

// Payable Amount (percentage based)
const payableAmount = computed(() => {
    return (parseFloat(grandTotal.value) * (props.paymentPercentage / 100)).toFixed(2);
});
```

**Emitted Data to Checkout:**
```javascript
emit('proceed-to-checkout', {
    package: 'BAS',
    protection_code: 'LDW',
    protection_amount: 15.50,
    extras: selectedExtras,           // Map of selected extras with quantities
    detailedExtras: getSelectedExtrasDetails,
    totals: {
        grandTotal: 250.00,
        payableAmount: 37.50,         // 15%
        pendingAmount: 212.50
    }
});
```

---

## Phase 4: Checkout Form & Payment Initiation

### File: `resources/js/Components/StripeCheckout.vue`
**Lines:** 1-168

**Purpose:** Initiate Stripe checkout process.

**Process Flow:**

1. **Validate Booking Data** (Lines 58-84)
   ```javascript
   const requiredFields = ['customer', 'vehicle_id', 'pickup_date', 'return_date', 'total_amount'];
   const missingFields = requiredFields.filter(field => !props.bookingData[field]);
   ```

2. **Save to Session Storage** (Lines 87-91)
   ```javascript
   sessionStorage.setItem('pendingBookingData', JSON.stringify(props.bookingData));
   ```

3. **Create Stripe Checkout Session** (Lines 93-98)
   ```javascript
   const response = await axios.post(`/${locale}/payment/charge`, {
       bookingData: props.bookingData,
   });
   ```

4. **Redirect to Stripe** (Lines 107-112)
   ```javascript
   const { error } = await stripe.redirectToCheckout({ sessionId });
   ```

**Error Handling:**
- Emits 'error' event to parent component
- Displays loading spinner during processing

---

## Phase 5: Stripe Session Creation (Backend)

### File: `app/Http/Controllers/StripeCheckoutController.php`
**Lines:** 1-308

**Purpose:** Create Stripe Checkout Session with proper metadata.

**Method:** `createSession(Request $request)`

**Key Processing Steps:**

1. **Validate Request** (Lines 29-50)
   ```php
   $validated = $request->validate([
       'vehicle' => 'required|array',
       'package' => 'required|string',
       'extras' => 'nullable|array',
       'customer' => 'required|array',
       'pickup_date' => 'required|string',
       // ... more fields
   ]);
   ```

2. **Calculate Payment Amounts** (Lines 52-58)
   ```php
   $payableSetting = PayableSetting::first();
   $paymentPercentage = $payableSetting ? $payableSetting->payment_percentage : 15;
   $payableAmount = round($validated['total_amount'] * ($paymentPercentage / 100), 2);
   $pendingAmount = round($validated['total_amount'] - $payableAmount, 2);
   ```

3. **Get Vehicle Image** (Lines 60-81)
   ```php
   if ($validated['vehicle']['source'] === 'internal') {
       // Find primary image from images array
       foreach ($validated['vehicle']['images'] as $img) {
           if ($img['image_type'] === 'primary') {
               $vehicleImage = $img['image_url'];
               break;
           }
       }
   }
   ```

4. **Build Stripe Line Items** (Lines 95-109)
   ```php
   $lineItems = [[
       'price_data' => [
           'currency' => strtolower($currency),
           'product_data' => [
               'name' => $validated['vehicle']['brand'] . ' ' . $validated['vehicle']['model'],
               'description' => $validated['package'] . ' Package - ' . $validated['number_of_days'] . ' day(s)',
               'images' => [$vehicleImage],
           ],
           'unit_amount' => (int) ($payableAmount * 100), // cents
       ],
       'quantity' => 1,
   ]];
   ```

5. **Prepare Metadata** (Lines 112-148)
   ```php
   $metadata = [
       'vehicle_id' => $validated['vehicle']['id'],
       'vehicle_source' => $validated['vehicle']['source'],
       'vehicle_brand' => $validated['vehicle']['brand'],
       'vehicle_model' => $validated['vehicle']['model'],
       'package' => $validated['package'],
       'pickup_date' => $validated['pickup_date'],
       'pickup_time' => $validated['pickup_time'],
       // ... all booking details
       'extras' => json_encode($validated['extras']),
       'extras_data' => json_encode($validated['detailed_extras']),
   ];
   ```

6. **Determine Payment Methods** (Lines 158-180)
   ```php
   $availableMethods = ['card'];
   if (strtoupper($currency) === 'EUR') {
       $availableMethods[] = 'bancontact';
   }
   $klarnaCurrencies = ['EUR', 'USD', 'GBP', 'DKK', 'NOK', 'SEK', 'CHF'];
   if (in_array(strtoupper($currency), $klarnaCurrencies)) {
       $availableMethods[] = 'klarna';
   }
   ```

7. **Create Stripe Session** (Lines 182-195)
   ```php
   $session = StripeSession::create([
       'payment_method_types' => $paymentMethodTypes,
       'line_items' => $lineItems,
       'mode' => 'payment',
       'success_url' => route('booking.success', ['locale' => $currentLocale]) . '?session_id={CHECKOUT_SESSION_ID}',
       'cancel_url' => route('booking.cancel', ['locale' => $currentLocale]),
       'metadata' => $metadata,
   ]);
   ```

8. **Return Response** (Lines 203-207)
   ```php
   return response()->json([
       'success' => true,
       'session_id' => $session->id,
       'url' => $session->url,
   ]);
   ```

---

## Phase 6: Booking Creation & Provider Reservation

### File: `app/Services/StripeBookingService.php`
**Lines:** 1-624

**Purpose:** Create booking record and trigger provider reservations after successful payment.

**Method:** `createBookingFromSession($session)`

**Processing Steps:**

1. **Idempotency Check** (Lines 29-34)
   ```php
   $existingBooking = Booking::where('stripe_session_id', $session->id)->first();
   if ($existingBooking) {
       return $existingBooking;
   }
   ```

2. **Start Database Transaction** (Line 39)
   ```php
   DB::beginTransaction();
   ```

3. **Find or Create Customer** (Lines 43, 169-185)
   ```php
   $customer = $this->findOrCreateCustomer($metadata);
   ```

4. **Create Booking Record** (Lines 53-80)
   ```php
   $booking = Booking::create([
       'booking_number' => Booking::generateBookingNumber(),
       'customer_id' => $customer->id,
       'vehicle_id' => $vehicleId,  // null for external providers
       'provider_source' => $metadata->vehicle_source,
       'provider_vehicle_id' => $metadata->vehicle_id,
       'plan' => $metadata->package,
       'total_days' => (int) $metadata->number_of_days,
       'base_price' => (float) $metadata->total_amount,
       'amount_paid' => (float) $metadata->payable_amount,
       'pending_amount' => (float) $metadata->pending_amount,
       'payment_status' => 'partial',
       'booking_status' => 'confirmed',
       'stripe_session_id' => $session->id,
       // ... more fields
   ]);
   ```

5. **Create Payment Record** (Lines 84-92)
   ```php
   BookingPayment::create([
       'booking_id' => $booking->id,
       'payment_method' => $metadata->payment_method ?? 'stripe',
       'transaction_id' => $session->payment_intent,
       'amount' => (float) $metadata->payable_amount,
       'payment_status' => 'succeeded',
   ]);
   ```

6. **Create Extras Records** (Lines 94-134)
   ```php
   $extrasData = json_decode($metadata->extras_data ?? '[]', true);
   foreach ($extrasData as $extraItem) {
       BookingExtra::create([
           'booking_id' => $booking->id,
           'extra_type' => 'optional',
           'extra_name' => $extraItem['name'],
           'quantity' => (int) $extraItem['qty'],
           'price' => (float) $extraItem['total'],
       ]);
   }
   ```

7. **Trigger Provider Reservations** (Lines 139-154)
   - LocautoRent: `triggerLocautoReservation()`
   - GreenMotion/USave: `triggerGreenMotionReservation()`
   - Adobe: `triggerAdobeReservation()`
   - Internal: No API call needed

### Provider-Specific Reservation Logic

#### LocautoRent Reservation (Lines 190-308)
```php
protected function triggerLocautoReservation($booking, $metadata)
{
    $locautoService = app(LocautoRentService::class);

    // Build reservation data
    $reservationData = [
        'pickup_date' => $metadata->pickup_date,
        'pickup_time' => $metadata->pickup_time,
        'return_date' => $metadata->dropoff_date,
        'return_time' => $metadata->dropoff_time,
        'pickup_location_code' => $metadata->pickup_location_code,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'sipp_code' => $metadata->sipp_code,
        'extras' => $extras,  // Protection + optional extras
        'driver_age' => $metadata->customer_driver_age ?? 35,
    ];

    $xmlResponse = $locautoService->makeReservation($reservationData);

    // Parse confirmation from XML response
    $confirmationNumber = (string) $xmlObject->xpath('//ota:UniqueID')[0]['ID'];
}
```

#### GreenMotion Reservation (Lines 313-419)
```php
protected function triggerGreenMotionReservation($booking, $metadata)
{
    $greenMotionService = app(GreenMotionService::class);
    $greenMotionService->setProvider($booking->provider_source);

    // Build customer details
    $customerDetails = [
        'firstname' => $firstName,
        'surname' => $lastName,
        'phone' => $metadata->customer_phone,
        'email' => $metadata->customer_email,
    ];

    // Build extras array
    $extras = [];
    foreach ($rawExtras as $ex) {
        if ($ex['qty'] > 0) {
            $extras[] = [
                'id' => $ex['id'],
                'option_qty' => $ex['qty'],
                'option_total' => $ex['total'],
            ];
        }
    }

    $xmlResponse = $greenMotionService->makeReservation(
        $metadata->pickup_location_code,
        $metadata->pickup_date,
        $metadata->pickup_time,
        // ... parameters
    );
}
```

#### Adobe Reservation (Lines 429-622)
```php
protected function triggerAdobeReservation($booking, $metadata)
{
    // Fetch protections and extras from Adobe API (MANDATORY)
    $categoryItems = $this->adobeCarService->getProtectionsAndExtras(
        $pickupOffice,
        $category,
        ['startdate' => $startDate, 'enddate' => $endDate]
    );

    // Build items array for booking
    $bookingItems = [];
    foreach ($allItems as $item) {
        if (shouldInclude) {
            $bookingItems[] = [
                'code' => $code,
                'quantity' => $quantity,
                'total' => $item['total'],
                'type' => $item['type'],
                'included' => true,
            ];
        }
    }

    // Create booking
    $adobeParams = [
        'bookingNumber' => 0,
        'category' => $category,  // Single-letter code like 'e', 'n'
        'startdate' => $startDate,
        'pickupoffice' => $pickupOffice,
        'enddate' => $endDate,
        'returnoffice' => $returnOffice,
        'name' => $firstName,
        'lastName' => $lastName,
        'items' => $bookingItems,  // MANDATORY
    ];

    $adobeResponse = $this->adobeCarService->createBooking($adobeParams);
}
```

---

## Phase 7: Vehicle Search & Data Aggregation

### File: `app/Http/Controllers/SearchController.php`
**Lines:** 1-1668

**Purpose:** Search and aggregate vehicles from multiple providers.

**Main Method:** `search(Request $request)` (Lines 50-1396)

**Process Flow:**

1. **Validate Request** (Lines 52-88)
   ```php
   $validated = $request->validate([
       'where' => 'nullable|string',
       'date_from' => 'nullable|date',
       'date_to' => 'nullable|date|after:date_from',
       'provider' => 'nullable|string',
       'provider_pickup_id' => 'nullable|string',
       'unified_location_id' => 'nullable|integer',
       // ... more fields
   ]);
   ```

2. **Calculate Rental Days** (Lines 91-94)
   ```php
   $dtStart = Carbon::parse($validated['date_from'] . ' ' . $validated['start_time']);
   $dtEnd = Carbon::parse($validated['date_to'] . ' ' . $validated['end_time']);
   $rentalDays = max(1, (int) ceil($dtStart->diffInMinutes($dtEnd) / 1440));
   ```

3. **Fetch Internal Vehicles** (Lines 96-364)
   ```php
   $internalVehiclesQuery = Vehicle::query()
       ->whereIn('status', ['available', 'rented'])
       ->with(['images', 'bookings', 'vendorProfile', 'benefits', 'vendorPlans', 'addons']);

   // Apply filters
   // Apply date conflicts
   // Apply location filters
   // Apply attribute filters
   // Return collection with reviews and ratings
   ```

4. **Fetch Provider Vehicles** (Lines 368-1230)
   - GreenMotion/USave (Lines 473-645)
   - Adobe (Lines 645-798)
   - OK Mobility (Lines 799-963)
   - Wheelsys (Lines 964-1067)
   - LocautoRent (Lines 1068-1188)
   - Renteon (Lines 1189-1228)

5. **Normalize Vehicle Data** (Each provider section)
   ```php
   $providerVehicles->push([
       'id' => $providerToFetch . '_' . $vehicle['id'],
       'source' => $providerToFetch,
       'brand' => $brandName,
       'model' => $vehicleModel,
       'category' => $categoryName,
       'image' => $imageUrl,
       'price_per_day' => (float) ($totalPrice / $rentalDays),
       'total_price' => $totalPrice,
       'currency' => $currency,
       'transmission' => $transmission,
       'fuel' => $fuel,
       'seating_capacity' => $seats,
       'latitude' => (float) $locationLat,
       'longitude' => (float) $locationLng,
       'products' => $products,
       // ... more fields
   ]);
   ```

6. **Parse SIPP Codes** (Lines 1469-1639)
   ```php
   private function parseSippCode($sipp, $provider = null)
   {
       // SIPP format: 4-character code
       // Char 1: Category (M=Mini, E=Economy, C=Compact, etc.)
       // Char 2: Type (D=2-door, W=Wagon, S=SUV, etc.)
       // Char 3: Transmission (M=Manual, A=Automatic)
       // Char 4: Fuel/AC (P=Petrol, D=Diesel, etc.)

       return [
           'category' => 'Economy',
           'type_name' => '2-3 Door',
           'transmission' => 'automatic',
           'fuel' => 'petrol',
           'doors' => 4,
           'seating_capacity' => 5,
           'air_conditioning' => true,
       ];
   }
   ```

7. **Return Data to Frontend** (Lines 1377-1395)
   ```php
   return Inertia::render('SearchResults', [
       'vehicles' => $vehicles,              // Paginated collection
       'okMobilityVehicles' => $okMobilityVehiclesPaginated,
       'renteonVehicles' => $renteonVehiclesPaginated,
       'filters' => $validated,
       'brands' => $combinedBrands,
       'categories' => $categoriesFromOptions,
       'optionalExtras' => array_values($searchOptionalExtras),
       'locationName' => $validated['location_name'],
   ]);
   ```

---

## File Count Summary

### Frontend Files: 4
1. **SearchResults.vue** (1789 lines) - Main search results page with SPA state management
2. **CarListingCard.vue** (1465 lines) - Individual vehicle card with package selection
3. **BookingExtrasStep.vue** (1620 lines) - Extras selection and booking summary
4. **StripeCheckout.vue** (168 lines) - Stripe checkout initiator

### Backend Files: 3
1. **SearchController.php** (1668 lines) - Search endpoint and provider aggregation
2. **StripeCheckoutController.php** (308 lines) - Stripe session creation
3. **StripeBookingService.php** (624 lines) - Booking creation and provider reservations

### Total: 7 main files (6,062 lines)

---

## Data Flow Summary

### From Frontend to Backend:

1. **User selects package** → `CarListingCard.vue` emits event
2. **SearchResults.vue** receives → Updates state, shows `BookingExtrasStep.vue`
3. **User confirms extras** → `BookingExtrasStep.vue` emits data
4. **SearchResults.vue** receives → Shows `BookingCheckoutStep.vue`
5. **User clicks "Book Now"** → `StripeCheckout.vue` calls API
6. **StripeCheckoutController** creates Stripe session → Returns session ID
7. **User completes payment** → Stripe webhook triggers
8. **StripeBookingService** creates booking → Calls provider APIs
9. **Success page** → Displays confirmation

---

## Key Integration Points

### 1. Provider Detection
```javascript
const isGreenMotionOrUSave = vehicle.source === 'greenmotion' || vehicle.source === 'usave';
const isLocautoRent = vehicle.source === 'locauto_rent';
const isAdobeCars = vehicle.source === 'adobe';
const isInternal = vehicle.source === 'internal';
```

### 2. Currency Handling
- Frontend: `useCurrency()` composable
- Backend: Currency symbol mapping in SearchController
- Stripe: ISO currency codes (USD, EUR, GBP, etc.)

### 3. Price Normalization
- Adobe: Total (TDR) divided by rental days
- GreenMotion: Product totals directly
- LocautoRent: Per-day rate × days
- Internal: price_per_day directly

### 4. SIPP Code Parsing
- Standard 4-character ACRISS code
- Determines: category, body type, transmission, fuel/AC
- Provider-specific overrides for accuracy

### 5. Metadata Flow
```javascript
// StripeCheckout.vue → StripeCheckoutController
bookingData = {
    vehicle: {...},
    customer: {...},
    extras: {...},
    detailed_extras: [...],
    protection_code: 'LDW',
    // ... 40+ fields
}

// StripeCheckoutController → StripeBookingService (via Stripe metadata)
session.metadata = {
    vehicle_id: '...',
    package: 'BAS',
    extras: '[{...}]',
    extras_data: '[{...}]',
    // ... all booking details
}
```

---

## Error Handling Strategy

### Frontend:
- Try-catch in all async operations
- User-friendly error messages
- Fallback to placeholder images
- Loading states during API calls

### Backend:
- Database transactions (rollback on error)
- Comprehensive logging
- Idempotency checks
- Graceful degradation for provider failures

### Payment:
- Stripe webhook retry logic
- Booking status updates via notes field
- Partial payment support (deposit + balance)

---

## Security Considerations

1. **Server-side validation** of all booking data
2. **Idempotency keys** to prevent duplicate bookings
3. **Stripe metadata** for secure data passing
4. **Transaction integrity** with database rollbacks
5. **Provider credential management** via environment variables
6. **Customer data privacy** (GDPR compliance)

---

## Future Enhancements

1. **Real-time availability updates** via websockets
2. **Multi-provider price comparison** in one view
3. **Loyalty program integration**
4. **Dynamic pricing based on demand**
5. **Multi-language support** for provider communications
6. **Mobile app** with native payment integrations
