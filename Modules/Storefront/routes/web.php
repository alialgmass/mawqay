<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Middleware\EnsureCustomerBelongsToStore;
use Modules\Storefront\Http\Controllers\HomeController;
use Modules\Storefront\Http\Middleware\UseArabicLocale;
use Modules\Tenancy\Http\Middleware\IdentifyStore;
use Modules\Tenancy\Support\StoreRoutes;

Route::middleware(['web', UseArabicLocale::class, IdentifyStore::class, EnsureCustomerBelongsToStore::class])
    ->prefix('{store}')
    ->where(['store' => StoreRoutes::slugPattern()])
    ->group(function () {
        Route::get('/', HomeController::class)->name('storefront.home');
    });
