<?php

use App\Http\Controllers\GeocodingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\PaymentController;
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

// -------------web routes for user profile ----------------------
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


// -------------web routes for Vendor profile ----------------------
Route::middleware(['auth'])->group(function () {

    // User Profile routes
    Route::inertia('travel-documents', 'Profile/TravelDocuments');
    Route::post('/documents/upload', [UserDocumentController::class, 'uploadDocuments'])->name('documents.upload');
    Route::inertia('completed-bookings', 'Profile/CompletedBookings');
    Route::inertia('confirmed-bookings', 'Profile/ConfirmedBookings');
    Route::inertia('pending-bookings', 'Profile/PendingBookings');
    Route::inertia('issued-payments', 'Profile/IssuedPayments');
    Route::inertia('review', 'Profile/Review');
    Route::inertia('favourites', 'Profile/Favourites');
    Route::inertia('inbox', 'Profile/Inbox');

    // vendor Profile Web Routes
    Route::get('/vendor/register', [VendorController::class, 'create'])->name('vendor.register');
    Route::post('/vendor/store', [VendorController::class, 'store'])->name('vendor.store');

    // Vehicle Listing 
    Route::inertia('vehicle-listing', 'Auth/VehicleListing');
    Route::get('/vehicles/create', [VehicleController::class, 'create'])->name('vehicles.create');
    Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
    Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');

    // Booking Routes
    Route::get('/booking/{id}', [VehicleController::class, 'booking'])->name('booking.show');
});

Route::inertia('vendor-approved', 'Vendor/VendorApproved');
Route::inertia('vendor-pending', 'Vendor/VendorPending');
Route::inertia('vendor-rejected', 'Vendor/VendorRejected');

// Open route to get to the single car page
Route::get('/vehicle/{id}', [VehicleController::class, 'show'])->name('vehicle.show');

// Route::inertia('single-car/vehicle/{id}', 'SingleCar');


// -------------Map Search Routing---------------
Route::get('/s', [SearchController::class, 'search']);
Route::get('/api/geocoding/autocomplete', [GeocodingController::class, 'autocomplete']);


// Stripe Routes

Route::get('/payment', [PaymentController::class, 'index'])->name('payment.index');
Route::post('/payment/charge', [PaymentController::class, 'charge'])->name('payment.charge');

Route::inertia('admin-dashboard', 'AdminDashboard');
require __DIR__ . '/auth.php';
