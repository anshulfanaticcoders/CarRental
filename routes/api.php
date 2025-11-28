<?php

use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\AdobeController;
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
use App\Http\Controllers\GreenMotionController; // Import GreenMotionController
use App\Http\Controllers\Vendor\VendorOverviewController; // Import VendorOverviewController
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

// Adobe API routes (must come before generic provider routes)
Route::get('/adobe/vehicles', [AdobeController::class, 'getVehicles'])->name('api.adobe.vehicles');
Route::get('/adobe/vehicle-details', [AdobeController::class, 'getVehicleDetails'])->name('api.adobe.vehicle-details');
Route::get('/adobe/dropoff-locations/{location_id}', [AdobeController::class, 'getDropoffLocationsForProvider'])->name('api.adobe.dropoff-locations');

// GreenMotion API routes
Route::get('/greenmotion/locations-autocomplete', [GreenMotionController::class, 'getGreenMotionLocationsForAutocomplete'])->name('api.greenmotion.locations-autocomplete');
Route::get('/{provider}/dropoff-locations/{location_id}', [GreenMotionController::class, 'getDropoffLocationsForProvider'])->name('api.provider.dropoff-locations');

// Route for unified location search
Route::get('/unified-locations', [App\Http\Controllers\SearchController::class, 'searchUnifiedLocations'])->name('api.unified-locations.search');


// Vendor API routes - Note: Moved to web.php for proper authentication
