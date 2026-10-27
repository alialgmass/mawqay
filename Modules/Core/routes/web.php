<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\HealthController;

Route::get('/health', HealthController::class)->name('health');
