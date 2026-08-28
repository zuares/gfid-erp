<?php

use App\Http\Controllers\Payroll\PayrollDashboardController;
use App\Http\Controllers\Payroll\PayrollReportController;
use App\Http\Controllers\Payroll\PieceRateController;
use App\Http\Controllers\Payroll\PieceworkPayrollController;

Route::middleware(['web', 'auth', 'access:payroll'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | PAYROLL DASHBOARD (borongan jahit + cutting)
    |--------------------------------------------------------------------------
     */
    Route::get('payroll/dashboard', [PayrollDashboardController::class, 'index'])
        ->name('payroll.dashboard');

    // API lazy-load per tab + filter AJAX
    Route::get('payroll/dashboard/data', [PayrollDashboardController::class, 'data'])
        ->name('payroll.dashboard.data');

    // Slip upah borongan per operator (siap cetak)
    Route::get('payroll/dashboard/slip', [PayrollDashboardController::class, 'slip'])
        ->name('payroll.dashboard.slip');

    /*
    |--------------------------------------------------------------------------
    | PIECEWORK PAYROLL (CUTTING & SEWING)
    |--------------------------------------------------------------------------
    | Overview gabungan + detail per module: cutting | sewing
    |--------------------------------------------------------------------------
     */
    Route::get('payroll/piecework', [PieceworkPayrollController::class, 'overview'])
        ->name('payroll.piecework.overview');

    Route::post('payroll/piecework', [PieceworkPayrollController::class, 'storeOverview'])
        ->name('payroll.piecework.store_overview');

    Route::prefix('payroll/piecework/{module}')
        ->whereIn('module', ['cutting', 'sewing'])
        ->name('payroll.piecework.')
        ->group(function () {

            Route::get('/', [PieceworkPayrollController::class, 'index'])
                ->name('index');

            Route::get('/create', [PieceworkPayrollController::class, 'create'])
                ->name('create');

            Route::post('/', [PieceworkPayrollController::class, 'store'])
                ->name('store');

            Route::get('/{period}', [PieceworkPayrollController::class, 'show'])
                ->name('show');

            Route::delete('/{period}', [PieceworkPayrollController::class, 'destroy'])
                ->name('destroy');

            // SLIP PER OPERATOR
            Route::get('/{period}/slip/{employee}', [PieceworkPayrollController::class, 'slip'])
                ->name('slip');

            // SLIP SEMUA OPERATOR (KHUSUS SEWING)
            Route::get('/{period}/slip-all', [PieceworkPayrollController::class, 'slipAll'])
                ->name('slip_all');

            // FINALIZE → Dr HPP / Cr Hutang
            Route::post('/{period}/finalize', [PieceworkPayrollController::class, 'finalize'])
                ->name('finalize');

            // PAY → Dr Hutang / Cr Kas-Bank
            Route::post('/{period}/pay', [PieceworkPayrollController::class, 'pay'])
                ->name('pay');

            // REGENERATE (draft only)
            Route::post('/{period}/regenerate', [PieceworkPayrollController::class, 'regenerate'])
                ->name('regenerate');
        });

    /*
    |--------------------------------------------------------------------------
    | PIECE RATES (MASTER TARIF BORONGAN)
    |--------------------------------------------------------------------------
     */
    Route::prefix('payroll/piece-rates')
        ->name('payroll.piece_rates.')
        ->group(function () {
            Route::get('/', [PieceRateController::class, 'index'])->name('index');
            Route::get('/create', [PieceRateController::class, 'create'])->name('create');
            Route::post('/', [PieceRateController::class, 'store'])->name('store');
            Route::get('/{pieceRate}/edit', [PieceRateController::class, 'edit'])->name('edit');
            Route::put('/{pieceRate}', [PieceRateController::class, 'update'])->name('update');
            Route::delete('/{pieceRate}', [PieceRateController::class, 'destroy'])->name('destroy');
        });

    /*
    |--------------------------------------------------------------------------
    | PAYROLL REPORTS
    |--------------------------------------------------------------------------
     */
    Route::prefix('payroll/reports')
        ->name('payroll.reports.')
        ->group(function () {
            Route::get('/operators', [PayrollReportController::class, 'operatorSummary'])
                ->name('operators');

            Route::get('/operator-slips', [PayrollReportController::class, 'operatorSlips'])
                ->name('operator_slips');

            Route::get('/operators/{employee}/detail',
                [PayrollReportController::class, 'operatorDetail']
            )->name('operator_detail');
        });
});
