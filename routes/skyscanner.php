<?php

use App\Http\Controllers\Skyscanner\CarHireLocationsController;
use App\Http\Controllers\Skyscanner\CarHireRedirectController;
use App\Http\Controllers\Skyscanner\CarHireSearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Skyscanner Routes
|--------------------------------------------------------------------------
|
| Gated on SKYSCANNER_ENABLED — the documented kill switch finally does
| something. Rate-limited by API key (throttle:skyscanner), not by the
| shared per-IP bucket that 429'd partner bursts.
|
*/

if (! config('skyscanner.enabled')) {
    return;
}

Route::get('quotes/{currency}/{pickup_point}/{dropoff_point}/{pickup_datetime}/{dropoff_datetime}/{driver_age}', CarHireSearchController::class)
    ->middleware('throttle:skyscanner')
    ->name('skyscanner.car-hire.search.rest');

Route::prefix('skyscanner')->middleware('throttle:skyscanner')->group(function () {
    Route::get('locations', CarHireLocationsController::class)
        ->name('skyscanner.locations');

    Route::post('car-hire/search', CarHireSearchController::class)
        ->name('skyscanner.car-hire.search');

    Route::get('redirect', CarHireRedirectController::class)
        ->name('skyscanner.redirect');
});
