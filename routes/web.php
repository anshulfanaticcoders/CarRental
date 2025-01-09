<?php

use App\Http\Controllers\GeocodingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\VendorDocumentController;
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
    Route::get('/vehicle/{id}', [VehicleController::class, 'show']);
});



Route::post('/documents/upload', [UserDocumentController::class, 'uploadDocuments'])->name('documents.upload');

// Routing for Travel documents
Route::inertia('single-car/vehicle/{id}', 'SingleCar');

Route::inertia('vehicle-listing', 'Auth/VehicleListing');

// Route to display the form for creating a new vehicle
Route::get('/vehicles/create', [VehicleController::class, 'create'])->name('vehicles.create');

// Route to store a new vehicle
Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');

// Route to display the list of vehicles
Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');


// Map api
Route::get('/s', [SearchController::class, 'search']);
Route::get('/vehicle/{vehicle}', [SearchController::class, 'show']);

Route::get('/api/geocoding/autocomplete', [GeocodingController::class, 'autocomplete']);

require __DIR__ . '/auth.php';
