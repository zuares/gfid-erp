<?php

namespace App\Services\Payroll;

use App\Models\Account;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanRepayment;
use App\Models\EmployeeSavingsTransaction;
use App\Models\Journal;
use App\Models\PieceworkPayrollPeriod;
use App\Services\Accounting\JournalService;
use App\Services\Payroll\DailyAttendanceBonusCalculator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PieceworkPayrollPostingService
{
    public function __construct(
        protected JournalService $journalService
    ) {}

    /**
     * FINALIZE merekonsiliasi upah yang sudah dikapitalisasi oleh produksi.
     * Hanya selisih yang diposting agar upah tidak masuk HPP dua kali.
     */
    public function finalize(PieceworkPayrollPeriod $period): PieceworkPayrollPeriod
    {
        return DB::transaction(function () use ($period) {
            // Lock header payroll agar finalize/pay yang bersamaan tidak membaca
            // status lama lalu membuat jurnal atau marker ganda.
            $period = PieceworkPayrollPeriod::query()
                ->lockForUpdate()
                ->findOrFail($period->getKey());

            $payable = Account::where('code', '2102')->first();
            if (! $payable) {
                throw new \RuntimeException('Akun 2102 (Hutang Upah Borongan) tidak ditemukan.');
            }

            $activeAccrual = Journal::query()
                ->where('source_type', 'piecework_payroll_period_accrual')
                ->where('source_id', $period->id)
                ->whereNull('voided_at')
                ->latest('id')
                ->first();

            if ($period->status === 'final') {
                // Repair marker untuk periode lama yang sudah final sebelum field
                // accounting masuk ke $fillable.
                $updates = [];
                if (! $period->payable_account_id) {
                    $updates['payable_account_id'] = $payable->id;
                }
                if (! $period->finalized_at) {
                    $updates['finalized_at'] = now();
                }
                if (! $period->accrual_journal_id && $activeAccrual) {
                    $updates['accrual_journal_id'] = $activeAccrual->id;
                }

                if ($updates) {
                    $period->forceFill($updates)->save();
                }

                return $period->fresh();
            }

            // Hitung total termasuk bonus kehadiran harian yang eligible.
            $attendanceBonuses = DailyAttendanceBonusCalculator::forPeriod($period);
            $bonusTotal = (float) $attendanceBonuses->sum('bonus_amount');
            $total = round((float) $period->lines()->sum('amount') + $bonusTotal, 2);
            if ($total <= 0) {
                throw new \RuntimeException('Total payroll 0. Tidak bisa finalize.');
            }

            // anti dobel journal
            if ($period->accrual_journal_id) {
                $existing = Journal::find($period->accrual_journal_id);
                if ($existing && ! $existing->voided_at) {
                    throw new \RuntimeException('Sudah ada jurnal accrual aktif untuk periode ini.');
                }
            }

            $sourceTypes = match ((string) $period->module) {
                'cutting' => [JournalService::SRC_CUTTING_JOB_WAGE, JournalService::SRC_CUTTING_WIP],
                'sewing' => [JournalService::SRC_SEWING_PICKUP_WAGE, JournalService::SRC_SEWING_RETURN_OK, JournalService::SRC_SEWING_REWORK_OK],
                default => [],
            };

            $alreadyAccrued = empty($sourceTypes) ? 0.0 : (float) DB::table('journal_lines as jl')
                ->join('journals as j', 'j.id', '=', 'jl.journal_id')
                ->whereNull('j.voided_at')
                ->whereIn('j.source_type', $sourceTypes)
                ->whereBetween('j.date', [$period->period_start, $period->period_end])
                ->where('jl.account_id', $payable->id)
                ->sum('jl.credit');

            $difference = round($total - $alreadyAccrued, 2);
            $journal = null;

            if (abs($difference) > 0.01) {
                $debitAccount = $period->module === 'daily'
                    ? Account::where('code', JournalService::CODE_EXP_DAILY_PAYROLL)
                        ->where('type', 'expense')
                        ->where('is_active', true)
                        ->firstOrFail()
                    : Account::where('code', $period->module === 'finishing' ? '1203' : '1202')->firstOrFail();
                $payrollLabel = $period->module === 'daily' ? 'Payroll Harian' : 'Payroll Borongan';
                $desc = strtoupper($period->module).' '.$payrollLabel.' (REKONSILIASI) '
                    .$period->period_start.' s/d '.$period->period_end;

                $lines = $difference > 0
                    ? [
                        ['account_id' => $debitAccount->id, 'debit' => $difference, 'credit' => 0],
                        ['account_id' => $payable->id, 'debit' => 0, 'credit' => $difference],
                    ]
                    : [
                        ['account_id' => $payable->id, 'debit' => abs($difference), 'credit' => 0],
                        ['account_id' => $debitAccount->id, 'debit' => 0, 'credit' => abs($difference)],
                    ];

                $journal = $this->journalService->post(
                    date: $period->period_end,
                    sourceType: 'piecework_payroll_period_accrual',
                    sourceId: $period->id,
                    description: $desc,
                    lines: $lines,
                );
            }

            $period->forceFill([
                'total_amount' => $total,
                'status' => 'final',
                'finalized_at' => now(),
                'finalized_by' => Auth::id(),
                'payable_account_id' => $payable->id,
                'accrual_journal_id' => $journal?->id ?: $activeAccrual?->id,
            ])->save();

            return $period;
        });
    }

    /**
     * PAY:
     * Dr 2102 Hutang Upah Borongan
     * Cr Kas/Bank (setelah bonus tabungan dan potongan hutang karyawan)
     * Cr Tabungan Karyawan (jika bonus disisihkan)
     * Cr Piutang Pinjaman Karyawan (jika ada potongan)
     */
    public function pay(
        PieceworkPayrollPeriod $period,
        int $paidFromAccountId,
        array $loanDeductions = [],
        array $attendanceBonusDestinations = [],
    ): PieceworkPayrollPeriod
    {
        return DB::transaction(function () use ($period, $paidFromAccountId, $loanDeductions, $attendanceBonusDestinations) {
            // Satu lock per payroll period menjadi guard utama terhadap double
            // submit dari dua request yang datang hampir bersamaan.
            $period = PieceworkPayrollPeriod::query()
                ->lockForUpdate()
                ->findOrFail($period->getKey());

            if ($period->status !== 'final') {
                throw new \RuntimeException('Periode harus FINAL sebelum dibayar.');
            }

            // Recovery untuk data lama: jurnal payment mungkin sudah terbentuk,
            // tetapi marker di payroll period gagal tersimpan.
            $existingPayment = Journal::query()
                ->with('lines')
                ->where('source_type', 'piecework_payroll_period_payment')
                ->where('source_id', $period->id)
                ->whereNull('voided_at')
                ->latest('id')
                ->first();

            if ($existingPayment) {
                $paidAmount = round((float) $existingPayment->lines->sum('debit'), 2);
                if ($paidAmount <= 0) {
                    throw new \RuntimeException('Jurnal pembayaran payroll ditemukan tetapi nilainya tidak valid.');
                }

                $paidFromLine = $existingPayment->lines
                    ->first(function ($line) {
                        return (float) $line->credit > 0
                            && Account::query()
                                ->whereKey($line->account_id)
                                ->where('is_cash', true)
                                ->exists();
                    });

                $period->forceFill([
                    'payment_journal_id' => $existingPayment->id,
                    'paid_from_account_id' => $paidFromLine?->account_id ?: $period->paid_from_account_id,
                    'paid_at' => $period->paid_at ?: ($existingPayment->posted_at ?: now()),
                    'total_amount' => $period->total_amount ?: $paidAmount,
                ])->save();

                return $period->fresh();
            }

            if ($period->payment_journal_id || $period->paid_at) {
                throw new \RuntimeException(
                    'Marker pembayaran payroll ada, tetapi jurnal pembayaran aktif tidak ditemukan. Periksa jurnal sebelum membayar ulang.'
                );
            }

            $total = (float) ($period->total_amount ?? 0);
            if ($total <= 0) {
                // safety: hitung ulang kalau total kosong
                $attendanceBonuses = DailyAttendanceBonusCalculator::forPeriod($period);
                $total = round(
                    (float) $period->lines()->sum('amount')
                    + (float) $attendanceBonuses->sum('bonus_amount'),
                    2
                );
            }
            if ($total <= 0) {
                throw new \RuntimeException('Total payroll 0. Tidak bisa dibayar.');
            }

            $attendanceBonuses ??= DailyAttendanceBonusCalculator::forPeriod($period);
            $eligibleBonusEmployeeIds = $attendanceBonuses
                ->pluck('employee_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $requestedBonusDestinations = collect($attendanceBonusDestinations)
                ->mapWithKeys(fn ($destination, $employeeId) => [(int) $employeeId => (string) $destination])
                ->all();

            foreach ($requestedBonusDestinations as $employeeId => $destination) {
                if (! in_array($employeeId, $eligibleBonusEmployeeIds, true)) {
                    throw new \RuntimeException('Pilihan bonus kehadiran tidak sesuai dengan operator yang eligible.');
                }
                if (! in_array($destination, ['savings', 'paid'], true)) {
                    throw new \RuntimeException('Tujuan bonus kehadiran tidak valid.');
                }
            }

            $savedBonusRows = $attendanceBonuses
                ->map(function (array $bonus) use ($requestedBonusDestinations) {
                    $destination = $requestedBonusDestinations[$bonus['employee_id']] ?? 'savings';

                    return $bonus + ['destination' => $destination];
                })
                ->filter(fn (array $bonus) => $bonus['destination'] === 'savings')
                ->values();
            $savedBonusTotal = round((float) $savedBonusRows->sum('bonus_amount'), 2);

            $deductions = collect($loanDeductions)
                ->mapWithKeys(fn ($amount, $loanId) => [(int) $loanId => round((float) $amount, 2)])
                ->filter(fn (float $amount) => $amount > 0)
                ->all();
            $loans = collect();
            $deductionTotal = 0.0;

            if ($deductions) {
                $periodEmployeeIds = $period->lines()
                    ->pluck('employee_id')
                    ->map(fn ($id) => (int) $id)
                    ->unique();

                $loans = EmployeeLoan::query()
                    ->whereIn('id', array_keys($deductions))
                    ->whereIn('status', ['posted', 'settled'])
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($deductions as $loanId => $amount) {
                    $loan = $loans->get($loanId);
                    if (! $loan) {
                        throw new \RuntimeException("Pinjaman karyawan #{$loanId} tidak ditemukan atau belum POSTED.");
                    }
                    if (! $periodEmployeeIds->contains((int) $loan->employee_id)) {
                        throw new \RuntimeException("Pinjaman karyawan #{$loanId} bukan milik operator pada payroll ini.");
                    }

                    $paid = (float) $loan->repayments()
                        ->where('status', 'posted')
                        ->sum('amount');
                    $outstanding = max(0, round((float) $loan->principal_amount - $paid, 2));
                    if ($amount > $outstanding + 0.01) {
                        throw new \RuntimeException(
                            "Potongan pinjaman #{$loanId} melebihi sisa hutang (Rp "
                            .number_format($outstanding, 2, ',', '.').').'
                        );
                    }

                    $deductionTotal += $amount;
                }
            }

            $deductionTotal = round($deductionTotal, 2);
            if ($deductionTotal + $savedBonusTotal > $total + 0.01) {
                throw new \RuntimeException('Total potongan hutang melebihi total payroll.');
            }

            // hutang upah borongan (ambil dari period kalau ada)
            $payableId = (int) ($period->payable_account_id ?: 0);
            if (! $payableId) {
                $payable = Account::where('code', '2102')->first();
                if (! $payable) {
                    throw new \RuntimeException('Akun 2102 tidak ditemukan.');
                }

                $payableId = $payable->id;
            }

            // akun kas/bank pembayaran
            $paidFrom = Account::findOrFail($paidFromAccountId);
            if (! $paidFrom->is_cash || ! $paidFrom->is_active) {
                throw new \RuntimeException('Akun pembayaran harus akun Kas/Bank.');
            }

            $payrollLabel = $period->module === 'daily' ? 'Payroll Harian' : 'Payroll Borongan';
            $netPayment = round($total - $deductionTotal - $savedBonusTotal, 2);
            $desc = strtoupper($period->module).' '.$payrollLabel.' (PAY) '
            .$period->period_start.' s/d '.$period->period_end
            .' via '.$paidFrom->name
            .($savedBonusTotal > 0 ? ' · Bonus tabungan Rp '.number_format($savedBonusTotal, 2, ',', '.') : '')
            .($deductionTotal > 0 ? ' · Potongan hutang karyawan Rp '.number_format($deductionTotal, 2, ',', '.') : '');

            $paymentLines = [
                ['account_id' => $payableId, 'debit' => $total, 'credit' => 0],
            ];
            if ($netPayment > 0) {
                $paymentLines[] = ['account_id' => $paidFrom->id, 'debit' => 0, 'credit' => $netPayment];
            }
            if ($savedBonusTotal > 0) {
                $savingsAccount = Account::firstOrCreate(
                    ['code' => '2104'],
                    [
                        'name' => 'Tabungan Karyawan',
                        'type' => 'liability',
                        'is_cash' => false,
                        'is_active' => true,
                    ]
                );
                if ($savingsAccount->type !== 'liability' || ! $savingsAccount->is_active) {
                    throw new \RuntimeException('Akun 2104 harus aktif dan bertipe liability untuk Tabungan Karyawan.');
                }

                $paymentLines[] = [
                    'account_id' => $savingsAccount->id,
                    'debit' => 0,
                    'credit' => $savedBonusTotal,
                ];
            }
            foreach ($deductions as $loanId => $amount) {
                $paymentLines[] = [
                    'account_id' => (int) $loans->get($loanId)->receivable_account_id,
                    'debit' => 0,
                    'credit' => $amount,
                ];
            }

            $journal = $this->journalService->post(
                date: now()->toDateString(),
                sourceType: 'piecework_payroll_period_payment',
                sourceId: $period->id,
                description: $desc,
                lines: $paymentLines,
            );

            foreach ($savedBonusRows as $bonus) {
                EmployeeSavingsTransaction::create([
                    'employee_id' => $bonus['employee_id'],
                    'payroll_period_id' => $period->id,
                    'date' => now()->toDateString(),
                    'amount' => $bonus['bonus_amount'],
                    'type' => EmployeeSavingsTransaction::TYPE_DEPOSIT,
                    'status' => 'posted',
                    'journal_id' => $journal->id,
                    'source_type' => EmployeeSavingsTransaction::SOURCE_PAYROLL_BONUS,
                    'source_id' => $period->id,
                    'created_by' => Auth::id(),
                    'notes' => 'Bonus kehadiran 10% disisihkan ke tabungan karyawan.',
                ]);
            }

            foreach ($deductions as $loanId => $amount) {
                $loan = $loans->get($loanId);
                $paid = (float) $loan->repayments()->where('status', 'posted')->sum('amount');

                EmployeeLoanRepayment::create([
                    'employee_loan_id' => $loan->id,
                    'date' => now()->toDateString(),
                    'amount' => $amount,
                    'cash_account_id' => $paidFrom->id,
                    'reference' => 'PAYROLL-'.$period->module.'-'.$period->id,
                    'status' => 'posted',
                    'journal_id' => $journal->id,
                    'source_type' => EmployeeLoanRepayment::SOURCE_PAYROLL_DEDUCTION,
                    'source_id' => $period->id,
                    'created_by' => Auth::id(),
                    'notes' => 'Potongan hutang dari pembayaran payroll.',
                ]);

                if ($paid + $amount >= (float) $loan->principal_amount - 0.01) {
                    $loan->update(['status' => 'settled']);
                }
            }

            $period->forceFill([
                'paid_from_account_id' => $paidFrom->id,
                'paid_at' => now(),
                'paid_by' => Auth::id(),
                'payment_journal_id' => $journal->id,
                'total_amount' => $total,
            ])->save();

            return $period->fresh();
        });
    }
}
