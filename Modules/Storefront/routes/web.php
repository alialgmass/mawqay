<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Middleware\EnsureCustomerBelongsToStore;
use Modules\Auth\Http\Middleware\EnsureCustomerIsAuthenticated;
use Modules\Auth\Http\Middleware\EnsureCustomerSessionIsValid;
use Modules\Core\Http\Middleware\UseArabicLocale;
use Modules\Storefront\Http\Controllers\CartController;
use Modules\Storefront\Http\Controllers\CategoryController;
use Modules\Storefront\Http\Controllers\CheckoutController;
use Modules\Storefront\Http\Controllers\CustomerPageController;
use Modules\Storefront\Http\Controllers\HomeController;
use Modules\Storefront\Http\Controllers\NotFoundController;
use Modules\Storefront\Http\Controllers\OrderController;
use Modules\Storefront\Http\Controllers\PaymentController;
use Modules\Storefront\Http\Controllers\ProductController;
use Modules\Storefront\Http\Controllers\SearchController;
use Modules\Storefront\Http\Controllers\SurveyController;
use Modules\Tenancy\Http\Middleware\EnsureStoreIsPublic;
use Modules\Tenancy\Http\Middleware\IdentifyStore;
use Modules\Tenancy\Support\StoreRoutes;

Route::middleware(['web', UseArabicLocale::class, IdentifyStore::class, EnsureStoreIsPublic::class, EnsureCustomerBelongsToStore::class, EnsureCustomerSessionIsValid::class])
    ->prefix('{store}')
    ->where(['store' => StoreRoutes::slugPattern()])
    ->group(function () {
        Route::get('/', HomeController::class)->name('storefront.home');
        Route::get('/categories/{category}', CategoryController::class)->name('storefront.category');
        Route::get('/products/{product}', ProductController::class)->name('storefront.product');
        Route::get('/search', SearchController::class)->name('storefront.search');
        Route::get('/cart', [CartController::class, 'show'])->name('storefront.cart');
        Route::post('/cart/items', [CartController::class, 'store'])->name('storefront.cart.add');
        Route::put('/cart/items/{item}', [CartController::class, 'update'])->whereNumber('item')->name('storefront.cart.update');
        Route::delete('/cart/items/{item}', [CartController::class, 'destroy'])->whereNumber('item')->name('storefront.cart.remove');
        Route::post('/cart/accept-prices', [CartController::class, 'acceptPrices'])->name('storefront.cart.accept-prices');
        Route::get('/login', [CustomerPageController::class, 'login'])->name('storefront.login');
        Route::get('/register', [CustomerPageController::class, 'register'])->name('storefront.register');
        Route::get('/forgot-password', [CustomerPageController::class, 'forgotPassword'])->name('storefront.forgot-password');
        Route::get('/survey/{token}', [SurveyController::class, 'show'])->middleware('throttle:20,1')->name('storefront.survey');
        Route::post('/survey/{token}', [SurveyController::class, 'submit'])->middleware('throttle:20,1')->name('storefront.survey.submit');
        Route::get('/reset-password/{token}', [CustomerPageController::class, 'resetPassword'])->name('storefront.reset-password');
        Route::middleware(EnsureCustomerIsAuthenticated::class)->group(function () {
            Route::get('/checkout', [CheckoutController::class, 'show'])->name('storefront.checkout');
            Route::post('/checkout', [CheckoutController::class, 'place'])->name('storefront.checkout.place');
            Route::get('/orders', [OrderController::class, 'index'])->name('storefront.orders');
            Route::get('/orders/{number}', [OrderController::class, 'show'])->name('storefront.order');
            Route::get('/orders/{number}/confirmation', [OrderController::class, 'confirmation'])->name('storefront.order.confirmation');
            Route::post('/orders/{number}/receipt', [PaymentController::class, 'store'])->name('storefront.order.receipt');
            Route::post('/orders/{number}/received', [OrderController::class, 'received'])->name('storefront.order.received');
            Route::post('/orders/{number}/cancel', [OrderController::class, 'cancel'])->name('storefront.order.cancel');
            Route::get('/account', [CustomerPageController::class, 'account'])->name('storefront.account');
            Route::get('/account/addresses', [CustomerPageController::class, 'addresses'])->name('storefront.addresses');
        });
        Route::get('/{path}', NotFoundController::class)->where('path', '.*')->name('storefront.not-found');
    });
