<?php

use App\Http\Controllers\StandaloneAccounting\AccountController;
use App\Http\Controllers\StandaloneAccounting\JournalController;
use App\Http\Controllers\StandaloneAccounting\JournalMenuController;
use App\Http\Controllers\StandaloneAccounting\OpeningMigrationController;
use App\Http\Controllers\StandaloneAccounting\StockOpnameVarianceController;
use App\Http\Controllers\StandaloneAccounting\TrialBalanceController;
use App\Http\Middleware\EnsureStandaloneAccountingAccess;
use Illuminate\Http\Request;
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
        Route::get('opening-migrasi', [OpeningMigrationController::class, 'index'])->name('opening-migration.index');
        Route::post('opening-migrasi/preview', [OpeningMigrationController::class, 'preview'])->name('opening-migration.preview');
        Route::post('opening-migrasi/post', [OpeningMigrationController::class, 'store'])->name('opening-migration.store');
        Route::post('opening-migrasi/koreksi', [OpeningMigrationController::class, 'correct'])->name('opening-migration.correct');
        Route::get('menu-jurnal/{module}', [JournalMenuController::class, 'show'])
            ->where('module', 'pembelian|penjualan|penerimaan-kas|pengeluaran-kas|kas-kecil|kartu-persediaan|buku-besar-pembantu')
            ->name('journal-menu.show');

        Route::get('stock-opname-variance', [StockOpnameVarianceController::class, 'index'])
            ->name('stock-opname-variance.index');

        Route::get('akun', [AccountController::class, 'index'])
            ->name('accounts.index');
        Route::get('akun/{account}/buku-besar', [AccountController::class, 'ledger'])
            ->whereNumber('account')
            ->name('accounts.ledger');
        Route::get('akun/{account}', [AccountController::class, 'show'])
            ->whereNumber('account')
            ->name('accounts.show');
        Route::post('akun', [AccountController::class, 'store'])
            ->name('accounts.store');

        Route::get('jurnal', [JournalController::class, 'index'])
            ->name('journals.index');
        Route::get('jurnal/create', [JournalController::class, 'create'])
            ->name('journals.create');
        Route::get('jurnal/buat', function (Request $request) {
            return redirect()->route('standalone-accounting.journals.create', $request->query());
        })->name('journals.create-legacy');
        Route::get('jurnal/{journal}', [JournalController::class, 'show'])
            ->name('journals.show');
        Route::post('jurnal', [JournalController::class, 'store'])
            ->name('journals.store');
        Route::post('jurnal/{journal}/void', [JournalController::class, 'void'])
            ->name('journals.void');
    });
