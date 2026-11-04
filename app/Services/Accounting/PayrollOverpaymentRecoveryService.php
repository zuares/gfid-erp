<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\EmployeeLoan;
use App\Models\Journal;
use App\Models\PieceworkPayrollLine;
use App\Models\PieceworkPayrollPeriod;
use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingJournal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollOverpaymentRecoveryService
{
    public function __construct(private JournalService $journals) {}

    /**
     * Catat tagihan ke operator setelah sumber upah sudah di-VOID pasca bayar.
     * Payroll yang telah dibayar tetap utuh; koreksi diposting pada tanggal keputusan.
     */
    public function post(
        PieceworkPayrollPeriod $period,
        PieceworkPayrollLine $line,
        Journal $voidedSource,
    ): StandaloneAccountingJournal {
        return DB::transaction(function () use ($period, $line, $voidedSource): StandaloneAccountingJournal {
            $period = PieceworkPayrollPeriod::query()->whereKey($period->id)->lockForUpdate()->firstOrFail();
            $line = PieceworkPayrollLine::query()->whereKey($line->id)->lockForUpdate()->firstOrFail();
            $voidedSource = Journal::query()->with('lines.account')->whereKey($voidedSource->id)->lockForUpdate()->firstOrFail();

            if (! $period->paid_at || ! $period->isFinalized()) {
                $this->fail('Tagihan koreksi hanya dapat dibuat untuk payroll FINAL yang sudah dibayar.');
            }
            if ((int) $line->payroll_period_id !== (int) $period->id) {
                $this->fail('Baris payroll tidak berasal dari periode yang dipilih.');
            }
            if (! in_array($voidedSource->source_type, StandalonePayrollPostingService::productionSources((string) $period->module), true)
                || ! $voidedSource->voided_at?->gt($period->paid_at)
                || ! $voidedSource->date->betweenIncluded($period->period_start, $period->period_end)) {
                $this->fail('Sumber upah harus berasal dari periode ini dan di-VOID setelah pembayaran payroll.');
            }

            $amount = round((float) $voidedSource->lines
                ->filter(fn ($sourceLine) => $sourceLine->account?->code === JournalService::CODE_PAYROLL_PAYABLE)
                ->sum('credit'), 2);
            if ($amount <= 0 || abs($amount - (float) $line->amount) > 0.01) {
                $this->fail('Nilai sumber upah yang di-VOID harus sama dengan baris payroll yang ditagihkan.');
            }
            if (! $period->paid_from_account_id) {
                $this->fail('Akun pembayaran payroll tidak ditemukan.');
            }

            $legacyReceivable = $this->legacyAccount(JournalService::CODE_EMPLOYEE_LOAN, 'asset');
            $legacyPayable = $this->legacyAccount(JournalService::CODE_PAYROLL_PAYABLE, 'liability');
            $standaloneReceivable = $this->standaloneAccount('1-135', 'asset');
            $standalonePayable = $this->standaloneAccount('2-130', 'liability');
            $reference = "PAYROLL-REC-P{$period->id}-L{$line->id}";
            $description = "Tagihan kelebihan payroll #{$period->id} · sumber upah #{$voidedSource->id} VOID setelah bayar";

            $legacyJournal = $this->journals->post(
                now()->toDateString(),
                JournalService::SRC_PAYROLL_OVERPAYMENT_RECOVERY,
                $period->id,
                $description,
                [
                    ['account_id' => $legacyReceivable->id, 'debit' => $amount, 'credit' => 0],
                    ['account_id' => $legacyPayable->id, 'debit' => 0, 'credit' => $amount],
                ],
                [
                    'reference_no' => $reference,
                    'notes' => "Keputusan tagih kembali. Payroll line #{$line->id}; sumber upah #{$voidedSource->id} di-VOID {$voidedSource->voided_at->format('Y-m-d H:i:s')}",
                    'created_by' => $period->paid_by,
                    'approved_by' => $period->paid_by,
                    'approved_at' => now(),
                ],
            );

            $loan = EmployeeLoan::query()->where('reference', $reference)->lockForUpdate()->first();
            if (! $loan) {
                EmployeeLoan::create([
                    'employee_id' => $line->employee_id,
                    'date' => now()->toDateString(),
                    'principal_amount' => $amount,
                    'cash_account_id' => $period->paid_from_account_id,
                    'receivable_account_id' => $legacyReceivable->id,
                    'description' => "Tagihan koreksi kelebihan payroll periode #{$period->id}",
                    'reference' => $reference,
                    'status' => 'posted',
                    'journal_id' => $legacyJournal->id,
                    'created_by' => $period->paid_by,
                    'notes' => "Bukan pinjaman kas. Tagihan karena sumber upah #{$voidedSource->id} di-VOID setelah pembayaran payroll.",
                ]);
            }

            $standalone = StandaloneAccountingJournal::query()
                ->where('source_type', JournalService::SRC_PAYROLL_OVERPAYMENT_RECOVERY)
                ->where('source_id', $period->id)
                ->lockForUpdate()
                ->first();
            if (! $standalone) {
                $standalone = StandaloneAccountingJournal::create([
                    'date' => now()->toDateString(),
                    'reference' => 'SA-'.$reference,
                    'source_type' => JournalService::SRC_PAYROLL_OVERPAYMENT_RECOVERY,
                    'source_id' => $period->id,
                    'description' => $description,
                    'posted_at' => now(),
                ]);
                $standalone->lines()->createMany([
                    ['account_id' => $standaloneReceivable->id, 'debit' => $amount, 'credit' => 0],
                    ['account_id' => $standalonePayable->id, 'debit' => 0, 'credit' => $amount],
                ]);
            }

            return $standalone;
        });
    }

    private function legacyAccount(string $code, string $type): Account
    {
        $account = Account::query()->where('code', $code)->where('is_active', true)->first();
        if (! $account || $account->type !== $type) {
            $this->fail("Akun lama {$code} harus aktif dan bertipe {$type}.");
        }

        return $account;
    }

    private function standaloneAccount(string $code, string $type): StandaloneAccountingAccount
    {
        $account = StandaloneAccountingAccount::query()->where('code', $code)->where('is_active', true)->first();
        if (! $account || $account->type !== $type) {
            $this->fail("Akun Akuntansi Baru {$code} harus aktif dan bertipe {$type}.");
        }

        return $account;
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['payroll_recovery' => $message]);
    }
}
