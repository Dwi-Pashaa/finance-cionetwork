<?php

use App\Http\Controllers\Api\V1\BalanceController;
use App\Http\Controllers\Api\V1\FinanceHistoryController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\PingController;
use App\Http\Controllers\Api\V1\XenditWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', [HealthController::class, 'index'])->name('api.v1.health');

    // Xendit Webhook Callback
    Route::post('/xendit/webhook/invoice', [XenditWebhookController::class, 'handleInvoice'])->name('api.v1.xendit.webhook.invoice');

    Route::middleware(['request.id', 'hmac.auth', 'throttle:60,1'])->group(function () {
        Route::get('/ping', [PingController::class, 'index'])->name('api.v1.ping');
        Route::get('/balance', [BalanceController::class, 'index'])->name('api.v1.balance');
        Route::post('/balance/deduct', [BalanceController::class, 'deduct'])->name('api.v1.balance.deduct');
        Route::post('/balance/refund', [BalanceController::class, 'refund'])->name('api.v1.balance.refund');
        Route::get('/history', [FinanceHistoryController::class, 'index'])->name('api.v1.history.index');
        Route::post('/history', [FinanceHistoryController::class, 'store'])->name('api.v1.history.store');
    });
});
