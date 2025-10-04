# Laravel Car Rental Platform - AI Development Guide

## Project Overview

Laravel-based car rental platform with Vue.js frontend, supporting multi-language support (en, fr, nl, es, ar), multiple user roles (Customer, Vendor, Admin), and third-party API integrations (GreenMotion, U-Save).

### Core Technology Stack
- **Backend**: Laravel 10.x, PHP 8.1+, MySQL
- **Frontend**: Vue 3, Inertia.js, TypeScript, Tailwind CSS, Vite
- **Payment**: Stripe, Real-time: Laravel Echo with Pusher
- **Storage**: AWS S3, Authentication: Laravel Sanctum

## Development Commands

### Essential Commands
```bash
# Frontend
npm run dev              # Development server
npm run build           # Production build

# Backend
php artisan serve       # Laravel server
php artisan migrate     # Database migrations
php artisan tinker      # Laravel REPL
php artisan queue:work  # Queue processing
php artisan storage:link  # Storage links

# Testing & Quality
php artisan test        # Run tests
vendor/bin/pint         # Code style

# Cache Management
php artisan optimize:clear  # Clear all caches
php artisan config:cache    # Cache config
```

## Architecture

### Role-Based Access Control
- **Admin**: Full platform management (`middleware(['auth', 'role:admin'])`)
- **Vendor**: Vehicle/booking management (`middleware(['auth', 'role:vendor'])`)
- **Customer**: Booking/profile management (`middleware(['auth', 'role:customer'])`)

### Route Structure
- **Admin**: `/admin/*` (outside locale prefix)
- **Customer/Vendor**: `/{locale}/*` (with locale prefix)
- **API**: Mix of authenticated and open endpoints

### Directory Organization
- `app/Http/Controllers/Admin/` - Admin controllers
- `app/Http/Controllers/Vendor/` - Vendor controllers
- `app/Http/Controllers/Auth/` - Authentication controllers
- `resources/js/Pages/AdminDashboardPages/` - Admin components
- `resources/js/Pages/Vendor/` - Vendor components
- `resources/js/Pages/Profile/` - Customer components

## Key Features

### Booking System
- Multi-step flow with Stripe integration
- Vehicle availability checking
- Damage protection with photos
- Status management (pending, confirmed, completed, cancelled)

### Vehicle Management
- Bulk upload via CSV (`BulkVehicleUploadController`)
- Image management with bulk operations
- Categories, features, and addons
- External API integration (GreenMotion, U-Save)

### Document Management
- User verification system
- Vendor document upload/approval
- Bulk document processing

## MCP Tools Integration

### Available MCP Servers
1. **Sequential Thinking**: Complex problem analysis
2. **Chrome DevTools**: Frontend testing, screenshots, performance
3. **Context7**: Current library documentation
4. **Linear**: Project management and issue tracking
5. **Serena**: Advanced code generation (when available)

### MCP Usage Guidelines
- Use `sequential-thinking` for complex architectural decisions
- Use `chrome-devtools` for frontend testing and performance analysis
- Use `context7` for current library documentation and examples
- Use `linear` for ALL project management tasks
- Use `serena` for advanced AI assistance when other tools insufficient

## Development Workflow (CRITICAL)

### MANDATORY Workflow Steps
1. **IMMEDIATELY commit current state** before starting any work:
   ```bash
   git add .
   git commit -m "Work in progress: [task description]"
   ```

2. **Create Linear issue** using `mcp__linear__create_issue` with detailed plan

3. **Reference Linear issue ID** in all commit messages

4. **Update Linear issue** throughout development process

5. **Mark Linear issue completed** when task is finished

### Linear Integration Commands
```bash
# Create issue
mcp__linear__create_issue title="Task name" description="Implementation plan"

# Update progress
mcp__linear__update_issue issueId="VRO-123" status="In Progress"

# Add comments
mcp__linear__create_comment issueId="VRO-123" body="Progress update"
```

## Code Standards (Essential)

### PHP/Laravel Standards
- **PSR-12 compliance** for all PHP code
- **Class naming**: PascalCase (`VehicleController`, `LocationSearchService`)
- **Method naming**: camelCase (`getVehiclesByCategory`, `calculateTotalPrice`)
- **Single responsibility**: Each controller/class handles one domain
- **Dependency injection**: Use constructor injection, avoid manual instantiation
- **Form requests**: Always validate input using Form Request classes
- **Eloquent optimization**: Use eager loading to prevent N+1 queries

### Vue.js Standards
- **Composition API**: Use `<script setup>` syntax
- **Component naming**: PascalCase files, kebab-case in templates
- **Props validation**: Always define prop types and validation
- **Event naming**: kebab-case for custom events
- **Reactive data**: `ref()` for primitives, `reactive()` for objects
- **TypeScript**: Define types for props, emits, and reactive data

### Database Standards
- **Migration naming**: Descriptive names (`2025_01_01_create_vehicles_table.php`)
- **Foreign keys**: Use proper constraints with cascading rules
- **Indexing**: Strategic indexing for query performance
- **Normalization**: Follow 1NF, 2NF, 3NF unless denormalization is justified

## Common Development Patterns

### Controller Pattern
```php
class VehicleController extends Controller
{
    public function __construct(
        private VehicleService $vehicleService,
        private LocationSearchService $locationSearchService
    ) {}

    public function index(Request $request)
    {
        $vehicles = $this->vehicleService->getVehiclesWithFilters($request->all());
        return Inertia::render('Vehicles/Index', compact('vehicles'));
    }
}
```

### Vue Component Pattern
```vue
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'

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
const isExpanded = ref(false)

const toggleFavorite = () => emit('favorite', props.vehicle.id)
</script>
```

### Model Pattern
```php
class Vehicle extends Model
{
    protected $fillable = ['vendor_id', 'category_id', 'brand', 'model', 'price_per_day'];

    protected $casts = [
        'price_per_day' => 'decimal:2',
        'features' => 'array',
        'featured' => 'boolean'
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'available');
    }
}
```

## Anti-Patterns to Avoid

### Database Anti-Patterns
- **N+1 queries**: Use eager loading (`Vehicle::with('category')->get()`)
- **Queries in loops**: Use `whereIn()` instead of querying in loops
- **Raw SQL without binding**: Always use parameterized queries

### Vue.js Anti-Patterns
- **Direct DOM manipulation**: Use reactive data binding
- **Mutating props directly**: Emit events to parent instead
- **Complex logic in templates**: Use computed properties

### PHP Anti-Patterns
- **Hardcoded values**: Use configuration and dependency injection
- **God classes**: Follow single responsibility principle
- **Manual instantiation**: Use dependency injection

## Environment Configuration

### Required .env Variables
```
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=car_rental
DB_USERNAME=root
DB_PASSWORD=

# Stripe
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...

# Pusher (Real-time)
PUSHER_APP_ID=...
PUSHER_APP_KEY=...
PUSHER_APP_SECRET=...
PUSHER_APP_CLUSTER=mt1

# AWS S3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=car-rental-storage

# Third-party APIs
GREENMOTION_API_KEY=...
USAVE_API_KEY=...
TAPFILIATE_API_KEY=...
```

## Quick Reference

### Adding New Features
1. **Admin features**: Create controller in `app/Http/Controllers/Admin/`, add routes in admin section, create Vue components in `AdminDashboardPages/`
2. **Vehicle features**: Update `VehicleController` or vendor controllers, modify models and frontend components
3. **Languages**: Update locale routes, add translation files, update middleware

### Common Issues
- **N+1 queries**: Use eager loading with `with()`
- **Slow queries**: Add appropriate database indexes
- **Frontend performance**: Implement lazy loading and code splitting
- **Memory issues**: Use pagination and chunking for large datasets

## Reference Documentation

For detailed documentation, examples, and advanced patterns:
- **Detailed code examples**: See `CODE-PATTERNS.md`
- **Database schemas**: See `DATABASE-SCHEMAS.md`
- **API documentation**: See `API-REFERENCE.md`
- **Troubleshooting**: See `TROUBLESHOOTING.md`

## Security Considerations

- **Input validation**: Always validate on both client and server
- **XSS prevention**: Use proper escaping in templates
- **Authentication**: Implement proper auth and authorization checks
- **CSRF protection**: Enabled on all forms by default
- **SQL injection**: Use Eloquent/parameterized queries (automatic)

---

*This guide is optimized for AI consumption. For detailed explanations and examples, refer to the reference documentation files.*