<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Owner\CategoryController;
use App\Http\Controllers\Owner\MenuItemController;
use App\Http\Controllers\Owner\MenuItemVariantController;
use App\Http\Controllers\Owner\OrderController as OwnerOrderController;
use App\Http\Controllers\Owner\RestaurantController as OwnerRestaurantController;
use App\Http\Controllers\PublicRestaurantController;
use Illuminate\Support\Facades\Route;

// ---------- Public auth endpoints ----------
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// ---------- Public menu browsing (no auth) ----------
Route::get('/restaurants',               [PublicRestaurantController::class, 'index']);
Route::get('/restaurants/{slug}/menu',   [PublicRestaurantController::class, 'menu']);

// ---------- Authenticated endpoints (any role) ----------
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me',      [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

// ---------- Customer-only ----------
Route::middleware(['auth:sanctum', 'role:customer'])
    ->prefix('customer')
    ->group(function () {
        Route::get('/ping', fn () => ['role' => 'customer', 'ok' => true]);

        // Phase 5: ordering flow.
        Route::post('/orders',                  [CustomerOrderController::class, 'place']);
        Route::get('/orders',                   [CustomerOrderController::class, 'index']);
        Route::get('/orders/{order}',           [CustomerOrderController::class, 'show']);
        Route::post('/orders/{order}/cancel',   [CustomerOrderController::class, 'cancel']);

        // Phase 9: ratings (polymorphic — restaurant OR rider).
        Route::post('/orders/{order}/rate', [\App\Http\Controllers\Customer\RatingController::class, 'store']);
    });

// ---------- Rider-only ----------
Route::middleware(['auth:sanctum', 'role:rider'])
    ->prefix('rider')
    ->group(function () {
        Route::get('/ping', fn () => ['role' => 'rider', 'ok' => true]);

        // Phase 6: dispatch + GPS.
        Route::get('/me',                           [\App\Http\Controllers\Rider\RiderController::class, 'me']);
        Route::post('/location',                    [\App\Http\Controllers\Rider\RiderController::class, 'updateLocation']);
        Route::post('/duty',                        [\App\Http\Controllers\Rider\RiderController::class, 'toggleDuty']);
        Route::post('/orders/{order}/picked-up',    [\App\Http\Controllers\Rider\RiderController::class, 'pickedUp']);
        Route::post('/orders/{order}/delivered',    [\App\Http\Controllers\Rider\RiderController::class, 'delivered']);
    });

// ---------- Restaurant owner / admin ----------
Route::middleware(['auth:sanctum', 'role:restaurant_owner,admin'])
    ->prefix('owner')
    ->group(function () {
        // Restaurant management
        Route::get('/restaurants',                [OwnerRestaurantController::class, 'index']);
        Route::post('/restaurants',               [OwnerRestaurantController::class, 'store']);
        Route::get('/restaurants/{restaurant}',   [OwnerRestaurantController::class, 'show']);
        Route::patch('/restaurants/{restaurant}', [OwnerRestaurantController::class, 'update']);

        // Categories
        Route::post('/categories',              [CategoryController::class, 'store']);
        Route::patch('/categories/{category}',  [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

        // Menu items
        Route::post('/menu-items',                                [MenuItemController::class, 'store']);
        Route::patch('/menu-items/{menuItem}',                    [MenuItemController::class, 'update']);
        Route::patch('/menu-items/{menuItem}/availability',       [MenuItemController::class, 'toggleAvailability']);
        Route::delete('/menu-items/{menuItem}',                   [MenuItemController::class, 'destroy']);

        // Variants
        Route::post('/variants',                  [MenuItemVariantController::class, 'store']);
        Route::patch('/variants/{variant}',       [MenuItemVariantController::class, 'update']);
        Route::delete('/variants/{variant}',      [MenuItemVariantController::class, 'destroy']);

        // Phase 5: order state transitions from the restaurant's side.
        Route::get('/orders',                          [OwnerOrderController::class, 'index']);
        Route::post('/orders/{order}/confirm',         [OwnerOrderController::class, 'confirm']);
        Route::post('/orders/{order}/start-preparing', [OwnerOrderController::class, 'startPreparing']);
        Route::post('/orders/{order}/cancel',          [OwnerOrderController::class, 'cancel']);
    });
