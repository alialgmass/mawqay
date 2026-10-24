<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Middleware\EnsureCustomerBelongsToStore;
use Modules\Storefront\Http\Controllers\CategoryController;
use Modules\Storefront\Http\Controllers\HomeController;
use Modules\Storefront\Http\Controllers\ProductController;
use Modules\Storefront\Http\Controllers\SearchController;
use Modules\Storefront\Http\Middleware\UseArabicLocale;
use Modules\Tenancy\Http\Middleware\EnsureStoreIsPublic;
use Modules\Tenancy\Http\Middleware\IdentifyStore;
use Modules\Tenancy\Support\StoreRoutes;

Route::middleware(['web', UseArabicLocale::class, IdentifyStore::class, EnsureStoreIsPublic::class, EnsureCustomerBelongsToStore::class])
    ->prefix('{store}')
    ->where(['store' => StoreRoutes::slugPattern()])
    ->group(function () {
        Route::get('/', HomeController::class)->name('storefront.home');
        Route::get('/categories/{category}', CategoryController::class)->name('storefront.category');
        Route::get('/products/{product}', ProductController::class)->name('storefront.product');
        Route::get('/search', SearchController::class)->name('storefront.search');
    });
