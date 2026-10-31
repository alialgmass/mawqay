<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StoreRegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('home');

Route::get('blog', [LandingController::class, 'blog'])->name('blog.index');
Route::get('blog/{slug}', [LandingController::class, 'blogShow'])
    ->where('slug', '[a-z0-9-]+')
    ->name('blog.show');

Route::post('register/store', [StoreRegistrationController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('register.store');

Route::get('sitemap.xml', SitemapController::class)->name('sitemap');

Route::post('locale/switch', [LocaleController::class, 'switch'])
    ->middleware('web')
    ->name('locale.switch');
