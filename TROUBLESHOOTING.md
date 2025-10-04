# Troubleshooting Guide

## Common Development Issues

### Database Issues

#### Migration Problems
**Issue**: Migration fails with foreign key constraint error
```bash
# Solution: Check if related tables exist and data is consistent
php artisan migrate:status
php artisan migrate:rollback --step=1
php artisan migrate
```

**Issue**: Table already exists error
```bash
# Solution: Check migration status and manually handle
php artisan migrate:status
# If migration is marked as run but table doesn't exist:
php artisan migrate:rollback --step=1
# Then run migrations again
php artisan migrate
```

#### N+1 Query Problems
**Issue**: Slow page loads due to multiple database queries
```php
// Problematic code:
$vehicles = Vehicle::all(); // Causes N+1 when accessing relationships

// Solution: Use eager loading
$vehicles = Vehicle::with(['category', 'vendor', 'images'])->get();

// For pagination:
$vehicles = Vehicle::with(['category', 'vendor', 'images'])->paginate(15);
```

#### Connection Issues
**Issue**: Database connection refused
```bash
# Check .env configuration
php artisan config:cache
php artisan tinker
>>> DB::connection()->getPdo();
```

### Frontend Issues

#### Vue Component Not Rendering
**Issue**: Component shows blank or errors in console
```javascript
// Check for:
1. Missing props validation
2. Undefined reactive data
3. Incorrect import paths
4. Syntax errors in template

// Debug steps:
// 1. Check browser console for errors
// 2. Add console.log statements
// 3. Use Vue DevTools
// 4. Verify data structure with console.log(props)
```

#### Inertia.js Route Issues
**Issue**: Page not found or routing errors
```php
// Check route definition:
Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');

// Check controller returns Inertia response:
return Inertia::render('Vehicles/Index', $data);

// Check frontend component exists:
// resources/js/Pages/Vehicles/Index.vue
```

#### Asset Loading Issues
**Issue**: CSS/JS assets not loading
```bash
# Clear and rebuild assets
npm run build
php artisan view:clear
php artisan config:clear

# Check vite.config.js for correct paths
# Verify public/storage link exists
php artisan storage:link
```

### Performance Issues

#### Slow Page Loads
**Issue**: Page loading takes too long
```php
// Solutions:
1. Implement eager loading for relationships
2. Add database indexes for frequent queries
3. Use pagination for large datasets
4. Implement caching for expensive operations
5. Optimize images and assets

// Example caching:
$categories = Cache::remember('vehicle_categories', 3600, function () {
    return VehicleCategory::orderBy('name')->get();
});
```

#### Memory Leaks
**Issue**: Memory usage increases over time
```php
// Solutions:
1. Use chunk() for large data processing
Vehicle::chunk(100, function ($vehicles) {
    // Process vehicles in chunks
});

2. Clear large objects when done
unset($largeObject);

3. Use queues for heavy operations
php artisan queue:work
```

### Authentication Issues

#### Login Problems
**Issue**: Users can't login or session issues
```bash
# Check configuration:
php artisan config:cache
php artisan route:clear

# Clear sessions:
php artisan session:table
php artisan migrate
php artisan session:flush
```

#### Authorization Problems
**Issue**: Permission denied errors
```php
// Check middleware definition:
Route::middleware(['auth', 'role:admin'])->group(function () {
    // Admin routes
});

// Check user has required role:
$user = User::find($userId);
dd($user->role); // Should show 'admin', 'vendor', or 'customer'
```

### File Upload Issues

#### Image Upload Problems
**Issue**: Files not uploading or storing correctly
```php
// Check filesystem configuration:
# config/filesystems.php
'default' => env('FILESYSTEM_DRIVER', 'public'),

# .env file
FILESYSTEM_DRIVER=public

# Check permissions:
chmod -R 775 storage/app/public
php artisan storage:link
```

#### S3 Upload Issues
**Issue**: AWS S3 uploads failing
```bash
# Check .env credentials:
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket

# Test connection:
php artisan tinker
>>> Storage::disk('s3')->put('test.txt', 'Hello World');
```

### Queue Issues

#### Jobs Not Processing
**Issue**: Queued jobs not executing
```bash
# Check queue configuration:
php artisan config:cache

# Start queue worker:
php artisan queue:work

# Check failed jobs:
php artisan queue:failed

# Retry failed jobs:
php artisan queue:retry all
```

#### Memory Issues with Queues
**Issue**: Queue worker runs out of memory
```bash
# Run with memory limit:
php artisan queue:work --memory=512

# Use supervisord for production:
# /etc/supervisor/conf.d/laravel-worker.conf
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path-to-your-project/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=/path-to-your-project/storage/logs/worker.log
```

### Payment Issues

#### Stripe Integration Problems
**Issue**: Payment processing fails
```php
// Check API keys:
# .env file
STRIPE_KEY=pk_test_xxx
STRIPE_SECRET=sk_test_xxx

# Test connection:
php artisan tinker
>>> \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
>>> \Stripe\Account::retrieve();

// Check webhook configuration:
php artisan route:list | grep webhook
```

#### Webhook Issues
**Issue**: Stripe webhooks not received
```bash
# Test webhook endpoint:
ngrok http 8000

# Update webhook URL in Stripe dashboard
# Test with Stripe CLI:
stripe listen --forward-to localhost:8000/webhooks/stripe
```

### Third-Party API Issues

#### GreenMotion API Problems
**Issue**: External API calls failing
```php
// Check API credentials:
# .env file
GREENMOTION_API_KEY=your_key
GREENMOTION_BASE_URL=https://api.greenmotion.com

// Test connection:
php artisan tinker
>>> $response = Http::get('https://api.greenmotion.com/test-endpoint');
>>> dd($response->status(), $response->json());

// Check rate limits:
// Implement exponential backoff for failed requests
```

### Email Issues

#### Emails Not Sending
**Issue**: Mail not being delivered
```bash
# Check mail configuration:
# .env file
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls

# Test mail configuration:
php artisan config:cache
php artisan tinker
>>> Mail::raw('Test email', function($message) {
>>>     $message->to('test@example.com')->subject('Test');
>>> });
```

### Debugging Techniques

#### General Debugging
```php
// Use Laravel's built-in debugging:
1. Enable debug mode in .env: APP_DEBUG=true
2. Check storage/logs/laravel.log
3. Use dd() for quick debugging
4. Use Laravel Telescope for detailed insights

// Log custom messages:
Log::info('User action', ['user_id' => $user->id, 'action' => 'login']);
Log::error('Payment failed', ['error' => $e->getMessage()]);
```

#### Frontend Debugging
```javascript
// Use browser developer tools:
1. Network tab for API requests
2. Console for JavaScript errors
3. Elements for DOM inspection
4. Vue DevTools for component debugging

// Add debug statements:
console.log('Component data:', this.$data);
console.log('API response:', response.data);
```

### Performance Optimization

#### Database Optimization
```sql
-- Add indexes for slow queries:
EXPLAIN SELECT * FROM vehicles WHERE category_id = 1 AND status = 'available';

-- Create composite indexes:
CREATE INDEX idx_vehicles_search ON vehicles(category_id, status, price_per_day);

-- Analyze slow query log:
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;
```

#### Frontend Optimization
```bash
# Optimize assets:
npm run build
npm run prod

# Enable gzip compression:
# nginx config
gzip on;
gzip_types text/plain text/css application/json application/javascript;

# Implement caching:
# Add Cache-Control headers
```

### Environment Issues

#### Local Development Setup
```bash
# Fresh installation steps:
1. git clone repository
2. composer install
3. npm install
4. cp .env.example .env
5. php artisan key:generate
6. php artisan migrate
7. php artisan storage:link
8. npm run dev
```

#### Production Deployment
```bash
# Production optimization:
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize --no-restart

# Check permissions:
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### Common Error Messages

#### "Call to a member function on null"
**Cause**: Trying to call method on null object
**Solution**: Check if object exists before calling methods
```php
// Instead of:
$vehicle->category->name;

// Use:
$vehicle->category?->name;
// or
if ($vehicle->category) {
    $vehicle->category->name;
}
```

#### "Route not defined"
**Cause**: Route name doesn't exist or not cached
**Solution**: Check route definition and clear cache
```bash
php artisan route:clear
php artisan route:list | grep route_name
```

#### "404 Not Found" for API endpoints
**Cause**: Missing API route prefix or incorrect method
**Solution**: Ensure routes are defined in api.php
```php
// routes/api.php
Route::middleware('auth:sanctum')->get('/vehicles', [VehicleController::class, 'index']);
```

---

*This file contains troubleshooting solutions for common issues. For essential patterns and guidelines, see the main CLAUDE.md file.*