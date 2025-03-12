<?php

use App\Http\Controllers\Admin\ActivityLogsController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\BookingDashboardController;
use App\Http\Controllers\Admin\BusinessReportsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PaymentDashboardController;
use App\Http\Controllers\Admin\PlansController;
use App\Http\Controllers\Admin\PopularPlacesController;
use App\Http\Controllers\Admin\UserReportDownloadController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\UsersReportController;
use App\Http\Controllers\Admin\VehicleAddonsController;
use App\Http\Controllers\Admin\VehicleDashboardController;

use App\Http\Controllers\Admin\VendorsReportController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\FrontendPageController;
use App\Http\Controllers\GeocodingController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Admin\VehicleCategoriesController;
use App\Http\Controllers\Vendor\BlockingDateController;
use App\Http\Controllers\Vendor\DamageProtectionController;
use App\Http\Controllers\Vendor\PlanController;
use App\Http\Controllers\Vendor\VendorBookingController;
use App\Http\Controllers\Vendor\VendorOverviewController;
use App\Http\Controllers\Vendor\VendorVehicleAddonController;
use App\Http\Controllers\Vendor\VendorVehicleController;
use App\Http\Controllers\Vendor\VendorVehiclePlanController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Admin\VendorsDashboardController;
use App\Models\Booking;
use App\Models\Message;
use App\Models\Page;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\UserDocumentController;
use App\Http\Controllers\VehicleController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// --------------*************-------------Route starts----------**************---------------
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/user', [ProfileController::class, 'show'])->name('user.profile');
    // Vehicle Listing 
    // Route::inertia('vehicle-listing', 'Auth/VehicleListing');

    Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');


    Route::get('/messages', [BookingController::class, 'getCustomerBookingsForMessages'])->name('messages.index');
    Route::get('/messages/vendor', [MessageController::class, 'vendorIndex'])->name('messages.vendor.index');
    Route::get('/messages/{booking}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::get('/messages/unread', [MessageController::class, 'getUnreadCount'])->name('messages.unread');
    Route::get('/messages/{booking}/last', [MessageController::class, 'getLastMessage'])->name('messages.last');
    Route::delete('/messages/{id}', [MessageController::class, 'destroy'])->name('messages.destroy');

    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-read/{id}', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::get('/notifications/unread', [NotificationController::class, 'getUnreadCount'])->name('notifications.unread');

    Route::get('/profile/completion', [ProfileController::class, 'getProfileCompletion'])->name('profile.completion');

});
Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
// Open Routes for non-logged in users
Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
Route::get('/vehicle/{id}', [VehicleController::class, 'show'])->name('vehicle.show');
Route::get('/s', [SearchController::class, 'search']);
Route::get('/api/geocoding/autocomplete', [GeocodingController::class, 'autocomplete']);
Route::get('/blogs-page', [BlogController::class, 'showBlogPage'])->name('blogs-page');
Route::get('/page/{slug}', [FrontendPageController::class, 'show'])->name('pages.show');


// Show Blogs on Home page
// Route::get('/', [BlogController::class, 'show'])->name('welcome');
Route::get('/', [BlogController::class, 'homeBlogs'])->name('welcome');
Route::get('blog/{blog}', [BlogController::class, 'show'])->name('blog.show');


// Stripe Routes
Route::get('/payment', [PaymentController::class, 'index'])->name('payment.index');



// These are the Routes for ---------*****ADMIN****-------------
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::inertia('admin-dashboard', 'AdminDashboard');
    Route::resource('vehicles-categories', VehicleCategoriesController::class)->parameters(['vehicles-categories' => 'vehicleCategory']);
    Route::resource('vendors', VendorsDashboardController::class)->except(['create', 'edit', 'show']);
    Route::put('/vendors/{vendorProfile}/status', [VendorsDashboardController::class, 'updateStatus'])->name('vendors.updateStatus');

    Route::resource('users', UsersController::class)->except(['create', 'edit', 'show']);
    Route::resource('vendor-vehicles', VehicleDashboardController::class)->except(['create', 'edit', 'show']);
    // Route::inertia('vendors', 'AdminDashboardPages/Vendors/Index');
    Route::resource('customer-bookings', BookingDashboardController::class)->except(['create', 'edit', 'show']);
    Route::get('/customer-bookings/pending', [BookingDashboardController::class, 'pending'])->name('customer-bookings.pending');
    Route::get('/customer-bookings/confirmed', [BookingDashboardController::class, 'confirmed'])->name('customer-bookings.confirmed');
    Route::get('/customer-bookings/completed', [BookingDashboardController::class, 'completed'])->name('customer-bookings.completed');
    Route::get('/customer-bookings/cancelled', [BookingDashboardController::class, 'cancelled'])->name('customer-bookings.cancelled');
    Route::resource('booking-addons', VehicleAddonsController::class)->middleware(['auth']);
    Route::resource('popular-places', PopularPlacesController::class)->except(['show']);
    Route::resource('plans', PlansController::class);
    Route::resource('blogs', BlogController::class);
    Route::get('/admin-dashboard', [DashboardController::class, 'index']);

    // activity logs
    Route::get('/activity-logs', [ActivityLogsController::class, 'index'])->name('activity-logs.index');

    // payments
    Route::get('/admin/payments', [PaymentDashboardController::class, 'index'])
        ->name('admin.payments.index');

    // User reports
    Route::get('/users-report', [UsersReportController::class, 'index']);
    Route::get('/vendors-report', [VendorsReportController::class, 'index']);
    Route::get('/business-report', [BusinessReportsController::class, 'index']);

    Route::get('/admin/reports/users/download', [UserReportDownloadController::class, 'downloadXML']);

    Route::resource('pages', PageController::class)->names('admin.pages');

});



// These are the Routes for ---------*****VENDOR****-------------
Route::middleware(['auth', 'role:vendor'])->group(function () {
    // vendor Profile Web Routes
    Route::inertia('vendor-approved', 'Vendor/VendorApproved');
    //Route::inertia('booking-success/details', 'Booking/Success');
    Route::inertia('vendor-pending', 'Vendor/VendorPending');
    Route::inertia('vendor-rejected', 'Vendor/VendorRejected');
    Route::get('/vendor/documents', [VendorController::class, 'index'])->name('vendor.documents.index');
    Route::post('/vendor/update', [VendorController::class, 'update'])->name('vendor.update');
    // creating vehicle routes
    // Route::get('/vehicles/create', [VehicleController::class, 'create'])->name('vehicles.create');
    Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
    Route::get('/vehicle-categories', [VehicleController::class, 'getCategories'])->name('vehicle.categories');

    // this is for showing All Booking details of customer in vendor profile
    Route::resource('bookings', VendorBookingController::class)->names('bookings');
    Route::post('api/bookings/{booking}/cancel', [VendorBookingController::class, 'cancel'])->name('bookings.cancel');

    // this is used to show payment history of all customers on vendor profile page
    Route::get('/vendor/payments', [BookingController::class, 'getVendorPaymentHistory'])->name('vendor.payments');

    // this is for showing All Vehicles of vendor in vendor profile
    Route::resource('current-vendor-vehicles', VendorVehicleController::class);
    Route::delete('/current-vendor-vehicles/{vehicle}/images/{image}', [VendorVehicleController::class, 'deleteImage'])
        ->name('current-vendor-vehicles.deleteImage');

    //this route is used to block rental dates in vendor profile 
    Route::resource('blocking-dates', BlockingDateController::class)->names('vendor.blocking-dates');

    // Customer review in Vendor Profile
    Route::get('/customer-reviews', [ReviewController::class, 'vendorReviews'])
        ->name('vendor.reviews');
    Route::patch('/reviews/{review}/status', [ReviewController::class, 'updateStatus'])
        ->name('reviews.update-status');

    // Vendor Overview
    Route::get('/overview', [VendorOverviewController::class, 'index'])->name('vendor.overview');

    // these are for damage protection on vendor profile page
    Route::get('/damage-protection/{booking}', [DamageProtectionController::class, 'index'])
        ->name('vendor.damage-protection.index');

    Route::post('/damage-protection/{booking}/upload-before', [DamageProtectionController::class, 'uploadBeforeImages'])
        ->name('vendor.damage-protection.upload-before');

    Route::post('/damage-protection/{booking}/upload-after', [DamageProtectionController::class, 'uploadAfterImages'])
        ->name('vendor.damage-protection.upload-after');

    Route::delete('/damage-protection/{booking}/delete-before', [DamageProtectionController::class, 'deleteBeforeImages'])
        ->name('vendor.damage-protection.delete-before-images');

    Route::delete('/damage-protection/{booking}/delete-after', [DamageProtectionController::class, 'deleteAfterImages'])
        ->name('vendor.damage-protection.delete-after-images');


    // these are used to get plans on the vehicle listing page
    Route::get('/plans', [VendorVehiclePlanController::class, 'getPlans']);
    Route::post('/vehicle-plans', [VendorVehiclePlanController::class, 'store']);
    Route::get('/vehicle-plans/{vehicleId}', [VendorVehiclePlanController::class, 'getVehiclePlans']);
    Route::delete('/vehicle-plans/{id}', [VendorVehiclePlanController::class, 'destroy']);

    // these are used on vendor profile page to update plan price 
    Route::get('/plans', [PlanController::class, 'index'])->name('VendorPlanIndex');
    Route::get('vendor/plans/{id}/edit', [PlanController::class, 'edit'])->name('VendorPlanEdit');
    Route::put('vendor/plans/{id}', [PlanController::class, 'update'])->name('VendorPlanUpdate');


    Route::resource('vendor-vehicle-addons', VendorVehicleAddonController::class);

});


// These are the Routes for ---------*****CUSTOMER****-------------
Route::middleware(['auth', 'role:customer'])->group(function () {

    // User Profile routes
    // Route::inertia('travel-documents', 'Profile/TravelDocuments');
    Route::get('/user/documents', [UserDocumentController::class, 'index'])->name('user.documents.index');
    Route::get('/user/documents/create', [UserDocumentController::class, 'create'])->name('user.documents.create');
    Route::post('/user/documents', [UserDocumentController::class, 'store'])->name('user.documents.store');
    Route::get('/user/documents/{document}/edit', [UserDocumentController::class, 'edit'])->name('user.documents.edit');
    Route::patch('/user/documents/{document}', [UserDocumentController::class, 'update'])->name('user.documents.update');
    Route::post('/user/documents/bulk-upload', [UserDocumentController::class, 'bulkUpload'])->name('user.documents.bulk-upload');

    Route::inertia('completed-bookings', 'Profile/CompletedBookings');
    Route::inertia('confirmed-bookings', 'Profile/ConfirmedBookings');
    Route::inertia('pending-bookings', 'Profile/PendingBookings');
    Route::inertia('issued-payments', 'Profile/IssuedPayments');
    Route::inertia('review', 'Profile/Review');
    Route::inertia('favourites', 'Profile/Favourites');
    Route::inertia('inbox', 'Profile/Inbox');
    Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::get('/profile/reviews', [ReviewController::class, 'userReviews'])->name('profile.reviews');

    // apply for vendor
    Route::post('/vendor/store', [VendorController::class, 'store'])->name('vendor.store');
    Route::get('/vendor/register', [VendorController::class, 'create'])->name('vendor.register');

    // Booking Routes
    Route::get('/booking/{id}', [VehicleController::class, 'booking'])->name('booking.show');
    // Route::get('/booking-success', [BookingController::class, 'success'])->name('booking.success');
    Route::get('/booking-success/details', [BookingController::class, 'getBookingDetails'])->name('booking-success.details');
    Route::get('/booking', [BookingController::class, 'create'])->name('booking.create');
    Route::post('/booking', [BookingController::class, 'store'])->name('booking.store');
    Route::post('/payment/charge', [PaymentController::class, 'charge'])->name('payment.charge');
    // this route is to show customer booking in the customer profile
    Route::get('/customer/bookings', [BookingController::class, 'getCustomerBookingData'])->name('customer.bookings');


    //  this is route is for redirecting to the success page after payment done
    Route::get('/booking-success/details', function () {
        return Inertia::render('Booking/Success', [
            'payment_intent' => request('payment_intent'), // Pass payment intent ID
        ]);
    })->name('booking-success.details');

    // these routes are used to get booking confirmation on user profile page
    Route::get('/profile/bookings/pending', [BookingController::class, 'getPendingBookings'])
        ->name('profile.bookings.pending');

    Route::get('/profile/bookings/confirmed', [BookingController::class, 'getConfirmedBookings'])
        ->name('profile.bookings.confirmed');

    Route::get('/profile/bookings/completed', [BookingController::class, 'getCompletedBookings'])
        ->name('profile.bookings.completed');


    //  these routes are used  to add favourite vehicles into customer profile from single car page, search result 
    Route::post('/vehicles/{vehicle}/favourite', [FavoriteController::class, 'favourite'])->name('vehicles.favourite');
    Route::post('/vehicles/{vehicle}/unfavourite', [FavoriteController::class, 'unfavourite'])->name('vehicles.unfavourite');
    Route::get('/favorites', [FavoriteController::class, 'getFavorites']);
});


Route::middleware(['auth', 'vendor.status'])->group(function () {
    Route::get('/vehicles/create', [VehicleController::class, 'create'])->name('vehicles.create');
    Route::inertia('vehicle-listing', 'Auth/VehicleListing');
});



Route::middleware('auth:sanctum')->get('/messages/{booking}/poll', function (Request $request, $booking) {
    $user = Auth::user();
    $lastMessageId = $request->input('last_message_id', 0);

    $booking = Booking::with(['vehicle.vendor', 'customer'])->findOrFail($booking);

    // Ensure the authenticated user is either the customer or the vendor
    $customerId = $booking->customer->user_id;
    $vendorId = $booking->vehicle->vendor_id;

    if ($user->id !== $customerId && $user->id !== $vendorId) {
        abort(403, 'Unauthorized access to this conversation');
    }

    // Get the other participant in the conversation
    $otherUserId = ($user->id === $customerId) ? $vendorId : $customerId;

    // Get new messages
    $messages = Message::where('id', '>', $lastMessageId)
        ->where('booking_id', $booking->id)
        ->where(function ($query) use ($user, $otherUserId) {
            $query->where(function ($q) use ($user, $otherUserId) {
                $q->where('sender_id', $user->id)
                    ->where('receiver_id', $otherUserId);
            })
                ->orWhere(function ($q) use ($user, $otherUserId) {
                    $q->where('sender_id', $otherUserId)
                        ->where('receiver_id', $user->id);
                });
        })
        ->with(['sender', 'receiver'])
        ->orderBy('created_at', 'asc')
        ->get();

    return response()->json([
        'messages' => $messages
    ]);
});
require __DIR__ . '/auth.php';