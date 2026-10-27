<?php

use App\Http\Controllers\StandaloneAccounting\AccountController;
use App\Http\Controllers\StandaloneAccounting\JournalController;
use App\Http\Controllers\StandaloneAccounting\JournalMenuController;
use App\Http\Controllers\StandaloneAccounting\TrialBalanceController;
use App\Http\Middleware\EnsureStandaloneAccountingAccess;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', EnsureStandaloneAccountingAccess::class])
    ->prefix('akuntansi-baru')
    ->name('standalone-accounting.')
    ->group(function () {
        Route::get('/', fn () => to_route('standalone-accounting.trial-balance.index'))
            ->name('index');

        Route::get('neraca-saldo', [TrialBalanceController::class, 'index'])
            ->name('trial-balance.index');
        Route::get('neraca-saldo-periode-akhir', [TrialBalanceController::class, 'index'])
            ->name('trial-balance.period-end');

        Route::get('menu-jurnal', [JournalMenuController::class, 'index'])
            ->name('journal-menu.index');
        Route::get('menu-jurnal/{module}', [JournalMenuController::class, 'show'])
            ->where('module', 'pembelian|penjualan|penerimaan-kas|pengeluaran-kas|kas-kecil|kartu-persediaan|buku-besar-pembantu')
            ->name('journal-menu.show');

        Route::get('akun', [AccountController::class, 'index'])
            ->name('accounts.index');
        Route::post('akun', [AccountController::class, 'store'])
            ->name('accounts.store');

        Route::get('jurnal', [JournalController::class, 'index'])
            ->name('journals.index');
        Route::get('jurnal/buat', [JournalController::class, 'create'])
            ->name('journals.create');
        Route::post('jurnal', [JournalController::class, 'store'])
            ->name('journals.store');
    });
