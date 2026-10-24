<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\CustomerAddressController;
use Modules\Auth\Http\Controllers\CustomerAuthController;
use Modules\Auth\Http\Controllers\CustomerEmailVerificationController;
use Modules\Auth\Http\Controllers\CustomerPasswordController;
use Modules\Auth\Http\Controllers\CustomerProfileController;
use Modules\Auth\Http\Controllers\InvitationController;
use Modules\Auth\Http\Middleware\EnsureCustomerBelongsToStore;
use Modules\Auth\Http\Middleware\EnsureCustomerIsAuthenticated;
use Modules\Auth\Http\Middleware\EnsureCustomerSessionIsValid;
use Modules\Storefront\Http\Middleware\UseArabicLocale;
use Modules\Tenancy\Http\Middleware\EnsureStoreIsPublic;
use Modules\Tenancy\Http\Middleware\IdentifyStore;
use Modules\Tenancy\Support\StoreRoutes;

Route::middleware(['web', UseArabicLocale::class, IdentifyStore::class, EnsureStoreIsPublic::class, EnsureCustomerBelongsToStore::class, EnsureCustomerSessionIsValid::class])
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

        Route::post('forgot-password', [CustomerPasswordController::class, 'forgot'])
            ->middleware('throttle:customer-auth')
            ->name('customer.forgot-password');

        Route::post('reset-password', [CustomerPasswordController::class, 'reset'])
            ->middleware('throttle:customer-auth')
            ->name('customer.reset-password');

        Route::get('verify-email/{customer}/{hash}', [CustomerEmailVerificationController::class, 'verify'])
            ->middleware('signed')
            ->whereNumber('customer')
            ->name('customer.verify-email');

        Route::post('verify-email/resend', [CustomerEmailVerificationController::class, 'resend'])
            ->middleware('throttle:customer-auth')
            ->name('customer.verify-email.resend');

        Route::middleware(EnsureCustomerIsAuthenticated::class)->group(function () {
            Route::put('profile', [CustomerProfileController::class, 'update'])->name('customer.profile.update');
            Route::put('password', [CustomerProfileController::class, 'password'])->name('customer.password.update');

            Route::post('addresses', [CustomerAddressController::class, 'store'])->name('customer.addresses.store');
            Route::put('addresses/{address}', [CustomerAddressController::class, 'update'])->whereNumber('address')->name('customer.addresses.update');
            Route::delete('addresses/{address}', [CustomerAddressController::class, 'destroy'])->whereNumber('address')->name('customer.addresses.destroy');
            Route::post('addresses/{address}/default', [CustomerAddressController::class, 'makeDefault'])->whereNumber('address')->name('customer.addresses.default');
        });
    });

Route::middleware(['web', UseArabicLocale::class])
    ->prefix('invitations')
    ->group(function () {
        Route::get('{token}', [InvitationController::class, 'show'])->name('invitations.show');
        Route::post('{token}', [InvitationController::class, 'accept'])
            ->middleware('throttle:10,1')
            ->name('invitations.accept');
    });
