<?php

use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleCategoryController;
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

// Get Vehicle Categories from the database added by the Admin 
Route::get('/vehicle-categories', [VehicleCategoryController::class, 'index']);

Route::get('/vehicle-features', [VehicleController::class, 'getFeatures']);

Route::get('/vehicle/{id}', [VehicleController::class, 'show']);
