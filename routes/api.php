<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\Web\BrandController;
use App\Http\Controllers\Api\Web\ProductController;
use App\Http\Controllers\Api\Web\CategoryController;
use App\Http\Controllers\Api\Web\RetailerController;
use App\Http\Controllers\Api\Web\SalesmanController;
use App\Http\Controllers\Api\Web\DeliveryBoyController;
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

        /**
         * Staff Routes
         */
        Route::prefix('staff')->group(function () {

            /**
             * Salesmen Routes
             */
            Route::prefix('salesmen')->controller(SalesmanController::class)->group(function () {
                Route::get('/', 'index');            // List salesmen
                Route::post('/', 'store');           // Create salesman
                Route::get('{id}', 'show');          // Show salesman details
                Route::put('{id}', 'update');        // Update salesman
                Route::delete('{id}', 'destroy');    // Soft delete salesman
                Route::post('restore/{id}', 'restore'); // Restore salesman
            });

            /**
             * Delivery Boys Routes
             */
            Route::prefix('delivery-boys')->controller(DeliveryBoyController::class)->group(function () {
                Route::get('/', 'index');            // List delivery boys
                Route::post('/', 'store');           // Create delivery boy
                Route::get('{id}', 'show');          // Show delivery boy details
                Route::put('{id}', 'update');        // Update delivery boy
                Route::delete('{id}', 'destroy');    // Soft delete delivery boy
                Route::post('restore/{id}', 'restore'); // Restore delivery boy
            });

            /**
             * Retailers Routes
             */
            Route::prefix('retailers')->controller(RetailerController::class)->group(function () {
                Route::get('/', 'index');            // List retailers
                Route::post('/', 'store');           // Create retailer
                Route::get('{id}', 'show');          // Show retailer details
                Route::put('{id}', 'update');        // Update retailer
                Route::delete('{id}', 'destroy');    // Soft delete retailer
                Route::post('restore/{id}', 'restore'); // Restore retailer
            });
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
