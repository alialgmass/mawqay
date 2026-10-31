<?php

use Illuminate\Support\Facades\Route;
use Modules\Payments\Http\Controllers\FawaterkWebhookController;
use Modules\Payments\Http\Controllers\ReceiptController;

Route::middleware(['web', 'signed'])
    ->get('/payment-receipts/{store}/{payment}', ReceiptController::class)
    ->whereNumber(['store', 'payment'])
    ->name('payments.receipt');

// Fawaterk webhooks: server-to-server, so no session or CSRF; authenticated by their HMAC signature.
Route::middleware(['api', 'throttle:120,1'])->group(function () {
    Route::post('/webhooks/fawaterk_json', [FawaterkWebhookController::class, 'paid'])->name('payments.webhooks.fawaterk.paid');
    Route::post('/webhooks/fawaterk/failed_json', [FawaterkWebhookController::class, 'failed'])->name('payments.webhooks.fawaterk.failed');
});
