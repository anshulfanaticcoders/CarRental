<?php

use App\Http\Controllers\Trabber\LocationsController;
use App\Http\Controllers\Trabber\SearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('trabber')->name('trabber.')->group(function () {
    Route::get('/locations', LocationsController::class)->name('locations');
    Route::post('/car-hire/search', SearchController::class)->name('car-hire.search');
});
