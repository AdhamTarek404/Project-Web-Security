<?php

use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\WebAdminController;
use App\Http\Controllers\Web\WebAuthController;
use App\Http\Controllers\Web\WebOrderActionController;
use App\Http\Controllers\Web\WebOrderController;
use App\Http\Controllers\Web\WebRatingController;
use App\Http\Controllers\Web\WebRestaurantManageController;
use App\Http\Controllers\Web\WebRiderController;
use Illuminate\Support\Facades\Route;

// ============== Public ==============
Route::get('/',                            [HomeController::class, 'index'])->name('home');
Route::get('/restaurants/{restaurant:slug}', [HomeController::class, 'show'])->name('restaurants.show');

// ============== Auth (session) ==============
Route::get('/login',     [WebAuthController::class, 'showLogin'])->name('login');
Route::post('/login',    [WebAuthController::class, 'login']);
Route::get('/register',  [WebAuthController::class, 'showRegister'])->name('register');
Route::post('/register', [WebAuthController::class, 'register']);
Route::post('/logout',   [WebAuthController::class, 'logout'])->middleware('auth')->name('logout');

// ============== Authenticated browser pages ==============
Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Customer.
    Route::post('/restaurants/{restaurant}/order', [WebOrderController::class, 'place'])->name('orders.place');
    Route::get('/orders/{order}',                  [WebOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/cancel',          [WebOrderActionController::class, 'customerCancel'])->name('orders.cancel');
    Route::post('/orders/{order}/rate',            [WebRatingController::class, 'store'])->name('orders.rate');

    // Restaurant owner — order actions.
    Route::post('/owner/orders/{order}/confirm',         [WebOrderActionController::class, 'ownerConfirm'])->name('owner.orders.confirm');
    Route::post('/owner/orders/{order}/start-preparing', [WebOrderActionController::class, 'ownerStartPreparing'])->name('owner.orders.start');
    Route::post('/owner/orders/{order}/cancel',          [WebOrderActionController::class, 'ownerCancel'])->name('owner.orders.cancel');

    // Restaurant owner — restaurant management.
    Route::get('/owner/restaurants/create',                       [WebRestaurantManageController::class, 'create'])->name('owner.restaurants.create');
    Route::post('/owner/restaurants',                              [WebRestaurantManageController::class, 'store'])->name('owner.restaurants.store');
    Route::get('/owner/restaurants/{restaurant}/manage',           [WebRestaurantManageController::class, 'manage'])->name('owner.restaurants.manage');
    Route::patch('/owner/restaurants/{restaurant}',                [WebRestaurantManageController::class, 'update'])->name('owner.restaurants.update');
    Route::post('/owner/restaurants/{restaurant}/toggle-open',     [WebRestaurantManageController::class, 'toggleOpen'])->name('owner.restaurants.toggle-open');

    Route::post('/owner/restaurants/{restaurant}/categories',      [WebRestaurantManageController::class, 'storeCategory'])->name('owner.categories.store');
    Route::patch('/owner/categories/{category}',                   [WebRestaurantManageController::class, 'updateCategory'])->name('owner.categories.update');
    Route::delete('/owner/categories/{category}',                  [WebRestaurantManageController::class, 'destroyCategory'])->name('owner.categories.destroy');

    Route::post('/owner/categories/{category}/menu-items',         [WebRestaurantManageController::class, 'storeMenuItem'])->name('owner.menu-items.store');
    Route::patch('/owner/menu-items/{menuItem}',                   [WebRestaurantManageController::class, 'updateMenuItem'])->name('owner.menu-items.update');
    Route::post('/owner/menu-items/{menuItem}/toggle-availability',[WebRestaurantManageController::class, 'toggleAvailability'])->name('owner.menu-items.toggle');
    Route::delete('/owner/menu-items/{menuItem}',                  [WebRestaurantManageController::class, 'destroyMenuItem'])->name('owner.menu-items.destroy');

    Route::post('/owner/menu-items/{menuItem}/variants',           [WebRestaurantManageController::class, 'storeVariant'])->name('owner.variants.store');
    Route::post('/owner/variants/{variant}/make-default',          [WebRestaurantManageController::class, 'makeDefaultVariant'])->name('owner.variants.default');
    Route::delete('/owner/variants/{variant}',                     [WebRestaurantManageController::class, 'destroyVariant'])->name('owner.variants.destroy');

    // Rider actions.
    Route::post('/rider/duty',                        [WebRiderController::class, 'toggleDuty'])->name('rider.duty');
    Route::post('/rider/location',                    [WebRiderController::class, 'updateLocation'])->name('rider.location');
    Route::post('/rider/orders/{order}/picked-up',    [WebOrderActionController::class, 'riderPickedUp'])->name('rider.orders.picked-up');
    Route::post('/rider/orders/{order}/delivered',    [WebOrderActionController::class, 'riderDelivered'])->name('rider.orders.delivered');

    // Admin-only pages. The middleware enforces it at the route level.
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/',             [WebAdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/orders',       [WebAdminController::class, 'orders'])->name('orders');
        Route::get('/users',        [WebAdminController::class, 'users'])->name('users');
        Route::get('/restaurants',  [WebAdminController::class, 'restaurants'])->name('restaurants');
        Route::get('/riders',       [WebAdminController::class, 'riders'])->name('riders');
        Route::get('/surge',        [WebAdminController::class, 'surge'])->name('surge');
        Route::post('/restaurants/{restaurant}/toggle-open', [WebAdminController::class, 'toggleRestaurantOpen'])->name('restaurants.toggle');

        // Phase 10: live map (Livewire).
        Route::get('/control-tower', fn () => view('admin.control-tower'))->name('control-tower');
    });
});
