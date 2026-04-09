<?php

use App\Http\Controllers\Skyscanner\CarHireRedirectController;
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

Route::prefix('skyscanner')->group(function () {
    Route::post('car-hire/search', CarHireSearchController::class)
        ->name('skyscanner.car-hire.search');

    Route::get('redirect', CarHireRedirectController::class)
        ->name('skyscanner.redirect');
});
