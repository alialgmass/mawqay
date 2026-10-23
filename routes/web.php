<?php

use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::post('locale/switch', [LocaleController::class, 'switch'])
    ->middleware('web')
    ->name('locale.switch');
