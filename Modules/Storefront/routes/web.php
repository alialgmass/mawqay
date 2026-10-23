<?php

use Illuminate\Support\Facades\Route;
use Modules\Storefront\Http\Controllers\HomeController;
use Modules\Storefront\Http\Middleware\UseArabicLocale;
use Modules\Tenancy\Http\Middleware\IdentifyStore;

$reserved = implode('|', array_map('preg_quote', config('tenancy.reserved_slugs', [])));

Route::middleware(['web', UseArabicLocale::class, IdentifyStore::class])
    ->prefix('{store}')
    ->where(['store' => "^(?!(?:{$reserved})$).+"])
    ->group(function () {
        Route::get('/', HomeController::class)->name('storefront.home');
    });
