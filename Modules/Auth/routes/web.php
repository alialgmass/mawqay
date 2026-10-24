<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\CustomerAuthController;
use Modules\Auth\Http\Controllers\InvitationController;
use Modules\Auth\Http\Middleware\EnsureCustomerBelongsToStore;
use Modules\Storefront\Http\Middleware\UseArabicLocale;
use Modules\Tenancy\Http\Middleware\EnsureStoreIsPublic;
use Modules\Tenancy\Http\Middleware\IdentifyStore;
use Modules\Tenancy\Support\StoreRoutes;

Route::middleware(['web', UseArabicLocale::class, IdentifyStore::class, EnsureStoreIsPublic::class, EnsureCustomerBelongsToStore::class])
    ->prefix('{store}/customer')
    ->where(['store' => StoreRoutes::slugPattern()])
    ->group(function () {
        Route::post('register', [CustomerAuthController::class, 'register'])
            ->middleware('throttle:customer-auth')
            ->name('customer.register');

        Route::post('login', [CustomerAuthController::class, 'login'])
            ->middleware('throttle:customer-auth')
            ->name('customer.login');

        Route::post('logout', [CustomerAuthController::class, 'logout'])
            ->name('customer.logout');
    });

Route::middleware(['web', UseArabicLocale::class])
    ->prefix('invitations')
    ->group(function () {
        Route::get('{token}', [InvitationController::class, 'show'])->name('invitations.show');
        Route::post('{token}', [InvitationController::class, 'accept'])
            ->middleware('throttle:10,1')
            ->name('invitations.accept');
    });
