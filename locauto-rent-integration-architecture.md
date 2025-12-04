# Locauto Rent Integration Architecture Plan

## Executive Summary

This document outlines the comprehensive architectural design for integrating Locauto Rent into the Laravel car rental platform. The integration follows the existing service-based architecture patterns established by GreenMotion and other providers, while adapting to Locauto's specific SOAP/OTA API requirements and Pay on Arrival business model.

## Current Architecture Analysis

### Existing Provider Integration Patterns

Based on codebase analysis, the current architecture follows these patterns:

#### 1. Service Layer Architecture
- **Service Classes**: Dedicated service classes for each provider (`GreenMotionService`, `OkMobilityService`)
- **XML/SOAP Communication**: HTTP-based XML requests with SimpleXML parsing
- **Configuration Management**: Environment-based credentials in `config/services.php`
- **Error Handling**: Comprehensive logging with request/response tracking

#### 2. Database Schema Pattern
- **Provider-Specific Tables**: Each provider has dedicated booking tables
- **Standardized Fields**: Common fields across providers (vehicle_id, location_id, dates, customer_details)
- **Provider-Specific Fields**: Custom fields for provider-specific data
- **JSON Storage**: Flexible storage for extras, API responses, and customer details

#### 3. Controller Structure
- **Dedicated Controllers**: Provider-specific controllers (`GreenMotionController`, `OkMobilityBookingController`)
- **Consistent Method Naming**: Standardized method patterns across providers
- **Inertia.js Integration**: Seamless frontend rendering with Vue.js components
- **API Endpoints**: RESTful API endpoints for AJAX operations

#### 4. Frontend Architecture
- **Component-Based**: Dedicated Vue components for each provider
- **Search Integration**: Unified search components with provider filtering
- **Booking Flow**: Multi-step booking process with Stripe integration
- **Responsive Design**: Mobile-optimized interfaces with Tailwind CSS

## Locauto Rent Integration Requirements

### API Specifications
- **Protocol**: SOAP/WSDL with OTA 2003/05 schema
- **Authentication**: RequestorID with username/password
- **Endpoints**: Test and production WSDL URLs
- **Data Format**: XML-based requests/responses
- **Location Codes**: XML location codes (not IATA)
- **Vehicle Codes**: SIPP codes for vehicle identification
- **Equipment**: Special equipment handling for extras

### Business Model Differences
- **Payment**: Pay on Arrival (POA) model
- **Deposits**: Different deposit structure
- **Taxes/Fees**: Specific tax and fee handling requirements
- **Cancellation**: Custom cancellation policies

## Detailed Architecture Design

### 1. Service Layer Implementation

#### LocautoRentService Class

**File**: `/opt/lampp/htdocs/CarRental/app/Services/LocautoRentService.php`

**Core Responsibilities**:
- SOAP client management and WSDL handling
- OTA request/response XML generation and parsing
- Authentication and session management
- Error handling and retry logic
- Data transformation between OTA and internal formats

**Key Methods**:
```php
class LocautoRentService
{
    public function __construct() { /* SOAP client initialization */ }
    
    // Core API methods
    public function getVehicleAvailability($locationCode, $pickupDate, $returnDate, $vehicleInfo)
    public function getRateDetails($rateQualifier, $rateInfo)
    public function makeReservation($reservationData)
    public function cancelReservation($confirmationNumber, $cancelReason)
    public function retrieveReservation($confirmationNumber)
    
    // Location and vehicle methods
    public function getLocationDetails($locationCode)
    public function getVehicleInformation($vehicleInfo)
    
    // Utility methods
    public function transformVehicles($otaVehicles)
    public function transformPricing($otaPricing)
    public function transformExtras($otaExtras)
}
```

**SOAP Client Configuration**:
```php
private function createSoapClient()
{
    $options = [
        'soap_version' => SOAP_1_1,
        'trace' => true,
        'exceptions' => true,
        'cache_wsdl' => WSDL_CACHE_NONE,
        'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
        'connection_timeout' => 30,
    ];
    
    return new SoapClient($this->wsdlUrl, $options);
}
```

### 2. Database Schema Design

#### LocautoRent Booking Table

**Migration File**: `/opt/lampp/htdocs/CarRental/database/migrations/2025_12_04_create_locauto_rent_bookings_table.php`

```php
Schema::create('locauto_rent_bookings', function (Blueprint $table) {
    $table->id();
    $table->string('locauto_booking_ref')->nullable()->unique();
    $table->string('confirmation_number')->nullable();
    $table->string('pickup_location_code');
    $table->string('dropoff_location_code');
    $table->date('pickup_date');
    $table->time('pickup_time');
    $table->date('return_date');
    $table->time('return_time');
    $table->string('vehicle_code'); // SIPP code
    $table->string('rate_qualifier')->nullable();
    $table->integer('driver_age');
    $table->json('customer_details');
    $table->json('selected_extras')->nullable();
    $table->json('special_equipment')->nullable(); // Locauto-specific equipment
    $table->decimal('vehicle_total', 10, 2);
    $table->decimal('extras_total', 10, 2)->default(0);
    $table->decimal('taxes_total', 10, 2)->default(0);
    $table->decimal('deposit_amount', 10, 2)->default(0);
    $table->string('currency', 3);
    $table->decimal('grand_total', 10, 2);
    $table->string('payment_type', 10)->default('POA'); // Pay on Arrival
    $table->decimal('credit_card_auth', 10, 2)->default(0); // Pre-auth amount
    $table->text('special_instructions')->nullable();
    $table->string('booking_status')->default('pending');
    $table->string('ota_status')->nullable(); // OTA status codes
    $table->json('api_request')->nullable(); // Store OTA request XML
    $table->json('api_response')->nullable(); // Store OTA response XML
    $table->timestamp('confirmed_at')->nullable();
    $table->timestamp('cancelled_at')->nullable();
    $table->string('cancellation_reason')->nullable();
    $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
    $table->timestamps();
    
    // Indexes for performance
    $table->index(['pickup_date', 'return_date']);
    $table->index('locauto_booking_ref');
    $table->index('booking_status');
});
```

#### Locauto Rent Model

**File**: `/opt/lampp/htdocs/CarRental/app/Models/LocautoRentBooking.php`

```php
class LocautoRentBooking extends Model
{
    use HasFactory;
    
    protected $table = 'locauto_rent_bookings';
    
    protected $fillable = [
        // All table fields
    ];
    
    protected $casts = [
        'customer_details' => 'array',
        'selected_extras' => 'array',
        'special_equipment' => 'array',
        'api_request' => 'array',
        'api_response' => 'array',
        'pickup_date' => 'date',
        'return_date' => 'date',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    // Scopes for common queries
    public function scopeConfirmed($query)
    {
        return $query->where('booking_status', 'confirmed');
    }
    
    public function scopePending($query)
    {
        return $query->where('booking_status', 'pending');
    }
}
```

### 3. Controller Architecture

#### Main LocautoRentController

**File**: `/opt/lampp/htdocs/CarRental/app/Http/Controllers/LocautoRentController.php`

**Structure**:
```php
class LocautoRentController extends Controller
{
    public function __construct(private LocautoRentService $locautoRentService)
    {
    }
    
    // Vehicle search and display
    public function searchVehicles(Request $request)
    public function getVehicleAvailability(Request $request)
    public function showVehicleDetails(Request $request, $locale, $vehicleCode)
    
    // Location management
    public function getLocations(Request $request)
    public function getLocationDetails(Request $request, $locationCode)
    public function getDropoffLocations(Request $request, $pickupCode)
    
    // Booking flow
    public function showBookingPage(Request $request, $locale, $vehicleCode)
    public function processBooking(Request $request)
    public function bookingConfirmation(Request $request, $confirmationNumber)
    
    // Utility methods
    private function transformOtaVehicles(SimpleXMLElement $otaResponse)
    private function transformOtaPricing(SimpleXMLElement $otaResponse)
    private function transformOtaExtras(SimpleXMLElement $otaResponse)
    private function parseLocationDetails(SimpleXMLElement $otaResponse)
}
```

#### Booking Management Controller

**File**: `/opt/lampp/htdocs/CarRental/app/Http/Controllers/LocautoRentBookingController.php`

**Responsibilities**:
- Customer booking management
- Booking history and details
- Cancellation handling
- Payment processing (for any pre-pay components)

### 4. Frontend Component Architecture

#### Search Component

**File**: `/opt/lampp/htdocs/CarRental/resources/js/Components/LocautoRentSearchComponent.vue`

**Features**:
- Location autocomplete with Locauto location codes
- Date/time selection with Locauto availability constraints
- Driver age validation
- Real-time vehicle availability checking

#### Vehicle Listing Component

**File**: `/opt/lampp/htdocs/CarRental/resources/js/Pages/LocautoRentCars.vue`

**Features**:
- Vehicle grid/list view with filters
- SIPP code-based vehicle categorization
- POA-specific pricing display
- Special equipment options
- Pay on Arrival messaging

#### Vehicle Detail Component

**File**: `/opt/lampp/htdocs/CarRental/resources/js/Pages/LocautoRentSingle.vue`

**Features**:
- Detailed vehicle information
- SIPP code explanation
- Equipment and extras selection
- Deposit and fee breakdown
- Pay on Arrival information

#### Booking Component

**File**: `/opt/lampp/htdocs/CarRental/resources/js/Pages/LocautoRentBooking.vue`

**Features**:
- Multi-step booking process
- Customer information forms
- Driver license validation
- Equipment selection
- Booking confirmation (no payment required)

### 5. Location Mapping Strategy

#### Unified Locations Integration

**Update File**: `/opt/lampp/htdocs/CarRental/public/unified_locations.json`

**Structure**:
```json
{
    "unified_location_id": 12345,
    "name": "Milan Malpensa Airport",
    "city": "Milan",
    "country": "Italy",
    "latitude": 45.630067,
    "longitude": 8.728084,
    "providers": [
        {
            "provider": "locauto_rent",
            "pickup_id": "MXP",
            "dropoffs": ["FCO", "LIN"],
            "location_details": {
                "airport": true,
                "terminal": "Terminal 1",
                "desk_location": "Arrivals Hall"
            }
        }
    ],
    "our_location_id": "internal_abc123"
}
```

#### Location Mapping Service

**File**: `/opt/lampp/htdocs/CarRental/app/Services/LocationMappingService.php`

**Responsibilities**:
- Convert internal location IDs to Locauto codes
- Handle location name search and autocomplete
- Map location amenities and features
- Support one-way rental locations

### 6. Vehicle and Equipment Mapping

#### SIPP Code Integration

**File**: `/opt/lampp/htdocs/CarRental/app/Services/VehicleMappingService.php`

**SIPP Code Mapping**:
```php
private $sippMapping = [
    'ECMR' => [
        'category' => 'Economy',
        'type' => '2/4 door',
        'transmission' => 'Manual',
        'fuel' => 'Petrol',
        'ac' => true,
        'example_vehicles' => ['Fiat Panda', 'Renault Twingo'],
        'description' => 'Economy 2/4 door Manual Petrol Air conditioning'
    ],
    'CCAR' => [
        'category' => 'Compact',
        'type' => '2/4 door',
        'transmission' => 'Manual',
        'fuel' => 'Petrol',
        'ac' => true,
        'example_vehicles' => ['Ford Focus', 'Volkswagen Golf'],
        'description' => 'Compact 2/4 door Manual Petrol Air conditioning'
    ],
    // Add more SIPP codes as needed
];
```

#### Equipment Translation Service

**File**: `/opt/lampp/htdocs/CarRental/app/Services/EquipmentMappingService.php`

**Equipment Code Mapping**:
```php
private $equipmentMapping = [
    'GPS' => [
        'name' => 'GPS Navigation',
        'description' => 'Portable GPS device',
        'daily_rate' => 10.00,
        'category' => 'navigation'
    ],
    'CSEAT' => [
        'name' => 'Child Seat',
        'description' => 'Child safety seat (2-4 years)',
        'daily_rate' => 8.00,
        'category' => 'safety'
    ],
    'BOOST' => [
        'name' => 'Booster Seat',
        'description' => 'Booster seat (4-8 years)',
        'daily_rate' => 6.00,
        'category' => 'safety'
    ],
    // Additional equipment codes
];
```

### 7. Configuration and Security

#### Environment Configuration

**File**: `/opt/lampp/htdocs/CarRental/config/services.php` (Update)

```php
'locauto_rent' => [
    'test_wsdl' => env('LOCAUTO_RENT_TEST_WSDL'),
    'production_wsdl' => env('LOCAUTO_RENT_PRODUCTION_WSDL'),
    'username' => env('LOCAUTO_RENT_USERNAME'),
    'password' => env('LOCAUTO_RENT_PASSWORD'),
    'requestor_id' => env('LOCAUTO_RENT_REQUESTOR_ID'),
    'agency_iata' => env('LOCAUTO_RENT_AGENCY_IATA'),
    'default_currency' => env('LOCAUTO_RENT_DEFAULT_CURRENCY', 'EUR'),
    'timeout' => env('LOCAUTO_RENT_TIMEOUT', 30),
    'retry_attempts' => env('LOCAUTO_RENT_RETRY_ATTEMPTS', 3),
],
```

#### Environment Variables

**File**: `.env` (Additions)

```env
# Locauto Rent Configuration
LOCAUTO_RENT_TEST_WSDL=https://test.locautorent.com/OTA/OTA_VehAvailRate.asmx?WSDL
LOCAUTO_RENT_PRODUCTION_WSDL=https://www.locautorent.com/OTA/OTA_VehAvailRate.asmx?WSDL
LOCAUTO_RENT_USERNAME=your_username
LOCAUTO_RENT_PASSWORD=your_password
LOCAUTO_RENT_REQUESTOR_ID=your_requestor_id
LOCAUTO_RENT_AGENCY_IATA=your_iata_code
LOCAUTO_RENT_DEFAULT_CURRENCY=EUR
LOCAUTO_RENT_TIMEOUT=30
LOCAUTO_RENT_RETRY_ATTEMPTS=3
```

### 8. Error Handling and Logging

#### Custom Exception Classes

**File**: `/opt/lampp/htdocs/CarRental/app/Exceptions/LocautoRentException.php`

```php
class LocautoRentException extends Exception
{
    public function __construct($message, $code = 0, Throwable $previous = null, $otaError = null)
    {
        parent::__construct($message, $code, $previous);
        $this->otaError = $otaError;
    }
    
    public function getOtaError()
    {
        return $this->otaError;
    }
}
```

#### Enhanced Logging Strategy

**File**: `/opt/lampp/htdocs/CarRental/app/Services/LocautoRentService.php` (Integration)

```php
private function logApiCall($method, $request, $response, $error = null)
{
    $logData = [
        'provider' => 'locauto_rent',
        'method' => $method,
        'request' => $this->maskSensitiveData($request),
        'response' => $response ? $this->maskSensitiveData($response) : null,
        'error' => $error,
        'timestamp' => now()->toISOString(),
    ];
    
    if ($error) {
        Log::channel('locauto_rent_errors')->error('API Error', $logData);
    } else {
        Log::channel('locauto_rent_api')->info('API Call', $logData);
    }
}
```

### 9. Route Configuration

#### Web Routes

**File**: `/opt/lampp/htdocs/CarRental/routes/web.php` (Additions)

```php
// Locauto Rent Routes
Route::get('/locauto-rent-cars', [LocautoRentController::class, 'showVehicles'])->name('locauto-rent-cars');
Route::get('/locauto-rent-car/{code}', [LocautoRentController::class, 'showVehicleDetails'])->name('locauto-rent-car');
Route::get('/locauto-rent-booking/{code}/checkout', [LocautoRentController::class, 'showBookingPage'])->name('locauto-rent-booking');
Route::get('/locauto-rent-booking-success/{confirmation}', [LocautoRentController::class, 'bookingConfirmation'])->name('locauto-rent-booking-success');

// API Routes for Locauto Rent
Route::prefix('api/locauto-rent')->group(function () {
    Route::get('/locations', [LocautoRentController::class, 'getLocations'])->name('locauto-rent.api.locations');
    Route::get('/vehicles', [LocautoRentController::class, 'getVehicleAvailability'])->name('locauto-rent.api.vehicles');
    Route::get('/location/{code}/details', [LocautoRentController::class, 'getLocationDetails'])->name('locauto-rent.api.location-details');
    Route::post('/booking', [LocautoRentController::class, 'processBooking'])->name('locauto-rent.api.booking');
    Route::get('/booking/{confirmation}', [LocautoRentController::class, 'getBookingDetails'])->name('locauto-rent.api.booking-details');
});
```

### 10. Pay on Arrival Adaptation

#### POA-Specific Logic

**Key Differences from Prepayment Models**:

1. **No Stripe Integration**: Remove payment processing for the main booking amount
2. **Credit Card Authorization**: Implement pre-auth for deposit amount only
3. **Confirmation Flow**: Different booking confirmation process
4. **Customer Communication**: Specific POA messaging and terms

**Implementation**:

```php
public function processBooking(Request $request)
{
    // Validate booking data
    $validatedData = $request->validate([
        // Standard validation rules
    ]);
    
    // No payment processing for main amount (POA model)
    // Only process credit card authorization for deposit if required
    
    try {
        $booking = $this->locautoRentService->makeReservation($validatedData);
        
        // Send confirmation emails with specific POA instructions
        $this->sendPoaConfirmationEmails($booking);
        
        return response()->json([
            'message' => 'Booking confirmed. Pay on arrival.',
            'confirmation_number' => $booking->confirmation_number,
            'deposit_amount' => $booking->deposit_amount,
            'payment_instructions' => $this->getPaymentInstructions($booking),
        ]);
        
    } catch (LocautoRentException $e) {
        Log::error('Locauto Rent booking failed', ['error' => $e->getMessage()]);
        return response()->json(['error' => $e->getMessage()], 400);
    }
}
```

### 11. Testing Strategy

#### Unit Tests

**Files**: `/opt/lampp/htdocs/CarRental/tests/Unit/LocautoRent/`

- `LocautoRentServiceTest.php` - Test service methods
- `LocautoRentBookingTest.php` - Test booking model
- `LocationMappingTest.php` - Test location mapping
- `VehicleMappingTest.php` - Test SIPP code mapping

#### Feature Tests

**Files**: `/opt/lampp/htdocs/CarRental/tests/Feature/LocautoRent/`

- `VehicleSearchTest.php` - Test vehicle search functionality
- `BookingFlowTest.php` - Test complete booking process
- `ApiEndpointsTest.php` - Test API endpoints
- `PoaFlowTest.php` - Test Pay on Arrival specific functionality

#### Integration Tests

**SOAP/WSDL Mock Testing**:
```php
public function testVehicleAvailabilityWithSoapMock()
{
    // Mock SOAP client responses
    $mockSoapClient = $this->mockSoapClient([
        'OTA_VehAvailRateRS' => $this->getMockAvailabilityResponse()
    ]);
    
    $this->app->instance(SoapClient::class, $mockSoapClient);
    
    $service = new LocautoRentService();
    $result = $service->getVehicleAvailability('MXP', '2025-12-15', '2025-12-20', []);
    
    $this->assertNotEmpty($result);
}
```

### 12. Deployment Strategy

#### Phased Rollout

**Phase 1: Backend Implementation**
1. Service layer development
2. Database migrations
3. Controller implementation
4. API endpoint testing

**Phase 2: Frontend Integration**
1. Vue component development
2. Search integration
3. Booking flow implementation
4. UI/UX testing

**Phase 3: Testing and Validation**
1. Integration testing
2. User acceptance testing
3. Performance testing
4. Security testing

**Phase 4: Production Deployment**
1. Environment configuration
2. Production testing
3. Go-live with monitoring
4. Post-launch optimization

#### Environment Setup

**Development**:
- Use Locauto test WSDL
- Enable comprehensive logging
- Mock payment processing
- Debug mode enabled

**Staging**:
- Production-like environment
- Real test credentials
- Full integration testing
- Performance monitoring

**Production**:
- Production WSDL endpoints
- Optimized configuration
- Error monitoring
- Analytics tracking

## Implementation Timeline

### Sprint 1: Foundation (Week 1-2)
- Database schema and migrations
- Basic service layer structure
- SOAP client integration
- Configuration setup

### Sprint 2: Core Functionality (Week 3-4)
- Vehicle availability API integration
- Location mapping implementation
- Basic controller methods
- Unit testing

### Sprint 3: Booking Flow (Week 5-6)
- Reservation API integration
- Booking controller implementation
- POA flow adaptation
- Frontend components development

### Sprint 4: Integration and Testing (Week 7-8)
- Frontend-backend integration
- API endpoint completion
- Comprehensive testing
- Documentation

### Sprint 5: Deployment and Optimization (Week 9-10)
- Production deployment
- Performance optimization
- Monitoring setup
- Post-launch support

## Critical Files for Implementation

### Backend Core Files
1. `/opt/lampp/htdocs/CarRental/app/Services/LocautoRentService.php` - Main SOAP service integration
2. `/opt/lampp/htdocs/CarRental/app/Http/Controllers/LocautoRentController.php` - Primary controller logic
3. `/opt/lampp/htdocs/CarRental/app/Models/LocautoRentBooking.php` - Booking data model
4. `/opt/lampp/htdocs/CarRental/database/migrations/2025_12_04_create_locauto_rent_bookings_table.php` - Database schema

### Frontend Core Files
1. `/opt/lampp/htdocs/CarRental/resources/js/Pages/LocautoRentCars.vue` - Vehicle listing page
2. `/opt/lampp/htdocs/CarRental/resources/js/Pages/LocautoRentSingle.vue` - Vehicle details page
3. `/opt/lampp/htdocs/CarRental/resources/js/Pages/LocautoRentBooking.vue` - Booking page
4. `/opt/lampp/htdocs/CarRental/resources/js/Components/LocautoRentSearchComponent.vue` - Search component

### Configuration Files
1. `/opt/lampp/htdocs/CarRental/config/services.php` - Service configuration
2. `/opt/lampp/htdocs/CarRental/routes/web.php` - Route definitions
3. `/opt/lampp/htdocs/CarRental/.env` - Environment variables

### Integration Files
1. `/opt/lampp/htdocs/CarRental/public/unified_locations.json` - Location mapping data
2. `/opt/lampp/htdocs/CarRental/app/Services/LocationMappingService.php` - Location mapping logic
3. `/opt/lampp/htdocs/CarRental/app/Services/VehicleMappingService.php` - SIPP code mapping

## Success Metrics

### Technical Metrics
- API response time < 2 seconds
- 99.9% uptime for integration
- Zero data loss in booking transfers
- Comprehensive test coverage (>90%)

### Business Metrics
- Successful booking completion rate >95%
- Customer satisfaction score >4.5/5
- Reduced booking abandonment due to POA model
- Increased conversion through location coverage

### Operational Metrics
- Error rate < 1%
- Average booking processing time < 30 seconds
- Real-time availability accuracy >98%
- Customer support ticket reduction

This comprehensive architecture design provides a solid foundation for integrating Locauto Rent while maintaining consistency with existing patterns and accommodating the unique requirements of the OTA/SOAP API and Pay on Arrival business model.