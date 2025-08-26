<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\Web\BrandController;
use App\Http\Controllers\Api\Web\ProductController;
use App\Http\Controllers\Api\Web\CategoryController;
use App\Http\Controllers\Api\Web\AgencyDetailController;
use App\Http\Controllers\Api\Web\ProductBatchController;
use App\Http\Controllers\Api\Web\AuthController as WebAuth;
use App\Http\Controllers\Api\Mobile\AuthController as MobileAuth;

Route::prefix('web')->group(function () {
    Route::post('/register', [WebAuth::class, 'register'])->middleware('throttle:5,1');
    Route::post('/login', [WebAuth::class, 'login']);
    Route::post('/refresh', [WebAuth::class, 'refresh'])->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', fn() => Auth::user())->middleware('auth:sanctum');
        Route::post('/logout', [WebAuth::class, 'logout']);
        Route::post('/logout-all', [WebAuth::class, 'logoutAll']);
        Route::get('/sessions', [SessionController::class, 'index']);
        Route::delete('/sessions/{id}', [SessionController::class, 'destroy']);

        /**
         * Agency Details
         */
        Route::post('/store-agency-details', [AgencyDetailController::class, 'store'])->name('agency-details.store');

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

        /**
         * Categories
         */
        Route::controller(CategoryController::class)->prefix('brands')->group(function () {

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
        /**
         * Products
         */
        Route::controller(ProductController::class)->prefix('products')->group(function () {
            Route::get('/', 'index');                  // List products
            Route::post('/', 'store');                 // Create product
            Route::get('/{id}', 'show');               // Show product details
            Route::post('/{id}/update', 'update');    // Update product
            Route::delete('/{id}', 'delete');         // Soft delete product

            // Status change
            Route::post('/{id}/change-status', 'changeStatus');

            // Trashed / Restore / Force Delete
            Route::get('/trashed', 'trashed');        // List trashed products
            Route::post('/{id}/restore', 'restore');  // Restore soft deleted product
            Route::delete('/{id}/force-delete', 'forceDelete'); // Permanent delete

            // Bulk actions
            Route::post('/bulk-delete', 'bulkDelete');
            Route::post('/bulk-restore', 'bulkRestore');
        });

        /**
         * Product Batches
         */
        Route::controller(ProductBatchController::class)->prefix('product-batches')->group(function () {
            Route::get('/', 'index');                  // List batches
            Route::post('/', 'store');                 // Create batch
            Route::get('/{id}', 'show');               // Show batch details
            Route::post('/{id}/update', 'update');    // Update batch
            Route::delete('/{id}', 'delete');         // Soft delete batch

            // Trashed / Restore / Force Delete
            Route::get('/trashed', 'trashed');        // List trashed batches
            Route::post('/{id}/restore', 'restore');  // Restore soft deleted batch
            Route::delete('/{id}/force-delete', 'forceDelete'); // Permanent delete
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
