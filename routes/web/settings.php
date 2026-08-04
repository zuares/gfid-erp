<?php

use App\Http\Controllers\Settings\SystemSettingController;
use App\Http\Controllers\Settings\WhatsAppSettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Settings Routes
|--------------------------------------------------------------------------
| Hanya owner yang bisa mengubah pengaturan sistem.
|
*/

Route::middleware(['auth'])->prefix('settings')->name('settings.')->group(function () {

    Route::middleware(['role:owner'])->group(function () {
        Route::get('system',               [SystemSettingController::class, 'index'])->name('system.index');
        Route::post('system/cutoff',       [SystemSettingController::class, 'storeCutoff'])->name('system.cutoff.store');
        Route::post('system/cutoff/clear', [SystemSettingController::class, 'clearCutoff'])->name('system.cutoff.clear');
        Route::get('whatsapp',              [WhatsAppSettingController::class, 'index'])->name('whatsapp.index');
        Route::post('whatsapp/test',        [WhatsAppSettingController::class, 'sendTest'])->name('whatsapp.test');

    });

});
