<?php

use App\Http\Controllers\WhatsAppCenterController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:owner,admin'])
    ->prefix('whatsapp')
    ->name('whatsapp.')
    ->group(function () {
        Route::get('/', [WhatsAppCenterController::class, 'index'])->name('index');
        Route::get('/messages/purchase-order/{purchase_order}', [WhatsAppCenterController::class, 'composePurchaseOrder'])
            ->name('messages.compose.purchase_order');
        Route::post('/messages', [WhatsAppCenterController::class, 'send'])->name('messages.send');
        Route::post('/messages/{whatsapp_message}/resend', [WhatsAppCenterController::class, 'resend'])
            ->name('messages.resend');
        Route::post('/templates', [WhatsAppCenterController::class, 'storeTemplate'])->name('templates.store');
        Route::put('/templates/{whatsapp_template}', [WhatsAppCenterController::class, 'updateTemplate'])
            ->name('templates.update');
    });
