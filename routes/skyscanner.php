<?php

use App\Http\Controllers\Skyscanner\CarHireRedirectController;
use App\Http\Controllers\Skyscanner\CarHireLocationsController;
use App\Http\Controllers\Skyscanner\CarHireSearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Skyscanner Routes
|--------------------------------------------------------------------------
|
| This file is intentionally isolated from the current live route loading.
| We will wire it into the application only after the integration contract
| and safety checks are finalized.
|
*/

Route::get('quotes/{currency}/{pickup_point}/{dropoff_point}/{pickup_datetime}/{dropoff_datetime}/{driver_age}', CarHireSearchController::class)
    ->name('skyscanner.car-hire.search.rest');

Route::prefix('skyscanner')->group(function () {
    Route::get('locations', CarHireLocationsController::class)
        ->name('skyscanner.locations');

    Route::post('car-hire/search', CarHireSearchController::class)
        ->name('skyscanner.car-hire.search');

    Route::get('redirect', CarHireRedirectController::class)
        ->name('skyscanner.redirect');
});
