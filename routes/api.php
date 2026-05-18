<?php

use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Api\CategoryFeaturesController;
use App\Http\Controllers\Api\FooterController;
use App\Http\Controllers\BookingAddonController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\RadiusApiController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\UserDocumentController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleCategoryController;
use App\Http\Controllers\PopularPlacesController;
use App\Http\Controllers\Api\SeoMetaController; // Added for SEO Meta
use App\Http\Controllers\Admin\PayableSettingController; // Import PayableSettingController
use App\Http\Controllers\ProviderLocationController;
use App\Http\Controllers\Vendor\VendorOverviewController; // Import VendorOverviewController
use App\Http\Controllers\Admin\ContactUsPageController;
use App\Http\Controllers\NewsletterSubscriptionController;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Route to get features for a specific category (public)
Route::get('/categories/{category}/features', [CategoryFeaturesController::class, 'index'])
    ->name('api.categories.features');

Route::middleware('auth:sanctum')->get('/messages/unread-count', [MessageController::class, 'getUnreadCount'])->name('messages.unreadCount');
Route::middleware('auth:sanctum')->post('/messages/mark-as-read/{booking_id}', [MessageController::class, 'markMessagesAsRead']);
Route::get('/vehicle-categories', [VehicleCategoryController::class, 'index'])->name('api.vehicle-categories.index');
Route::get('/vehicle-features', [VehicleController::class, 'getFeatures'])->name('api.vehicle-features.index');
Route::get('/vehicles', [VehicleController::class, 'showAllVendorVehicles']);

Route::get('/popular-places', [PopularPlacesController::class, 'index']);

Route::middleware('auth:sanctum')->get('/user-documents', [UserDocumentController::class, 'getUserDocuments']);



// Internal API for Vrooem Gateway (authenticated via bearer token)
Route::get('/internal/locations', [\App\Http\Controllers\Api\InternalLocationController::class, 'index'])
    ->middleware('gateway.token');
Route::get('/internal/vehicles', [\App\Http\Controllers\Api\InternalVehicleController::class, 'index'])
    ->middleware('gateway.token');

// Booking API's

Route::get('/plans', [PlanController::class, 'index']);
Route::get('/booking-addons', [BookingAddonController::class, 'index']);


Route::get('/user-count', function () {
    return response()->json(['count' => User::count()]);
});

Route::get('/users', function () {
    $users = User::all();
    return response()->json($users);
});
Route::get('/booking-success/details', [BookingController::class, 'getBookingDetails'])->name('booking-success.details');
Route::get('/currencies', [CurrencyController::class, 'index']);
Route::get('/currency-rates', [CurrencyController::class, 'rates'])->name('api.currency-rates');
// Route::get('/vendor/payments', [BookingController::class, 'getVendorPaymentHistory'])->name('vendor.payments');
// In your api.php routes file
Route::get('/vendors/{vendorProfileId}/reviews', [ReviewController::class, 'getApprovedReviews']);



// this is for recent blogs
Route::get('/recent-blogs', [BlogController::class, 'getRecentBlogs']);

Route::get('/faqs', [FaqController::class, 'getFaqs']);

Route::get('/vehicles/search-locations', [VehicleController::class, 'searchLocations'])->name('vehicles.search-locations');

Route::get('/testimonials/frontend', [App\Http\Controllers\Admin\TestimonialController::class, 'getFrontendTestimonials']);

Route::get('/radius', [RadiusApiController::class, 'getRadius']);

// SEO Meta Route
Route::get('/seo-meta', [SeoMetaController::class, 'getMetaBySlug'])->name('api.seo-meta.get');

Route::get('/admin/profile', [App\Http\Controllers\AdminProfileController::class, 'getAdminProfile']);

// Public API for fetching payment percentage
Route::get('/payment-percentage', [PayableSettingController::class, 'getPercentage'])->name('api.payment-percentage');

Route::get('/{provider}/dropoff-locations/{location_id}', [ProviderLocationController::class, 'getDropoffLocationsForProvider'])->name('api.provider.dropoff-locations');

// Route for unified location search
Route::get('/unified-locations', [App\Http\Controllers\SearchController::class, 'searchUnifiedLocations'])->name('api.unified-locations.search');


// Vendor API routes - Note: Moved to web.php for proper authentication
Route::get('/offers', [\App\Http\Controllers\OfferController::class, 'index']);
Route::get('/offers/active-price', [\App\Http\Controllers\OfferController::class, 'activePriceOffer']);
Route::get('/advertisement', [\App\Http\Controllers\OfferController::class, 'index']);
Route::get('/active-promo', [\App\Http\Controllers\OfferController::class, 'activePriceOffer']);
Route::get('/footer-contact-info', [ContactUsPageController::class, 'getContactInfo']);
Route::post('/newsletter/subscriptions', [NewsletterSubscriptionController::class, 'store'])
    ->middleware('throttle:newsletter');

// Internal Provider API for Vrooem Gateway
Route::prefix('internal/provider')->middleware('gateway.token')->group(function () {
    Route::post('/vehicles/search', [\App\Http\Controllers\Api\InternalProviderController::class, 'searchVehicles']);
    Route::get('/vehicles/{vehicleId}/extras', [\App\Http\Controllers\Api\InternalProviderController::class, 'getVehicleExtras']);
    Route::post('/bookings', [\App\Http\Controllers\Api\InternalProviderController::class, 'createBooking']);
    Route::get('/bookings/{bookingNumber}', [\App\Http\Controllers\Api\InternalProviderController::class, 'getBooking']);
    Route::post('/bookings/{bookingNumber}/cancel', [\App\Http\Controllers\Api\InternalProviderController::class, 'cancelBooking']);
});

// Stripe Checkout Routes
Route::post('/stripe/checkout', [\App\Http\Controllers\StripeCheckoutController::class, 'createSession'])->name('api.stripe.checkout');
Route::post('/stripe/webhook', [\App\Http\Controllers\StripeWebhookController::class, 'handle'])->name('api.stripe.webhook');

// Mobile API Routes (Sanctum tokens)
Route::prefix('mobile')->middleware('throttle:mobile')->group(function () {
    Route::middleware('throttle:mobile-auth')->group(function () {
        Route::post('/auth/register', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'register']);
        Route::post('/auth/login', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'login']);
        Route::post('/auth/apple', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'appleSignIn']);
        Route::post('/auth/check-availability', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'checkAvailability']);
    });

    Route::get('/locations/search', [\App\Http\Controllers\Api\Mobile\LocationController::class, 'search']);
    Route::get('/locations/{id}/dropoffs', [\App\Http\Controllers\Api\Mobile\LocationController::class, 'dropoffsFor'])->whereNumber('id');
    Route::get('/locations/{id}', [\App\Http\Controllers\Api\Mobile\LocationController::class, 'show'])->whereNumber('id');

    Route::post('/vehicles/search', [\App\Http\Controllers\Api\Mobile\VehicleSearchController::class, 'search']);
    Route::post('/vehicles/{id}/availability', [\App\Http\Controllers\Api\Mobile\VehicleSearchController::class, 'checkAvailability'])->whereNumber('id');

    Route::get('/currencies', [\App\Http\Controllers\Api\Mobile\CurrencyController::class, 'index']);
    Route::get('/currency-rates', [\App\Http\Controllers\Api\Mobile\CurrencyController::class, 'rates']);

    Route::get('/home/popular-places', [\App\Http\Controllers\Api\Mobile\HomeController::class, 'popularPlaces']);
    Route::get('/home/offers', [\App\Http\Controllers\Api\Mobile\HomeController::class, 'offers']);

    Route::get('/pages/{slug}', [\App\Http\Controllers\Api\Mobile\PageController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'me']);
        Route::post('/auth/logout', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'logout']);
        Route::post('/profile', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'updateProfile']);
        Route::post('/profile/password', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'changePassword']);
        Route::delete('/profile', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'deleteAccount']);

        Route::post('/push/register', [\App\Http\Controllers\Api\Mobile\PushController::class, 'register']);
        Route::delete('/push/register', [\App\Http\Controllers\Api\Mobile\PushController::class, 'unregister']);

        Route::get('/messages', [\App\Http\Controllers\Api\Mobile\MessageController::class, 'inbox']);
        Route::get('/messages/unread-count', [\App\Http\Controllers\Api\Mobile\MessageController::class, 'unreadCount']);
        Route::get('/messages/{booking_id}', [\App\Http\Controllers\Api\Mobile\MessageController::class, 'thread'])->whereNumber('booking_id');
        Route::post('/messages', [\App\Http\Controllers\Api\Mobile\MessageController::class, 'send']);
        Route::post('/messages/{booking_id}/read', [\App\Http\Controllers\Api\Mobile\MessageController::class, 'markRead'])->whereNumber('booking_id');
        Route::delete('/messages/{id}', [\App\Http\Controllers\Api\Mobile\MessageController::class, 'destroy'])->whereNumber('id');
        Route::post('/messages/{id}/restore', [\App\Http\Controllers\Api\Mobile\MessageController::class, 'restore'])->whereNumber('id');

        Route::get('/notifications', [\App\Http\Controllers\Api\Mobile\NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [\App\Http\Controllers\Api\Mobile\NotificationController::class, 'unreadCount']);
        Route::post('/notifications/{id}/read', [\App\Http\Controllers\Api\Mobile\NotificationController::class, 'markAsRead']);
        Route::post('/notifications/mark-all-read', [\App\Http\Controllers\Api\Mobile\NotificationController::class, 'markAllAsRead']);
        Route::delete('/notifications', [\App\Http\Controllers\Api\Mobile\NotificationController::class, 'clearAll']);

        Route::get('/bookings', [\App\Http\Controllers\Api\Mobile\BookingController::class, 'index']);
        Route::get('/bookings/by-session', [\App\Http\Controllers\Api\Mobile\BookingController::class, 'bySession']);
        Route::get('/bookings/{id}', [\App\Http\Controllers\Api\Mobile\BookingController::class, 'show'])->whereNumber('id');
        Route::get('/bookings/{id}/receipt', [\App\Http\Controllers\Api\Mobile\BookingController::class, 'downloadReceipt'])->whereNumber('id');
        Route::post('/bookings/{id}/cancel', [\App\Http\Controllers\Api\Mobile\BookingController::class, 'cancel'])->whereNumber('id');
        Route::post('/bookings/{id}/review', [\App\Http\Controllers\Api\Mobile\ReviewController::class, 'store'])->whereNumber('id');
        Route::get('/reviews', [\App\Http\Controllers\Api\Mobile\ReviewController::class, 'index']);

        Route::get('/favorites', [\App\Http\Controllers\Api\Mobile\FavoriteController::class, 'index']);
        Route::get('/favorites/status', [\App\Http\Controllers\Api\Mobile\FavoriteController::class, 'status']);
        Route::post('/favorites/toggle', [\App\Http\Controllers\Api\Mobile\FavoriteController::class, 'toggle']);

        // Vendor registration (customer → vendor upgrade)
        Route::post('/vendor/register', [\App\Http\Controllers\Api\Mobile\VendorRegistrationController::class, 'store']);

        // Vendor-only routes
        Route::prefix('vendor')->group(function () {
            Route::get('/overview', [\App\Http\Controllers\Api\Mobile\Vendor\OverviewController::class, 'index']);
            Route::get('/bookings', [\App\Http\Controllers\Api\Mobile\Vendor\BookingsController::class, 'index']);
            Route::get('/bookings-calendar', [\App\Http\Controllers\Api\Mobile\Vendor\BookingsController::class, 'calendar']);
            Route::get('/bookings/{id}', [\App\Http\Controllers\Api\Mobile\Vendor\BookingsController::class, 'show'])->whereNumber('id');
            Route::post('/bookings/{id}/status', [\App\Http\Controllers\Api\Mobile\Vendor\BookingsController::class, 'updateStatus'])->whereNumber('id');

            Route::get('/vehicles', [\App\Http\Controllers\Api\Mobile\Vendor\VehiclesController::class, 'index']);
            Route::get('/vehicles/{id}', [\App\Http\Controllers\Api\Mobile\Vendor\VehiclesController::class, 'show'])->whereNumber('id');
            Route::post('/vehicles/{id}/status', [\App\Http\Controllers\Api\Mobile\Vendor\VehiclesController::class, 'updateStatus'])->whereNumber('id');

            Route::get('/vehicles/{vehicle}/blockings', [\App\Http\Controllers\Api\Mobile\Vendor\BlockingDatesController::class, 'index'])->whereNumber('vehicle');
            Route::post('/vehicles/{vehicle}/blockings', [\App\Http\Controllers\Api\Mobile\Vendor\BlockingDatesController::class, 'store'])->whereNumber('vehicle');
            Route::delete('/vehicles/{vehicle}/blockings/{id}', [\App\Http\Controllers\Api\Mobile\Vendor\BlockingDatesController::class, 'destroy'])->whereNumber('vehicle')->whereNumber('id');

            Route::get('/payments', [\App\Http\Controllers\Api\Mobile\Vendor\PaymentsController::class, 'index']);

            Route::get('/reviews', [\App\Http\Controllers\Api\Mobile\Vendor\ReviewsController::class, 'index']);
            Route::post('/reviews/{id}/status', [\App\Http\Controllers\Api\Mobile\Vendor\ReviewsController::class, 'updateStatus'])->whereNumber('id');
            Route::post('/reviews/{id}/reply', [\App\Http\Controllers\Api\Mobile\Vendor\ReviewsController::class, 'reply'])->whereNumber('id');

            Route::get('/external-bookings', [\App\Http\Controllers\Api\Mobile\Vendor\ExternalBookingsController::class, 'index']);
            Route::get('/external-bookings/{id}', [\App\Http\Controllers\Api\Mobile\Vendor\ExternalBookingsController::class, 'show'])->whereNumber('id');
            Route::post('/external-bookings/{id}/status', [\App\Http\Controllers\Api\Mobile\Vendor\ExternalBookingsController::class, 'updateStatus'])->whereNumber('id');

            Route::get('/messages', [\App\Http\Controllers\Api\Mobile\MessageController::class, 'vendorInbox']);
        });

        Route::get('/documents', [\App\Http\Controllers\Api\Mobile\DocumentController::class, 'show']);
        Route::post('/documents', [\App\Http\Controllers\Api\Mobile\DocumentController::class, 'upload']);
        Route::delete('/documents/{field}', [\App\Http\Controllers\Api\Mobile\DocumentController::class, 'destroy']);
    });
});
