<?php

use Illuminate\Support\Facades\Route;
use Modules\Storefront\Http\Controllers\HomeController;
use Modules\Storefront\Http\Middleware\UseArabicLocale;
use Modules\Tenancy\Http\Middleware\IdentifyStore;

Route::middleware(['web', UseArabicLocale::class, IdentifyStore::class])
    ->prefix('{store}')
    ->group(function () {
        Route::get('/', HomeController::class)->name('storefront.home');
    });
