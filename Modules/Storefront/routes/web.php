<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Middleware\EnsureCustomerBelongsToStore;
use Modules\Auth\Http\Middleware\EnsureCustomerIsAuthenticated;
use Modules\Auth\Http\Middleware\EnsureCustomerSessionIsValid;
use Modules\Core\Http\Middleware\UseArabicLocale;
use Modules\Storefront\Http\Controllers\CategoryController;
use Modules\Storefront\Http\Controllers\CustomerPageController;
use Modules\Storefront\Http\Controllers\HomeController;
use Modules\Storefront\Http\Controllers\NotFoundController;
use Modules\Storefront\Http\Controllers\ProductController;
use Modules\Storefront\Http\Controllers\SearchController;
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
        Route::get('/login', [CustomerPageController::class, 'login'])->name('storefront.login');
        Route::get('/register', [CustomerPageController::class, 'register'])->name('storefront.register');
        Route::get('/forgot-password', [CustomerPageController::class, 'forgotPassword'])->name('storefront.forgot-password');
        Route::get('/reset-password/{token}', [CustomerPageController::class, 'resetPassword'])->name('storefront.reset-password');
        Route::middleware(EnsureCustomerIsAuthenticated::class)->group(function () {
            Route::get('/account', [CustomerPageController::class, 'account'])->name('storefront.account');
            Route::get('/account/addresses', [CustomerPageController::class, 'addresses'])->name('storefront.addresses');
        });
        Route::get('/{path}', NotFoundController::class)->where('path', '.*')->name('storefront.not-found');
    });
