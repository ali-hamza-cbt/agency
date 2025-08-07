<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\Web\AgencyDetailController;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;
use App\Http\Controllers\Api\Web\AuthController as WebAuth;

Route::prefix('web')->group(function () {
    Route::post('/register', [WebAuth::class, 'register'])->middleware('throttle:5,1');
    Route::post('/login', [WebAuth::class, 'login'])->middleware('throttle:5,1');
    Route::post('/refresh', [WebAuth::class, 'refresh'])->middleware('throttle:10,1');

    Route::middleware(['auth:sanctum', 'validate.sanctum.expiry'])->group(function () {
        Route::get('/profile', fn() => auth()->user());
        Route::post('/logout', [WebAuth::class, 'logout']);
        Route::post('/logout-all', [WebAuth::class, 'logoutAll']);
        Route::get('/sessions', [SessionController::class, 'index']);
        Route::delete('/sessions/{id}', [SessionController::class, 'destroy']);

        /**
         * Agency Details
         */
        Route::post('/store-agency-details', [AgencyDetailController::class, 'store'])->name('agency-details.store');
        Route::post('/upate-agency-details', [AgencyDetailController::class, 'update'])->name('agency-details.update');
    });
});





/**
 * Generate Csrf Token
 */
Route::get('web/sanctum/csrf-cookie', [CsrfCookieController::class, 'show'])->name('sanctum.csrf-cookie');
