<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\Web\BrandController;
use App\Http\Controllers\Api\Web\ProductController;
use App\Http\Controllers\Api\Web\CategoryController;
use App\Http\Controllers\Api\Web\AgencyDetailController;
use App\Http\Controllers\Api\Web\ProductBatchController;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;
use App\Http\Controllers\Api\Web\AuthController as WebAuth;

Route::prefix('web')->group(function () {
    Route::post('/register', [WebAuth::class, 'register'])->middleware('throttle:5,1');
    Route::post('/login', [WebAuth::class, 'login'])->middleware('throttle:5,1');
    Route::post('/refresh', [WebAuth::class, 'refresh'])->middleware('throttle:10,1');

    Route::middleware(['auth:sanctum', 'validate.sanctum.expiry'])->group(function () {
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
        Route::prefix('brands')->controller(BrandController::class)->group(function () {

            // CRUD
            Route::get('/', 'index');               // list brands
            Route::post('/', 'store');              // create brand
            Route::get('/trashed', 'trashed');      // trashed brands
            Route::get('/{id}', 'show');            // show brand
            Route::post('/{id}/update', 'update');  // update brand
            Route::delete('/{id}', 'destroy');      // delete brand

            // Actions
            Route::post('/{id}/change-status', 'changeStatus');
            Route::post('/{id}/restore', 'restore');
            Route::delete('/{id}/force-delete', 'forceDelete');

            // Bulk actions
            Route::post('/bulk-delete', 'bulkDelete');
            Route::post('/bulk-restore', 'bulkRestore');
        });

        /**
         * Categories
         */
        Route::prefix('categories')->controller(CategoryController::class)->group(function () {

            // CRUD
            Route::get('/', 'index');               // list brands
            Route::post('/', 'store');              // create brand
            Route::get('/trashed', 'trashed');      // trashed brands
            Route::get('/{id}', 'show');            // show brand
            Route::post('/{id}/update', 'update');  // update brand
            Route::delete('/{id}', 'destroy');      // delete brand

            // Actions
            Route::post('/{id}/change-status', 'changeStatus');
            Route::post('/{id}/restore', 'restore');
            Route::delete('/{id}/force-delete', 'forceDelete');

            // Bulk actions
            Route::post('/bulk-delete', 'bulkDelete');
            Route::post('/bulk-restore', 'bulkRestore');
        });

        /**
         * Products
         */
        Route::prefix('products')->controller(ProductController::class)->group(function () {

            // CRUD
            Route::get('/', 'index');               // list brands
            Route::post('/', 'store');              // create brand
            Route::get('/trashed', 'trashed');      // trashed brands
            Route::get('/{id}', 'show');            // show brand
            Route::post('/{id}/update', 'update');  // update brand
            Route::delete('/{id}', 'destroy');      // delete brand

            // Actions
            Route::post('/{id}/change-status', 'changeStatus');
            Route::post('/{id}/restore', 'restore');
            Route::delete('/{id}/force-delete', 'forceDelete');

            // Bulk actions
            Route::post('/bulk-delete', 'bulkDelete');
            Route::post('/bulk-restore', 'bulkRestore');

            // Product With Batches
            Route::post('/{id}/with-batches', 'withBatches');
            Route::post('/{id}/with-deleted-batches', 'withDeleteBatches');
        });

        /**
         * Product Batches
         */
        Route::prefix('product-batches')->controller(ProductBatchController::class)->group(function () {

            // CRUD
            Route::get('/', 'index');               // list brands
            Route::post('/', 'store');              // create brand
            Route::get('/trashed', 'trashed');      // trashed brands
            Route::get('/{id}', 'show');            // show brand
            Route::post('/{id}/update', 'update');  // update brand
            Route::delete('/{id}', 'destroy');      // delete brand

            // Actions
            Route::post('/{id}/change-status', 'changeStatus');
            Route::post('/{id}/restore', 'restore');
            Route::delete('/{id}/force-delete', 'forceDelete');

            // Bulk actions
            Route::post('/bulk-delete', 'bulkDelete');
            Route::post('/bulk-restore', 'bulkRestore');
        });
    });
});


/**
 * Generate Csrf Token
 */
Route::get('web/sanctum/csrf-cookie', [CsrfCookieController::class, 'show'])->name('sanctum.csrf-cookie');
