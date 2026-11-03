<?php

use App\Http\Controllers\Developer\DeveloperLedgerResetController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::post('/developer/reset-ledgers', [DeveloperLedgerResetController::class, 'store'])
        ->name('developer.reset-ledgers');
});
