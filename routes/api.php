<?php

use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\BookingAddonController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingExtraController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\Notifications\ContactUsNotificationController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\UserDocumentController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleCategoryController;
use App\Http\Controllers\PopularPlacesController;
use App\Models\Booking;
use App\Models\Message;
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
Route::get('/vehicle-categories', [VehicleCategoryController::class, 'index']);
Route::get('/vehicle-features', [VehicleController::class, 'getFeatures']);
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

Route::get('/booking-success/details', [BookingController::class, 'getBookingDetails'])->name('booking-success.details');


// this is for recent blogs
Route::get('/recent-blogs', [BlogController::class, 'getRecentBlogs']);

Route::get('/faqs', [FaqController::class, 'getFaqs']);

Route::get('/notifications/unread', [ContactUsNotificationController::class, 'getUnreadNotifications']);
Route::post('/notifications/mark-as-read', [ContactUsNotificationController::class, 'markAsRead']);