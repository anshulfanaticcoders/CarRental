# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel-based car rental platform with Vue.js frontend, supporting multiple user roles (Customer, Vendor, Admin) and featuring multi-language support (en, fr, nl, es, ar). The platform integrates with third-party APIs (GreenMotion, U-Save) and includes comprehensive booking management, payment processing, and messaging systems.

## Core Technology Stack

- **Backend**: Laravel 10.x with PHP 8.1+
- **Frontend**: Vue 3 with Inertia.js, TypeScript, Tailwind CSS
- **Build Tools**: Vite
- **Database**: MySQL
- **Payment**: Stripe
- **Real-time**: Laravel Echo with Pusher
- **File Storage**: AWS S3
- **Authentication**: Laravel Sanctum

## Development Commands

### Frontend Development
```bash
npm run dev          # Start development server with hot reload
npm run build        # Build for production
```

### Backend Development
```bash
php artisan serve                   # Start Laravel development server
php artisan migrate                 # Run database migrations
php artisan tinker                  # Open Laravel REPL
php artisan queue:work             # Process queue jobs
php artisan storage:link           # Create storage symbolic link
```

### Testing
```bash
php artisan test                    # Run PHPUnit tests
vendor/bin/pint                     # Run code style checks
```

### Cache & Configuration
```bash
php artisan config:cache           # Cache configuration
php artisan route:cache            # Cache routes
php artisan view:cache             # Cache views
php artisan optimize:clear         # Clear all caches
```

## Architecture Overview

### Role-Based Access Control
The system uses three primary roles with distinct middleware:
- **Admin**: Full platform management (`middleware(['auth', 'role:admin'])`)
- **Vendor**: Vehicle and booking management (`middleware(['auth', 'role:vendor'])`)
- **Customer**: Booking and profile management (`middleware(['auth', 'role:customer'])`)

### Multi-Language Structure
Routes are locale-prefixed (`/{locale}/...`) with automatic locale detection and session storage. Admin routes are outside locale prefixes.

### Key Integrations
- **GreenMotion API**: External car rental inventory (`GreenMotionController`)
- **U-Save API**: Additional rental provider integration
- **Stripe**: Payment processing (`PaymentController`)
- **Tapfiliate**: Affiliate management system

### Directory Structure

#### Controllers Organization
- `app/Http/Controllers/Admin/` - Admin-specific controllers
- `app/Http/Controllers/Vendor/` - Vendor-specific controllers
- `app/Http/Controllers/Auth/` - Authentication controllers
- Direct root-level controllers for shared functionality

#### Frontend Structure
- `resources/js/Pages/` - Vue page components organized by feature
- `resources/js/Pages/AdminDashboardPages/` - Admin interface components
- `resources/js/Pages/Vendor/` - Vendor interface components
- `resources/js/Pages/Profile/` - Customer profile components

### Key Features Implementation

#### Booking System
- Multi-step booking flow with Stripe integration
- Vehicle availability checking and blocking dates
- Damage protection with before/after photo documentation
- Status management (pending, confirmed, completed, cancelled)

#### Messaging System
- Real-time messaging between customers and vendors
- Polling mechanism for new messages (`MessageController`)
- Booking-specific conversation threads

#### Document Management
- User document verification system
- Vendor document upload and approval
- Bulk document processing capabilities

#### Vehicle Management
- Bulk vehicle upload via CSV (`BulkVehicleUploadController`)
- Image management with bulk operations
- Vehicle categories, features, and addons
- Integration with external vehicle APIs

### Important Configuration

#### Environment Variables
Key `.env` variables requiring configuration:
- Database connection settings
- Stripe keys (STRIPE_KEY, STRIPE_SECRET)
- Pusher configuration for real-time features
- AWS S3 credentials for file storage
- Third-party API credentials (GreenMotion, U-Save)
- Tapfiliate affiliate system credentials

#### Route Structure
- Admin routes: `/admin/*` (outside locale prefix)
- Customer/Vendor routes: `/{locale}/*` (with locale prefix)
- API routes: Mix of authenticated and open endpoints
- Fallback route for 404 handling

### Development Notes

#### Database
- Uses Laravel migrations and seeders
- Eloquent models with relationships
- Soft deletes implemented for key models

#### Frontend Patterns
- Inertia.js for SPA-like navigation
- Composition API with TypeScript
- Tailwind CSS for styling
- Component-based architecture with shared components

#### Queue System
- Background job processing for emails and notifications
- Should run `php artisan queue:work` in production

#### Security
- Laravel Sanctum for API authentication
- Role-based middleware protection
- Input validation and sanitization
- CSRF protection on all forms

## Development Best Practices

### Code Quality Standards

#### PHP Standards
- **PSR-12 Compliance**: Follow PSR-12 coding standards for all PHP code
- **Class Naming**: Use PascalCase for classes (e.g., `VehicleController`, `LocationSearchService`)
- **Method Naming**: Use camelCase for methods (e.g., `getVehiclesByCategory`, `calculateTotalPrice`)
- **Variable Naming**: Use descriptive camelCase variable names
- **Constants**: Use UPPER_SNAKE_CASE for constants (e.g., `MAX_VEHICLE_IMAGES`, `DEFAULT_PAGINATION_SIZE`)

#### Laravel Standards
- **Controller Single Responsibility**: Each controller should handle one specific domain
- **Request Validation**: Always validate input using Form Request classes or controller validation
- **Resource Responses**: Use Laravel Resource classes for consistent API responses
- **Error Handling**: Implement proper exception handling with custom error messages
- **Logging**: Use structured logging with proper log levels

#### Vue.js Standards
- **Composition API**: Use Composition API with `<script setup>` syntax
- **Component Naming**: Use PascalCase for component files and kebab-case in templates
- **Props Validation**: Always define prop types and validation rules
- **Event Naming**: Use kebab-case for custom events (e.g., `vehicle-selected`, `booking-updated`)
- **Reactive Data**: Use `ref()` for primitive values, `reactive()` for objects

#### TypeScript Standards
- **Type Definitions**: Always define types for props, emits, and reactive data
- **Interface Naming**: Use PascalCase with descriptive names (e.g., `Vehicle`, `BookingData`)
- **Generic Usage**: Leverage generics for reusable components
- **Strict Mode**: Enable strict TypeScript configuration
- **Type Imports**: Use `import type` for type-only imports

#### Performance Guidelines
- **Database Optimization**: Use eager loading to prevent N+1 queries
- **Caching Strategy**: Implement appropriate caching for frequently accessed data
- **Lazy Loading**: Use lazy loading for heavy components and images
- **Code Splitting**: Implement route-based code splitting
- **Asset Optimization**: Compress images and minimize asset sizes

#### Security Best Practices
- **Input Sanitization**: Always sanitize user input
- **XSS Prevention**: Use proper escaping in templates
- **SQL Injection Prevention**: Use parameterized queries (Eloquent handles this automatically)
- **Authentication**: Implement proper authentication and authorization checks
- **Data Validation**: Validate all input data on both client and server side

### Laravel-Specific Best Practices

#### Controller Patterns

**Dependency Injection**
```php
// ✅ Good: Constructor injection
class VehicleController extends Controller
{
    public function __construct(
        private LocationSearchService $locationSearchService,
        private VehicleRepository $vehicleRepository
    ) {}

    public function index(Request $request)
    {
        return Inertia::render('Vehicles/Index', [
            'vehicles' => $this->vehicleRepository->paginate($request->all())
        ]);
    }
}

// ❌ Bad: Manual instantiation
class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $service = new LocationSearchService(); // Avoid this
        // ...
    }
}
```

**Request Validation**
```php
// ✅ Good: Form Request validation
class VehicleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'price_per_day' => 'required|numeric|min:0'
        ];
    }
}

// ✅ Good: Controller validation
public function store(VehicleRequest $request)
{
    // Validation already passed
    $validated = $request->validated();
    // Process vehicle creation
}

// ❌ Bad: Inline complex validation
public function store(Request $request)
{
    $request->validate([
        'brand' => 'required|string|max:255',
        // Complex validation rules should be in Form Request
    ]);
}
```

**Response Formatting**
```php
// ✅ Good: Using Inertia responses
public function show(Vehicle $vehicle)
{
    return Inertia::render('Vehicles/Show', [
        'vehicle' => $vehicle->load(['images', 'category', 'vendor'])
    ]);
}

// ✅ Good: API Resource responses
public function apiIndex(Request $request)
{
    return VehicleResource::collection(
        Vehicle::with(['images', 'category'])->paginate(15)
    );
}
```

#### Model Standards

**Fillable Arrays and Mass Assignment**
```php
// ✅ Good: Explicit fillable fields
class Vehicle extends Model
{
    protected $fillable = [
        'vendor_id',
        'category_id',
        'brand',
        'model',
        'price_per_day',
        // ... other fields
    ];

    protected $casts = [
        'price_per_day' => 'decimal:2',
        'features' => 'array',
        'featured' => 'boolean',
    ];
}

// ❌ Bad: Using guarded
class Vehicle extends Model
{
    protected $guarded = ['id']; // Avoid this pattern
}
```

**Relationships and Scopes**
```php
// ✅ Good: Proper relationships
class Vehicle extends Model
{
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(VehicleImage::class);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'available');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('featured', true);
    }
}
```

**Accessors and Mutators**
```php
// ✅ Good: Useful accessors
class Vehicle extends Model
{
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price_per_day, 2);
    }

    public function getPrimaryImageAttribute(): ?VehicleImage
    {
        return $this->images->firstWhere('image_type', 'primary');
    }
}
```

#### Database Standards

**Migration Naming and Structure**
```php
// ✅ Good: Descriptive migration names
// 2025_01_01_060741_create_vehicles_table.php
// 2025_01_15_104835_create_plans_table.php
// 2025_03_21_101218_add_featured_to_vehicles_table.php

class CreateVehiclesTable extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('vehicle_categories')->onDelete('cascade');

            $table->string('brand');
            $table->string('model');
            $table->decimal('price_per_day', 10, 2);
            $table->enum('status', ['available', 'rented', 'maintenance'])->default('available');

            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            $table->json('features')->nullable();
            $table->boolean('featured')->default(false);

            $table->timestamps();

            // Indexes for performance
            $table->index(['status', 'featured']);
            $table->index(['vendor_id', 'status']);
        });
    }
}
```

**Query Optimization**
```php
// ✅ Good: Eager loading to prevent N+1
$vehicles = Vehicle::with(['images', 'category', 'vendor'])
    ->where('status', 'available')
    ->paginate(15);

// ✅ Good: Using scopes for complex queries
$vehicles = Vehicle::available()
    ->featured()
    ->inCategory($categoryId)
    ->paginate(15);

// ❌ Bad: N+1 queries
$vehicles = Vehicle::all(); // Will cause N+1 when accessing relationships
foreach ($vehicles as $vehicle) {
    echo $vehicle->category->name; // Separate query for each vehicle
}
```

#### Service Layer Usage

**When to Create Services**
- Complex business logic that doesn't belong in controllers
- External API integrations
- Data processing and transformation
- Reusable utility functions

**Service Example**
```php
// ✅ Good: Service for external API integration
class GreenMotionService
{
    public function __construct(
        private HttpClient $httpClient,
        private CacheManager $cache
    ) {}

    public function getAvailableVehicles(array $params): Collection
    {
        $cacheKey = 'greenmotion_vehicles_' . md5(serialize($params));

        return $this->cache->remember($cacheKey, 3600, function () use ($params) {
            $response = $this->httpClient->get('https://api.greenmotion.com/vehicles', [
                'query' => $params
            ]);

            return collect($response->json()['data']);
        });
    }
}
```

### Vue.js 3 & Inertia.js Standards

#### Component Architecture

**Component Structure**
```vue
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import type { Vehicle, VehicleImage } from '@/types'

interface Props {
  vehicle: Vehicle
  isFavorite?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isFavorite: false
})

interface Emits {
  favorite: [vehicleId: number]
  book: [vehicleId: number]
}

const emit = defineEmits<Emits>()

// Reactive data
const isExpanded = ref(false)
const currentImageIndex = ref(0)

// Computed properties
const primaryImage = computed(() => {
  return props.vehicle.images.find((img: VehicleImage) => img.image_type === 'primary')
})

const formattedPrice = computed(() => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(props.vehicle.price_per_day)
})

// Methods
const toggleFavorite = () => {
  emit('favorite', props.vehicle.id)
}

const bookVehicle = () => {
  emit('book', props.vehicle.id)
}

onMounted(() => {
  // Component initialization logic
})
</script>

<template>
  <div class="vehicle-card">
    <img
      v-if="primaryImage"
      :src="`/storage/${primaryImage.image_path}`"
      :alt="`${vehicle.brand} ${vehicle.model}`"
      class="vehicle-image"
    />

    <div class="vehicle-info">
      <h3>{{ vehicle.brand }} {{ vehicle.model }}</h3>
      <p class="price">{{ formattedPrice }}/day</p>

      <div class="actions">
        <button
          @click="toggleFavorite"
          :class="['favorite-btn', { active: isFavorite }]"
        >
          {{ isFavorite ? 'Remove from Favorites' : 'Add to Favorites' }}
        </button>

        <button
          @click="bookVehicle"
          class="book-btn"
        >
          Book Now
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.vehicle-card {
  @apply border rounded-lg overflow-hidden;
}

.vehicle-image {
  @apply w-full h-48 object-cover;
}

.vehicle-info {
  @apply p-4;
}

.price {
  @apply text-xl font-bold text-green-600;
}

.actions {
  @apply flex gap-2 mt-4;
}

.favorite-btn {
  @apply px-4 py-2 border rounded hover:bg-gray-50;
}

.favorite-btn.active {
  @apply bg-red-50 text-red-600 border-red-300;
}

.book-btn {
  @apply px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700;
}
</style>
```

**State Management Patterns**
```vue
<script setup lang="ts">
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'

// ✅ Good: Form handling with proper state management
const form = useForm({
  brand: '',
  model: '',
  price_per_day: '',
  features: [] as string[]
})

// ✅ Good: Loading states
const isSubmitting = ref(false)
const isLoading = ref(false)

// ✅ Good: Error handling
const errors = ref<Record<string, string[]>>({})

// ✅ Good: Computed properties for derived state
const isFormValid = computed(() => {
  return form.brand && form.model && form.price_per_day
})

// ✅ Good: Watch for changes
watch(() => form.brand, (newValue) => {
  // Auto-generate slug or other dependent fields
})

const submit = async () => {
  isSubmitting.value = true

  try {
    await form.post('/vehicles')
    // Success handling
  } catch (error) {
    // Error handling
    errors.value = form.errors
  } finally {
    isSubmitting.value = false
  }
}
</script>
```

**Performance Optimization**
```vue
<script setup lang="ts">
import { defineAsyncComponent, ref, onMounted } from 'vue'

// ✅ Good: Lazy loading heavy components
const HeavyMapComponent = defineAsyncComponent(() =>
  import('@/components/HeavyMapComponent.vue')
)

const ImageGallery = defineAsyncComponent(() =>
  import('@/components/ImageGallery.vue')
)

// ✅ Good: Conditional rendering with v-show vs v-if
const showDetails = ref(false)

// Use v-show for frequent toggling (DOM remains)
// Use v-if for conditional rendering (DOM removed/added)
</script>

<template>
  <div>
    <!-- v-show: Element stays in DOM, just hidden -->
    <div v-show="showDetails" class="details">
      <!-- Content that toggles frequently -->
    </div>

    <!-- v-if: Element removed from DOM -->
    <HeavyMapComponent v-if="showMap" />

    <!-- Lazy loaded component -->
    <ImageGallery v-if="showGallery" :images="vehicleImages" />
  </div>
</template>
```

### KISS & DRY Principles

#### KISS (Keep It Simple, Stupid) Implementation

**Function Complexity Guidelines**
- **Maximum 20 lines per function** - If longer, break into smaller functions
- **Single responsibility** - Each function should do one thing well
- **Clear naming** - Function names should clearly describe what they do
- **Avoid nested conditionals** - Use early returns and guard clauses

**Examples:**
```php
// ✅ Good: Simple, focused function
public function calculateTotalPrice(float $dailyRate, int $days): float
{
    return $dailyRate * $days;
}

// ✅ Good: Early returns and guard clauses
public function canBookVehicle(User $user, Vehicle $vehicle): bool
{
    if (!$user->hasVerifiedEmail()) {
        return false;
    }

    if ($vehicle->status !== 'available') {
        return false;
    }

    return true;
}

// ❌ Bad: Complex nested logic
public function canBookVehicle(User $user, Vehicle $vehicle): bool
{
    if ($user->hasVerifiedEmail()) {
        if ($vehicle->status === 'available') {
            if ($vehicle->vendor->is_active) {
                if (!$vehicle->isBlockedForDates($startDate, $endDate)) {
                    return true;
                }
            }
        }
    }
    return false;
}
```

**Class Responsibility Guidelines**
- **Maximum 200 lines per class** - If larger, consider splitting
- **Single responsibility principle** - Each class should have one reason to change
- **Cohesive methods** - All methods should relate to the class's core purpose

#### DRY (Don't Repeat Yourself) Implementation

**Code Duplication Detection**
Before creating new functionality, always search for existing implementations:
- **Controller methods**: Check existing controllers for similar patterns
- **Validation rules**: Look for similar validation in existing Form Requests
- **Vue components**: Check for reusable components that can be extended
- **Database queries**: Look for similar query patterns in existing models

**Service Extraction Guidelines**
```php
// ✅ Good: Extract reusable logic into services
class PricingService
{
    public function calculateVehiclePrice(
        Vehicle $vehicle,
        int $days,
        array $addons = []
    ): array {
        $basePrice = $vehicle->price_per_day * $days;

        if ($days >= 7) {
            $basePrice *= (1 - $vehicle->weekly_discount / 100);
        }

        if ($days >= 30) {
            $basePrice *= (1 - $vehicle->monthly_discount / 100);
        }

        $addonsTotal = $this->calculateAddonsPrice($addons);

        return [
            'base_price' => $basePrice,
            'addons_total' => $addonsTotal,
            'total' => $basePrice + $addonsTotal
        ];
    }

    private function calculateAddonsPrice(array $addons): float
    {
        return collect($addons)->sum(function ($addon) {
            return $addon['price'] * $addon['quantity'];
        });
    }
}

// Usage in multiple controllers
class BookingController extends Controller
{
    public function calculatePrice(BookingRequest $request, PricingService $pricingService)
    {
        return $pricingService->calculateVehiclePrice(
            $request->vehicle,
            $request->days,
            $request->addons
        );
    }
}

class QuoteController extends Controller
{
    public function getQuote(QuoteRequest $request, PricingService $pricingService)
    {
        return $pricingService->calculateVehiclePrice(
            $request->vehicle,
            $request->days,
            $request->addons
        );
    }
}
```

**Component Reusability Patterns**
```vue
<!-- ✅ Good: Reusable card component -->
<!-- components/VehicleCard.vue -->
<script setup lang="ts">
interface Props {
  vehicle: Vehicle
  showActions?: boolean
  variant?: 'default' | 'compact' | 'detailed'
}

const props = withDefaults(defineProps<Props>(), {
  showActions: true,
  variant: 'default'
})
</script>

<template>
  <div :class="['vehicle-card', `vehicle-card--${variant}`]">
    <!-- Reusable card structure -->
  </div>
</template>

<!-- Usage in different contexts -->
<VehicleCard :vehicle="vehicle" variant="compact" />
<VehicleCard :vehicle="vehicle" :show-actions="false" variant="detailed" />
```

#### Pre-Development Code Analysis Checklist

**Before Creating New Functionality:**
1. **Search Existing Controllers**: Look for similar endpoints or logic
2. **Check Existing Models**: Verify if similar relationships or methods exist
3. **Review Vue Components**: Look for reusable components that can be extended
4. **Examine Database Schema**: Check if existing tables can accommodate new requirements
5. **Look at Routes**: Ensure similar route patterns don't already exist

**Search Patterns:**
```bash
# Search for similar functionality
grep -r "vehicle.*price" app/Http/Controllers/
grep -r "calculate.*price" app/
grep -r "Vehicle.*price" resources/js/
```

**Decision Tree for New Functionality:**
1. **Does similar functionality exist?**
   - Yes → Extend existing code
   - No → Proceed to step 2
2. **Can existing code be refactored?**
   - Yes → Refactor and reuse
   - No → Create new functionality
3. **Is this reusable logic?**
   - Yes → Create service/helper
   - No → Keep in controller/component

#### Code Review Checklist

**Pre-Commit Validation:**
- [ ] Code follows PSR-12 standards
- [ ] Functions are under 20 lines
- [ ] Classes have single responsibility
- [ ] No code duplication exists
- [ ] Proper error handling implemented
- [ ] Security considerations addressed
- [ ] Performance implications considered
- [ ] Tests written for new functionality
- [ ] Documentation updated
- [ ] Existing functionality still works

### MySQL Database Best Practices

#### Schema Design Principles

**Normalization Guidelines**
- **First Normal Form (1NF)**: Ensure atomic values in each column
- **Second Normal Form (2NF)**: Remove partial dependencies on composite keys
- **Third Normal Form (3NF)**: Eliminate transitive dependencies
- **Denormalization**: Only for performance reasons with clear justification

**Examples:**
```sql
-- ✅ Good: Proper normalization
CREATE TABLE vehicles (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    vendor_id BIGINT NOT NULL,
    category_id BIGINT NOT NULL,
    brand VARCHAR(255) NOT NULL,
    model VARCHAR(255) NOT NULL,
    price_per_day DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_vendor_status (vendor_id, status),
    INDEX idx_category_featured (category_id, featured),
    FOREIGN KEY (vendor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES vehicle_categories(id) ON DELETE CASCADE
);

CREATE TABLE vehicle_features (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    vehicle_id BIGINT NOT NULL,
    feature_name VARCHAR(255) NOT NULL,
    feature_value VARCHAR(255),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,

    INDEX idx_vehicle_feature (vehicle_id, feature_name)
);

-- ❌ Bad: Violating normalization
CREATE TABLE vehicles (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    vendor_id BIGINT NOT NULL,
    category_id BIGINT NOT NULL,
    brand VARCHAR(255) NOT NULL,
    model VARCHAR(255) NOT NULL,
    price_per_day DECIMAL(10,2) NOT NULL,
    -- Bad: Storing multiple values in one field
    features TEXT, -- "GPS,Air Conditioning,Bluetooth"
    vendor_name VARCHAR(255), -- Redundant - can be joined
    category_name VARCHAR(255), -- Redundant - can be joined
    -- ...
);
```

**Data Types and Constraints**
```sql
-- ✅ Good: Appropriate data types
CREATE TABLE bookings (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    vehicle_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CHECK (end_date > start_date),
    CHECK (total_price >= 0),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (user_id) REFERENCES users(id),

    INDEX idx_user_status (user_id, status),
    INDEX idx_vehicle_dates (vehicle_id, start_date, end_date),
    INDEX idx_dates_range (start_date, end_date)
);

-- ✅ Good: Using appropriate constraints
ALTER TABLE vehicles
ADD CONSTRAINT chk_price_positive CHECK (price_per_day > 0),
ADD CONSTRAINT chk_mileage_positive CHECK (mileage >= 0),
ADD CONSTRAINT chk_seating_capacity CHECK (seating_capacity BETWEEN 1 AND 10);
```

#### Query Optimization

**Indexing Strategy**
```sql
-- ✅ Good: Strategic indexing
-- Single column indexes for frequent WHERE clauses
CREATE INDEX idx_vehicles_status ON vehicles(status);
CREATE INDEX idx_vehicles_vendor ON vehicles(vendor_id);

-- Composite indexes for multiple column conditions
CREATE INDEX idx_vehicles_status_featured ON vehicles(status, featured);
CREATE INDEX idx_bookings_user_dates ON bookings(user_id, start_date, end_date);

-- Covering indexes for common queries
CREATE INDEX idx_vehicles_list ON vehicles(status, featured, price_per_day, brand, model);

-- Partial indexes for specific conditions
CREATE INDEX idx_active_vendors ON users(id, created_at) WHERE role = 'vendor' AND is_active = 1;

-- ❌ Bad: Over-indexing
CREATE INDEX idx_vehicles_brand ON vehicles(brand);
CREATE INDEX idx_vehicles_model ON vehicles(model);
CREATE INDEX idx_vehicles_price ON vehicles(price_per_day);
-- These should be combined into a composite index based on query patterns
```

**Query Performance Examples**
```php
// ✅ Good: Optimized queries with eager loading
$vehicles = Vehicle::with(['images', 'category', 'vendor.profile'])
    ->where('status', 'available')
    ->where('featured', true)
    ->orderBy('price_per_day', 'asc')
    ->paginate(15);

// ✅ Good: Using scopes for complex conditions
// In Vehicle model:
public function scopeAvailableInCategory(Builder $query, $categoryId): Builder
{
    return $query->where('status', 'available')
        ->where('category_id', $categoryId);
}

public function scopeInPriceRange(Builder $query, $min, $max): Builder
{
    return $query->whereBetween('price_per_day', [$min, $max]);
}

// Usage:
$vehicles = Vehicle::availableInCategory($categoryId)
    ->inPriceRange(50, 200)
    ->withCount(['bookings' => function ($query) {
        $query->where('status', 'completed');
    }])
    ->paginate(15);

// ❌ Bad: N+1 queries
$vehicles = Vehicle::all();
foreach ($vehicles as $vehicle) {
    echo $vehicle->category->name; // Separate query for each vehicle
    echo $vehicle->vendor->name;   // Another separate query
    foreach ($vehicle->images as $image) {
        // More queries
    }
}

// ❌ Bad: Inefficient queries
$vehicles = Vehicle::where('status', 'available')->get();
foreach ($vehicles as $vehicle) {
    if ($vehicle->price_per_day > 100) {
        // This should be filtered in the database query
    }
}
```

**Advanced Query Optimization**
```php
// ✅ Good: Database-level calculations
$bookings = Booking::selectRaw('
        *,
        DATEDIFF(end_date, start_date) as rental_days,
        total_price / DATEDIFF(end_date, start_date) as daily_average_price
    ')
    ->with(['vehicle' => function ($query) {
        $query->select('id', 'brand', 'model');
    }])
    ->whereRaw('DATEDIFF(end_date, start_date) > 7')
    ->orderBy('daily_average_price', 'desc')
    ->paginate(20);

// ✅ Good: Using database aggregation
$vendorStats = Vendor::withCount(['vehicles', 'bookings'])
    ->withSum('bookings', 'total_price')
    ->withAvg('vehicles.price_per_day', 'average_vehicle_price')
    ->having('vehicles_count', '>', 0)
    ->orderBy('bookings_sum_total_price', 'desc')
    ->get();

// ✅ Good: Efficient date range queries
$availableVehicles = Vehicle::whereDoesntHave('bookings', function ($query) use ($startDate, $endDate) {
    $query->where(function ($subQuery) use ($startDate, $endDate) {
        $subQuery->whereBetween('start_date', [$startDate, $endDate])
            ->orWhereBetween('end_date', [$startDate, $endDate])
            ->orWhere(function ($innerQuery) use ($startDate, $endDate) {
                $innerQuery->where('start_date', '<=', $startDate)
                    ->where('end_date', '>=', $endDate);
            });
    });
})->get();
```

#### Migration Standards

**Migration Best Practices**
```php
// ✅ Good: Descriptive migration naming
// 2025_01_01_060741_create_vehicles_table.php
// 2025_01_15_104835_create_vehicle_features_table.php
// 2025_02_01_120000_add_indexes_to_vehicles_table.php
// 2025_02_15_150000_modify_vehicle_price_precision.php

// ✅ Good: Safe migration with rollback
class AddFeaturedToVehiclesTable extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->boolean('featured')->default(false)->after('status');
            $table->index('featured');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex(['featured']);
            $table->dropColumn('featured');
        });
    }
}

// ✅ Good: Data migration with safety checks
class MigrateVehicleFeaturesToJson extends Migration
{
    public function up(): void
    {
        // First add the new column
        Schema::table('vehicles', function (Blueprint $table) {
            $table->json('features')->nullable()->after('status');
        });

        // Migrate data safely
        DB::transaction(function () {
            Vehicle::chunk(100, function ($vehicles) {
                foreach ($vehicles as $vehicle) {
                    $features = $this->extractFeaturesFromOldFormat($vehicle);
                    $vehicle->update(['features' => $features]);
                }
            });
        });

        // Add index after data migration
        Schema::table('vehicles', function (Blueprint $table) {
            $table->index('features');
        });
    }

    private function extractFeaturesFromOldFormat(Vehicle $vehicle): array
    {
        // Logic to convert old format to new JSON format
        return [];
    }
}
```

**Database Constraints and Relationships**
```php
// ✅ Good: Proper foreign key constraints
class CreateBookingsTable extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            // Foreign keys with proper constraints
            $table->foreignId('vehicle_id')
                ->constrained('vehicles')
                ->onDelete('restrict'); // Prevent deletion if bookings exist

            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade'); // Delete bookings if user is deleted

            // Additional constraints
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_price', 10, 2);

            // Business logic constraints
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])
                ->default('pending');

            // Check constraints
            $table->foreign('vehicle_id')
                ->references('id')
                ->on('vehicles')
                ->onDelete('restrict');

            $table->unique(['vehicle_id', 'start_date', 'end_date'],
                'unique_vehicle_booking_dates');

            $table->timestamps();
        });
    }
}
```

#### Relationship Patterns

**BelongsTo Relationships**
```php
// ✅ Good: Clear BelongsTo relationships
class Vehicle extends Model
{
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id')
            ->select(['id', 'name', 'email', 'is_active']);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(VehicleCategory::class, 'category_id');
    }

    // With default relationship
    public function primaryImage(): BelongsTo
    {
        return $this->belongsTo(VehicleImage::class, 'primary_image_id')
            ->withDefault(function () {
                return new VehicleImage(['image_path' => 'default-vehicle.jpg']);
            });
    }
}
```

**HasMany Relationships**
```php
// ✅ Good: Proper HasMany with constraints
class Vehicle extends Model
{
    public function images(): HasMany
    {
        return $this->hasMany(VehicleImage::class)
            ->orderBy('image_type') // Order primary images first
            ->orderBy('sort_order');
    }

    public function activeBookings(): HasMany
    {
        return $this->hasMany(Booking::class)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('end_date', '>=', now());
    }

    public function completedBookings(): HasMany
    {
        return $this->hasMany(Booking::class)
            ->where('status', 'completed');
    }
}
```

**BelongsToMany Relationships**
```php
// ✅ Good: Many-to-many with pivot data
class Vehicle extends Model
{
    public function features(): BelongsToMany
    {
        return $this->belongsToMany(VehicleFeature::class, 'vehicle_feature_mappings')
            ->withPivot('is_included', 'additional_price')
            ->withTimestamps();
    }

    public function availableFeatures(): BelongsToMany
    {
        return $this->belongsToMany(VehicleFeature::class, 'vehicle_feature_mappings')
            ->wherePivot('is_available', true)
            ->orderBy('name');
    }
}

// ✅ Good: Polymorphic relationships
class Document extends Model
{
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }
}

class Vehicle extends Model
{
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}

class User extends Model
{
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
```

#### Performance Monitoring

**Query Debugging**
```php
// ✅ Good: Enable query logging for development
if (app()->environment('local')) {
    DB::listen(function ($query) {
        Log::info('Query executed', [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time . 'ms'
        ]);

        // Alert on slow queries
        if ($query->time > 100) {
            Log::warning('Slow query detected', [
                'sql' => $query->sql,
                'time' => $query->time
            ]);
        }
    });
}

// ✅ Good: Using query explain for optimization
$vehicles = Vehicle::where('status', 'available')
    ->where('price_per_day', '<', 100)
    ->get();

$explanation = DB::table('vehicles')
    ->where('status', 'available')
    ->where('price_per_day', '<', 100)
    ->explain();

Log::info('Query explanation', $explanation);
```

### Quick Reference Patterns

#### Common Code Patterns

**Controller Patterns**
```php
// ✅ Standard index method with filtering and pagination
public function index(Request $request)
{
    $query = Vehicle::with(['images', 'category', 'vendor.profile'])
        ->where('status', 'available');

    // Apply filters
    if ($request->filled('category_id')) {
        $query->where('category_id', $request->category_id);
    }

    if ($request->filled('min_price')) {
        $query->where('price_per_day', '>=', $request->min_price);
    }

    if ($request->filled('max_price')) {
        $query->where('price_per_day', '<=', $request->max_price);
    }

    $vehicles = $query->orderBy('featured', 'desc')
        ->orderBy('price_per_day', 'asc')
        ->paginate(15)
        ->withQueryString();

    return Inertia::render('Vehicles/Index', [
        'vehicles' => $vehicles,
        'filters' => $request->only(['category_id', 'min_price', 'max_price']),
        'categories' => VehicleCategory::orderBy('name')->get()
    ]);
}

// ✅ Standard store method with validation and error handling
public function store(VehicleRequest $request)
{
    try {
        DB::beginTransaction();

        $vehicle = Vehicle::create($request->validated());

        // Handle image uploads
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('vehicles', 'public');

                VehicleImage::create([
                    'vehicle_id' => $vehicle->id,
                    'image_path' => $path,
                    'image_type' => $index === 0 ? 'primary' : 'gallery',
                    'sort_order' => $index
                ]);
            }
        }

        // Log activity
        ActivityLogHelper::logActivity(
            'vehicle_created',
            "Vehicle {$vehicle->brand} {$vehicle->model} created",
            $vehicle
        );

        DB::commit();

        return redirect()
            ->route('vehicles.show', $vehicle)
            ->with('success', 'Vehicle created successfully!');

    } catch (\Exception $e) {
        DB::rollBack();

        Log::error('Vehicle creation failed', [
            'error' => $e->getMessage(),
            'request_data' => $request->all()
        ]);

        return back()
            ->withInput()
            ->withErrors(['error' => 'Failed to create vehicle. Please try again.']);
    }
}
```

**Vue Component Templates**
```vue
<!-- ✅ Standard listing component template -->
<template>
  <div class="container mx-auto px-4 py-8">
    <!-- Header with title and actions -->
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold">Vehicles</h1>
      <Link
        :href="route('vehicles.create')"
        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
      >
        Add Vehicle
      </Link>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-lg shadow mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">Category</label>
          <select
            v-model="filters.category_id"
            @change="applyFilters"
            class="w-full border rounded px-3 py-2"
          >
            <option value="">All Categories</option>
            <option
              v-for="category in categories"
              :key="category.id"
              :value="category.id"
            >
              {{ category.name }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Min Price</label>
          <input
            v-model.number="filters.min_price"
            @input="debounceFilters"
            type="number"
            placeholder="0"
            class="w-full border rounded px-3 py-2"
          />
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Max Price</label>
          <input
            v-model.number="filters.max_price"
            @input="debounceFilters"
            type="number"
            placeholder="1000"
            class="w-full border rounded px-3 py-2"
          />
        </div>
      </div>
    </div>

    <!-- Results -->
    <div v-if="vehicles.data.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <VehicleCard
        v-for="vehicle in vehicles.data"
        :key="vehicle.id"
        :vehicle="vehicle"
        @favorite="toggleFavorite"
        @book="bookVehicle"
      />
    </div>

    <!-- Empty state -->
    <div v-else class="text-center py-12">
      <p class="text-gray-500">No vehicles found matching your criteria.</p>
    </div>

    <!-- Pagination -->
    <Pagination :links="vehicles.links" class="mt-8" />
  </div>
</template>
```

**API Response Patterns**
```php
// ✅ Consistent API response structure
class ApiResponse
{
    public static function success($data = null, $message = 'Success')
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toISOString()
        ]);
    }

    public static function error($message = 'Error', $errors = null, $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => now()->toISOString()
        ], $code);
    }
}

// Usage in controllers
public function index(Request $request)
{
    $vehicles = Vehicle::with(['images', 'category'])
        ->paginate(15);

    return ApiResponse::success($vehicles, 'Vehicles retrieved successfully');
}

public function store(VehicleRequest $request)
{
    try {
        $vehicle = Vehicle::create($request->validated());
        return ApiResponse::success($vehicle, 'Vehicle created successfully');
    } catch (\Exception $e) {
        return ApiResponse::error('Failed to create vehicle', [
            'details' => $e->getMessage()
        ], 500);
    }
}
```

#### Anti-Patterns to Avoid

**Database Anti-Patterns**
```php
// ❌ Bad: N+1 queries
$vehicles = Vehicle::all();
foreach ($vehicles as $vehicle) {
    echo $vehicle->category->name; // Separate query for each
}

// ✅ Good: Eager loading
$vehicles = Vehicle::with('category')->get();

// ❌ Bad: Query in loops
foreach ($vehicleIds as $id) {
    $vehicle = Vehicle::find($id);
    // Process vehicle
}

// ✅ Good: Single query with whereIn
$vehicles = Vehicle::whereIn('id', $vehicleIds)->get();

// ❌ Bad: Using raw SQL without parameter binding
$vehicles = DB::select("SELECT * FROM vehicles WHERE brand = '$brand'");

// ✅ Good: Parameter binding
$vehicles = DB::select("SELECT * FROM vehicles WHERE brand = ?", [$brand]);
```

**Vue.js Anti-Patterns**
```vue
<script setup>
// ❌ Bad: Direct DOM manipulation
const updateElement = () => {
  document.getElementById('my-element').innerHTML = 'new content'
}

// ✅ Good: Reactive data binding
const content = ref('new content')

// ❌ Bad: Mutating props directly
const props = defineProps(['count'])
const increment = () => {
  props.count++ // This will cause warnings and doesn't work
}

// ✅ Good: Emit events to parent
const emit = defineEmits(['update:count'])
const increment = () => {
  emit('update:count', props.count + 1)
}

// ❌ Bad: Complex logic in template
<template>
  <div>{{ items.filter(item => item.active && item.price > 100 && item.category === selectedCategory).length }}</div>
</template>

// ✅ Good: Use computed properties
const filteredItemsCount = computed(() => {
  return items.value.filter(item =>
    item.active &&
    item.price > 100 &&
    item.category === selectedCategory.value
  ).length
})
</script>
```

**PHP Anti-Patterns**
```php
// ❌ Bad: Hardcoded values
class BookingService
{
    public function calculatePrice($days)
    {
        return $days * 50; // Hardcoded daily rate
    }
}

// ✅ Good: Configurable values
class BookingService
{
    public function __construct(
        private VehicleRepository $vehicleRepository
    ) {}

    public function calculatePrice(Vehicle $vehicle, int $days): float
    {
        return $vehicle->price_per_day * $days;
    }
}

// ❌ Bad: God class with too many responsibilities
class VehicleManager extends Controller
{
    public function createVehicle() { /* ... */ }
    public function updateVehicle() { /* ... */ }
    public function deleteVehicle() { /* ... */ }
    public function calculatePrice() { /* ... */ }
    public function sendNotifications() { /* ... */ }
    public function generateReports() { /* ... */ }
}

// ✅ Good: Single responsibility
class VehicleController extends Controller
{
    public function __construct(
        private VehicleService $vehicleService,
        private NotificationService $notificationService
    ) {}

    public function store(VehicleRequest $request)
    {
        $vehicle = $this->vehicleService->create($request->validated());
        $this->notificationService->notifyVehicleCreated($vehicle);
        return redirect()->route('vehicles.show', $vehicle);
    }
}
```

## Common Development Tasks

### Adding New Admin Features
1. Create controller in `app/Http/Controllers/Admin/`
2. Add routes in admin section of `routes/web.php`
3. Create Vue components in `resources/js/Pages/AdminDashboardPages/`
4. Add navigation items to admin dashboard

### Adding New Vehicle Features
1. Update `VehicleController` or create vendor-specific controller
2. Modify vehicle-related models and migrations
3. Update frontend components in appropriate directories
4. Consider bulk operation implications

### Adding New Languages
1. Update locale route pattern in `routes/web.php`
2. Add translation files
3. Update middleware for locale detection
4. Test all routes with new locale prefix

## MCP Tools and Integration

This project integrates several MCP (Model Context Protocol) servers to enhance development capabilities. Below are the available MCP servers and their use cases:

### Available MCP Servers

#### 1. Sequential Thinking (`sequential-thinking`)
**Purpose**: Dynamic and reflective problem-solving through structured thinking processes
**When to Use**:
- Breaking down complex problems into steps
- Planning and design with room for revision
- Analysis that might need course correction
- Multi-step solutions requiring context maintenance

**Key Tool**: `mcp__sequential-thinking__sequentialthinking`
- Use for complex architectural decisions
- Problem analysis that may evolve
- Planning tasks with uncertain scope
- Debugging complex issues

#### 2. Chrome DevTools (`chrome-devtools`)
**Purpose**: Browser automation, testing, and performance analysis
**When to Use**:
- Frontend testing and debugging
- Performance analysis and optimization
- Browser automation for testing
- Screenshot capture and visual testing

**Key Tools**:
- `mcp__chrome-devtools__take_screenshot` - Capture screenshots
- `mcp__chrome-devtools__take_snapshot` - Get page element structure
- `mcp__chrome-devtools__evaluate_script` - Execute JavaScript in browser
- `mcp__chrome-devtools__performance_start_trace` - Performance analysis
- `mcp__chrome-devtools__click`, `mcp__chrome-devtools__fill` - Browser automation

#### 3. Context7 (`context7`)
**Purpose**: Up-to-date documentation and code examples for libraries
**When to Use**:
- Needing current documentation for any library/package
- Looking for code examples and best practices
- Researching new dependencies or technologies
- Getting implementation guidance

**Key Tools**:
- `mcp__context7__resolve-library-id` - Find library documentation
- `mcp__context7__get-library-docs` - Get current docs and examples

#### 4. Serena (`serena`)
**Purpose**: AI assistant and code generation capabilities
**When to Use**:
- Code generation and refactoring tasks
- Automated documentation creation
- Complex code analysis and suggestions
- Advanced AI-powered development assistance

**Note**: Currently experiencing connection issues. Use as fallback when other tools don't suffice.

#### 5. Linear (`linear`)
**Purpose**: Project management and issue tracking integration
**When to Use**:
- Creating and managing development tasks
- Tracking project progress
- Managing team workflows
- Documentation and project planning

**Key Tools**:
- `mcp__linear__create_issue` - Create new issues/tasks
- `mcp__linear__update_issue` - Update existing issues
- `mcp__linear__list_issues` - View project issues
- `mcp__linear__create_comment` - Add comments to issues
- `mcp__linear__list_projects` - Manage projects
- `mcp__linear__list_teams` - Team management

### MCP Usage Guidelines

1. **Always identify the appropriate MCP server for your task**
2. **Use `sequential-thinking` for complex problem analysis**
3. **Use `chrome-devtools` for frontend testing and performance**
4. **Use `context7` when needing documentation or examples**
5. **Use `serena` for advanced AI assistance and code generation**
6. **Use `linear` for all project management tasks**

### Detailed MCP Tool Usage

#### Sequential Thinking Workflow
```bash
# When to use: Complex architectural decisions or debugging
# Example usage:
mcp__sequential-thinking__sequentialthinking \
  thought="I need to analyze the booking system architecture" \
  nextThoughtNeeded=true \
  thoughtNumber=1 \
  totalThoughts=5
```

#### Chrome DevTools Workflow
```bash
# When to use: Frontend testing, screenshots, performance analysis
# Example usage:
mcp__chrome-devtools__take_snapshot                    # Get page structure
mcp__chrome-devtools__take_screenshot format="png"     # Capture screenshot
mcp__chrome-devtools__evaluate_script function="() => document.title"  # Execute JS
mcp__chrome-devtools__performance_start_trace reload=true  # Start performance analysis
```

#### Context7 Workflow
```bash
# When to use: Needing documentation for libraries
# Example usage:
mcp__context7__resolve-library-id query="Laravel 10"    # Find library docs
mcp__context7__get-library-docs libraryId="laravel"     # Get specific docs
```

#### Serena Workflow
```bash
# When to use: Advanced code generation and AI assistance
# Note: Currently experiencing connection issues
# Use as fallback when other tools don't provide sufficient capability
```

#### Linear Workflow
```bash
# When to use: All project management tasks
# Example usage:
mcp__linear__create_issue title="New Feature" description="Implementation details"
mcp__linear__update_issue issueId="LINEAR-123" status="In Progress"
mcp__linear__create_comment issueId="LINEAR-123" body="Progress update"
```

## Development Workflow

### ⚡️ CRITICAL WORKFLOW REQUIREMENTS

**MANDATORY**: Before working on ANY task, you MUST follow this exact sequence:

1. **IMMEDIATELY create a local git commit** for current state:
   ```bash
   git add .
   git commit -m "Work in progress: [brief task description]"
   ```

2. **Create Linear issue** using `mcp__linear__create_issue` with detailed plan
3. **Reference Linear issue ID** in all subsequent commit messages
4. **Update Linear issue** throughout development process
5. **Mark Linear issue completed** when task is finished

**No exceptions to this workflow** - Linear and GitHub are essential components of our development process.

### Git Workflow Requirements

```bash
git add .
git commit -m "Work in progress: [task description]"
```

This ensures:
- Work is never lost
- Clear separation between different tasks
- Easy rollback if needed
- Proper change tracking
- Linear issue tracking integration

### Linear Integration Workflow

#### Before Starting Work:
1. **Create Linear Issue**: Use `mcp__linear__create_issue` to document the task
2. **Plan the Work**: Add detailed plan and steps to the issue description
3. **Reference the Issue**: Note the Linear issue ID in your commit messages

#### During Work:
1. **Update Progress**: Use `mcp__linear__update_issue` to track progress
2. **Add Comments**: Use `mcp__linear__create_comment` for important updates
3. **Track blockers**: Update issue status if blocked

#### After Completion:
1. **Mark as Completed**: Update issue status to completed
2. **Add Summary**: Comment with final results and learnings
3. **Link to PR**: Reference Linear issue in pull request

#### Linear Issue Template:
```markdown
## Task Description
[Clear description of what needs to be done]

## Implementation Plan
- [ ] Step 1
- [ ] Step 2
- [ ] Step 3

## Acceptance Criteria
- [ ] Criteria 1
- [ ] Criteria 2

## Notes/Blockers
[Any additional information]
```

### Combined Workflow Example:
```bash
# Step 0: IMMEDIATELY commit current state
git add .
git commit -m "Work in progress: About to start new task"

# Step 1: Create Linear issue with detailed plan
mcp__linear__create_issue title="Implement user authentication" description="## Implementation Plan\n- [ ] Create login form component\n- [ ] Implement authentication middleware\n- [ ] Add password reset functionality\n\n## Acceptance Criteria\n- [ ] Users can login with email/password\n- [ ] Password reset emails are sent\n- [ ] Session management works properly"

# Step 2: Start work with Linear-referenced commit
git add .
git commit -m "Starting LINEAR-456: Implement user authentication"

# Step 3: Work with progress updates
git commit -m "LINEAR-456: Created login form component"
mcp__linear__create_comment issueId="LINEAR-456" body="Login form component completed, moving to middleware"

# Step 4: Complete and update Linear
git commit -m "Completed LINEAR-456: User authentication implementation"
mcp__linear__update_issue issueId="LINEAR-456" status="Done"
mcp__linear__create_comment issueId="LINEAR-456" body="All authentication features implemented and tested. Ready for review."
```

### 🚨 WORKFLOW ENFORCEMENT

**This workflow is MANDATORY and NON-NEGOTIABLE**:

- **ALWAYS** create a git commit before starting any work
- **ALWAYS** create a Linear issue before writing code
- **ALWAYS** reference Linear issue IDs in commits
- **ALWAYS** update Linear issues with progress
- **NEVER** skip any of these steps

**Why this matters**:
- Prevents work loss during development
- Provides clear audit trail of changes
- Enables proper project tracking and management
- Ensures team visibility into progress
- Facilitates code reviews and collaboration
- Maintains project history and accountability

**Consequences of not following workflow**:
- Work may be lost without recovery options
- Lack of proper change tracking
- No visibility into project progress
- Difficulty in code review and collaboration
- Potential for duplicated or conflicting work