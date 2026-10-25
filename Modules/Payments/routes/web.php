<?php

use Illuminate\Support\Facades\Route;
use Modules\Payments\Http\Controllers\ReceiptController;

Route::middleware(['web', 'signed'])
    ->get('/payment-receipts/{store}/{payment}', ReceiptController::class)
    ->whereNumber(['store', 'payment'])
    ->name('payments.receipt');
