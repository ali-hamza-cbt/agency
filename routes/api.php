<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\Web\BrandController;
use App\Http\Controllers\Api\Web\AgencyDetailController;
use App\Http\Controllers\Api\Web\AuthController as WebAuth;
use App\Http\Controllers\Api\Mobile\AuthController as MobileAuth;

Route::prefix('web')->group(function () {
    Route::post('/register', [WebAuth::class, 'register'])->middleware('throttle:5,1');
    Route::post('/login', [WebAuth::class, 'login']);
    Route::post('/refresh', [WebAuth::class, 'refresh'])->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', fn() => auth()->user())->middleware('auth:sanctum');
        Route::post('/logout', [WebAuth::class, 'logout']);
        Route::post('/logout-all', [WebAuth::class, 'logoutAll']);
        Route::get('/sessions', [SessionController::class, 'index']);
        Route::delete('/sessions/{id}', [SessionController::class, 'destroy']);

        /**
         * Agency Details
         */
        Route::post('/store-agency-details', [AgencyDetailController::class, 'store'])->name('agency-details.store');
        Route::post('/upate-agency-details', [AgencyDetailController::class, 'update'])->name('agency-details.update');

        /**
         * Brands
         */
        Route::controller(BrandController::class)->prefix('brands')->group(function () {

            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('/{id}', 'show');
            Route::post('/{id}/update', 'update');
            Route::delete('/{id}', 'destroy');

            // Status change
            Route::post('/{id}/change-status', 'changeStatus');

            // Trashed/Restore/Force Delete
            Route::get('/trashed', 'trashed');
            Route::post('/{id}/restore', 'restore');
            Route::delete('/{id}/force-delete', 'forceDelete');

            // Bulk actions
            Route::post('/bulk-delete', 'bulkDelete');
            Route::post('/bulk-restore', 'bulkRestore');
        });
    });
});

Route::prefix('mobile')->group(function () {
    Route::post('/register', [MobileAuth::class, 'register'])->middleware('throttle:5,1');
    Route::post('/login', [MobileAuth::class, 'login'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/refresh', [MobileAuth::class, 'refresh'])->middleware('throttle:10,1');
        Route::post('/logout', [MobileAuth::class, 'logout']);
        Route::post('/logout-all', [MobileAuth::class, 'logoutAll']);
        Route::get('/sessions', [SessionController::class, 'index']);
        Route::delete('/sessions/{id}', [SessionController::class, 'destroy']);
    });
});
