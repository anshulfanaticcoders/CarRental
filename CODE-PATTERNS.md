# Code Patterns and Examples Reference

## Laravel Controller Patterns

### Standard Index with Filtering and Pagination
```php
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
```

### Store Method with Validation and Error Handling
```php
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

## Vue Component Patterns

### Standard Listing Component
```vue
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

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import VehicleCard from '@/Components/VehicleCard.vue'
import Pagination from '@/Components/Pagination.vue'
import type { Vehicle, VehicleCategory, PaginatedData } from '@/types'

interface Props {
  vehicles: PaginatedData<Vehicle>
  categories: VehicleCategory[]
  filters: Record<string, any>
}

const props = defineProps<Props>()

const filters = ref({ ...props.filters })
const isSubmitting = ref(false)

// Debounced filter application
const applyFilters = debounce(() => {
  router.get(route('vehicles.index'), filters.value, {
    preserveState: true,
    preserveScroll: true
  })
}, 300)

const toggleFavorite = (vehicleId: number) => {
  // Toggle favorite logic
}

const bookVehicle = (vehicleId: number) => {
  router.get(route('bookings.create', { vehicle_id: vehicleId }))
}

// Watch for filter changes
watch(filters.value, applyFilters, { deep: true })
</script>
```

## Database Query Patterns

### Optimized Queries with Eager Loading
```php
// Good: Prevents N+1 queries
$vehicles = Vehicle::with(['images', 'category', 'vendor'])
    ->where('status', 'available')
    ->orderBy('price_per_day', 'asc')
    ->paginate(15);

// Good: Using scopes for complex conditions
$vehicles = Vehicle::available()
    ->featured()
    ->inCategory($categoryId)
    ->withCount(['bookings' => function ($query) {
        $query->where('status', 'completed');
    }])
    ->paginate(15);

// Good: Database-level calculations
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
```

### Efficient Date Range Queries
```php
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

## Service Layer Patterns

### External API Integration Service
```php
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

    public function createBooking(array $bookingData): array
    {
        try {
            $response = $this->httpClient->post('https://api.greenmotion.com/bookings', [
                'json' => $bookingData
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('GreenMotion booking failed', [
                'error' => $e->getMessage(),
                'booking_data' => $bookingData
            ]);
            throw new \Exception('Failed to create booking with GreenMotion');
        }
    }
}
```

### Pricing Calculation Service
```php
class PricingService
{
    public function calculateVehiclePrice(
        Vehicle $vehicle,
        int $days,
        array $addons = []
    ): array {
        $basePrice = $vehicle->price_per_day * $days;

        // Apply discounts
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
            'total' => $basePrice + $addonsTotal,
            'daily_rate' => ($basePrice + $addonsTotal) / $days
        ];
    }

    private function calculateAddonsPrice(array $addons): float
    {
        return collect($addons)->sum(function ($addon) {
            return $addon['price'] * $addon['quantity'];
        });
    }
}
```

## Migration Patterns

### Standard Table Creation with Relationships
```php
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

            // Booking details
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_price', 10, 2);

            // Business logic constraints
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])
                ->default('pending');

            // Additional fields
            $table->text('notes')->nullable();
            $table->json('addons')->nullable();

            // Unique constraint for date ranges
            $table->unique(['vehicle_id', 'start_date', 'end_date'],
                'unique_vehicle_booking_dates');

            $table->timestamps();

            // Performance indexes
            $table->index(['user_id', 'status']);
            $table->index(['vehicle_id', 'start_date', 'end_date']);
            $table->index(['status', 'created_at']);
        });
    }
}
```

### Safe Data Migration
```php
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

## API Response Patterns

### Consistent API Response Structure
```php
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

    public static function paginated($data, $message = 'Data retrieved successfully')
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data->items(),
            'pagination' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem()
            ],
            'timestamp' => now()->toISOString()
        ]);
    }
}
```

---

*This file contains detailed code examples and patterns. For essential guidelines and quick reference, see the main CLAUDE.md file.*