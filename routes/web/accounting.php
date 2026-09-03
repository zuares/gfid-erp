<?php

use App\Http\Controllers\Accounting\AccountController;
use App\Http\Controllers\Accounting\ApReportController;
use App\Http\Controllers\Accounting\CashBasisReportController;
use App\Http\Controllers\Accounting\CashExpenseController;
use App\Http\Controllers\Accounting\CashReceiptController;
use App\Http\Controllers\Accounting\CashTransferController;
use App\Http\Controllers\Accounting\JournalController;
use App\Http\Controllers\Accounting\MarketplacePayoutController;
use App\Http\Controllers\Accounting\OpeningBalanceBatchController;
use App\Http\Controllers\Accounting\OpeningBalanceController;
use App\Http\Controllers\Accounting\ProductionJournalAuditController;
use App\Http\Controllers\Accounting\ProductionValueReportController;
use App\Http\Controllers\Accounting\ProfitLossController;
use App\Http\Controllers\Accounting\TrialBalanceController;
use App\Http\Controllers\Api\AccountSuggestController;

Route::middleware(['auth', 'access:accounting'])->prefix('accounting')->name('accounting.')->group(function () {
    Route::get('/', fn () => redirect()->route('accounting.cash-basis-report.index'))->name('index');
    Route::get('cash-basis-report', [CashBasisReportController::class, 'index'])->name('cash-basis-report.index');

    // ✅ Laporan Keuangan
    Route::get('ap-report',      [ApReportController::class,      'index'])->name('ap-report.index');
    Route::get('trial-balance',  [TrialBalanceController::class,  'index'])->name('trial-balance.index');
    Route::get('profit-loss',    [ProfitLossController::class,    'index'])->name('profit-loss.index');
    Route::get('production-journal-audit', [ProductionJournalAuditController::class, 'index'])
        ->name('production-journal-audit.index');
    Route::get('production-value-report', [ProductionValueReportController::class, 'index'])
        ->name('production-value-report.index');

    // ✅ Buku Besar & Ledger per Account (HARUS sebelum resource accounts)
    Route::get('buku-besar', [AccountController::class, 'bukuBesar'])->name('buku-besar.index');
    Route::get('accounts/{account}/ledger', [AccountController::class, 'ledger'])
        ->name('accounts.ledger');

    // ✅ COA (Master Accounts)
    Route::resource('accounts', AccountController::class);

    // ✅ Cash Receipts
    Route::resource('cash-receipts', CashReceiptController::class);
    Route::post('cash-receipts/{cashReceipt}/post', [CashReceiptController::class, 'post'])->name('cash-receipts.post');
    Route::post('cash-receipts/{cashReceipt}/void', [CashReceiptController::class, 'void'])->name('cash-receipts.void');

    // ✅ Cash/Bank Transfers
    Route::resource('cash-transfers', CashTransferController::class);
    Route::post('cash-transfers/{cashTransfer}/post', [CashTransferController::class, 'post'])->name('cash-transfers.post');
    Route::post('cash-transfers/{cashTransfer}/void', [CashTransferController::class, 'void'])->name('cash-transfers.void');

    // ✅ Journals (read-only)
    Route::get('journals', [JournalController::class, 'index'])->name('journals.index');
    Route::get('journals/{journal}', [JournalController::class, 'show'])->name('journals.show');

    // ✅ Marketplace Payouts
    Route::post('marketplace-payouts/bulk-post', [MarketplacePayoutController::class, 'bulkPost'])
        ->name('marketplace-payouts.bulk-post');
    Route::resource('marketplace-payouts', MarketplacePayoutController::class);
    Route::post('marketplace-payouts/import/shopee', [MarketplacePayoutController::class, 'importShopee'])
        ->name('marketplace-payouts.import-shopee');
    Route::post('marketplace-payouts/{marketplacePayout}/post', [MarketplacePayoutController::class, 'post'])->name('marketplace-payouts.post');
    Route::post('marketplace-payouts/{marketplacePayout}/void', [MarketplacePayoutController::class, 'void'])->name('marketplace-payouts.void');

    // ✅ Opening Balances
    Route::get('opening-balances', [OpeningBalanceController::class, 'index'])->name('opening-balances.index');
    Route::get('opening-balances/create', [OpeningBalanceController::class, 'create'])->name('opening-balances.create');
    Route::post('opening-balances', [OpeningBalanceController::class, 'store'])->name('opening-balances.store');
    Route::post('opening-balances/{journal}/void', [OpeningBalanceController::class, 'void'])->name('opening-balances.void');
});

// ✅ Cash Expenses — akses khusus untuk owner, admin, operating, dan user Accounting.
Route::middleware(['auth', 'access:cash-expenses'])->prefix('accounting')->name('accounting.')->group(function () {
    Route::get('cash-expenses/{cashExpense}/proof', [CashExpenseController::class, 'proof'])
        ->name('cash-expenses.proof');
    Route::post('cash-expenses/categories', [CashExpenseController::class, 'storeCategory'])
        ->name('cash-expenses.categories.store');
    Route::resource('cash-expenses', CashExpenseController::class);
    Route::post('cash-expenses/{cashExpense}/post', [CashExpenseController::class, 'post'])->name('cash-expenses.post');
    Route::post('cash-expenses/{cashExpense}/void', [CashExpenseController::class, 'void'])->name('cash-expenses.void');
    Route::post('cash-expenses/{cashExpense}/reclassify', [CashExpenseController::class, 'reclassify'])->name('cash-expenses.reclassify');
});

// routes/web.php
Route::middleware(['auth', 'access:accounting'])->prefix('accounting/opening-balances-batch')->name('accounting.opening-balances-batch.')->group(function () {
    Route::get('/', [OpeningBalanceBatchController::class, 'index'])->name('index');
    Route::get('/create', [OpeningBalanceBatchController::class, 'create'])->name('create');
    Route::get('/detail/{code}', [OpeningBalanceBatchController::class, 'detail'])->name('detail');
    Route::post('/', [OpeningBalanceBatchController::class, 'store'])->name('store');
    Route::post('/{journal}/void', [OpeningBalanceBatchController::class, 'void'])->name('void');
});

Route::middleware(['auth', 'access:accounting'])->get('v1/accounts/suggest', AccountSuggestController::class);
