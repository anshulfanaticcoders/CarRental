<?php

use App\Http\Controllers\BookingExtraController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\UserDocumentController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleCategoryController;
use App\Http\Controllers\PopularPlacesController;
use Illuminate\Http\Request;
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

Route::get('/documents', [UserDocumentController::class, 'getUserDocuments']);


// Booking API's

    Route::get('/plans', [PlanController::class, 'index']);
    Route::get('/booking-extras', [BookingExtraController::class, 'index']);

