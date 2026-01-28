<?php

use App\Http\Controllers\Accounting\AccountController;
use App\Http\Controllers\Accounting\CashExpenseController;
use App\Http\Controllers\Accounting\JournalController;
use App\Http\Controllers\Accounting\OpeningBalanceBatchController;
use App\Http\Controllers\Accounting\OpeningBalanceController;
use App\Http\Controllers\Api\AccountSuggestController;

Route::middleware(['auth'])->prefix('accounting')->name('accounting.')->group(function () {

    // ✅ Ledger per Account (HARUS sebelum resource accounts)
    Route::get('accounts/{account}/ledger', [AccountController::class, 'ledger'])
        ->name('accounts.ledger');

    // ✅ COA (Master Accounts)
    Route::resource('accounts', AccountController::class);

    // ✅ Cash Expenses
    Route::resource('cash-expenses', CashExpenseController::class);
    Route::post('cash-expenses/{cashExpense}/post', [CashExpenseController::class, 'post'])->name('cash-expenses.post');
    Route::post('cash-expenses/{cashExpense}/void', [CashExpenseController::class, 'void'])->name('cash-expenses.void');

    // ✅ Journals (read-only)
    Route::get('journals', [JournalController::class, 'index'])->name('journals.index');
    Route::get('journals/{journal}', [JournalController::class, 'show'])->name('journals.show');

    // ✅ Opening Balances
    Route::get('opening-balances', [OpeningBalanceController::class, 'index'])->name('opening-balances.index');
    Route::get('opening-balances/create', [OpeningBalanceController::class, 'create'])->name('opening-balances.create');
    Route::post('opening-balances', [OpeningBalanceController::class, 'store'])->name('opening-balances.store');
    Route::post('opening-balances/{journal}/void', [OpeningBalanceController::class, 'void'])->name('opening-balances.void');
});

// routes/web.php
Route::prefix('accounting/opening-balances-batch')->name('accounting.opening-balances-batch.')->group(function () {
    Route::get('/', [OpeningBalanceBatchController::class, 'index'])->name('index');
    Route::get('/create', [OpeningBalanceBatchController::class, 'create'])->name('create');
    Route::post('/', [OpeningBalanceBatchController::class, 'store'])->name('store');
    Route::post('/{journal}/void', [OpeningBalanceBatchController::class, 'void'])->name('void');
});

Route::get('v1/accounts/suggest', AccountSuggestController::class);
