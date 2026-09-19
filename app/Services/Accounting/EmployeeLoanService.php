<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanRepayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeLoanService
{
    public function __construct(private JournalService $journals) {}

    public function post(EmployeeLoan $loan): EmployeeLoan
    {
        return DB::transaction(function () use ($loan) {
            $locked = EmployeeLoan::query()->whereKey($loan->id)->lockForUpdate()->firstOrFail();

            if (in_array($locked->status, ['posted', 'settled'], true)) {
                return $locked;
            }
            if ($locked->status === 'void') {
                throw ValidationException::withMessages(['status' => 'Pinjaman karyawan sudah VOID, tidak bisa diposting.']);
            }
            $this->validateLoan($locked);

            $journal = $this->journals->post(
                $locked->date->toDateString(),
                JournalService::SRC_EMPLOYEE_LOAN,
                $locked->id,
                trim(($locked->description ?: 'Pinjaman karyawan').' · '.($locked->employee?->name ?: 'Karyawan').($locked->reference ? " (#{$locked->reference})" : '')),
                [
                    ['account_id' => $locked->receivable_account_id, 'debit' => (float) $locked->principal_amount, 'credit' => 0],
                    ['account_id' => $locked->cash_account_id, 'debit' => 0, 'credit' => (float) $locked->principal_amount],
                ],
                ['reference_no' => $locked->reference, 'notes' => $locked->notes, 'created_by' => $locked->created_by ?? auth()->id()],
            );

            $locked->update(['status' => 'posted', 'journal_id' => $journal->id]);

            return $locked->fresh();
        });
    }

    public function void(EmployeeLoan $loan, ?string $reason = null): EmployeeLoan
    {
        return DB::transaction(function () use ($loan, $reason) {
            $locked = EmployeeLoan::query()->with('journal')->whereKey($loan->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'void') {
                return $locked;
            }
            if ($locked->status !== 'posted' || ! $locked->journal) {
                throw ValidationException::withMessages(['status' => 'Hanya pinjaman POSTED yang bisa di-VOID.']);
            }
            if ($locked->repayments()->where('status', 'posted')->exists()) {
                throw ValidationException::withMessages(['status' => 'Pinjaman yang sudah memiliki angsuran POSTED tidak boleh di-VOID.']);
            }

            $this->journals->void($locked->journal, $reason);
            $locked->update([
                'status' => 'void',
                'notes' => trim(($locked->notes ?? '').($reason ? "\nVOID: {$reason}" : '')),
            ]);

            return $locked->fresh();
        });
    }

    public function postRepayment(EmployeeLoanRepayment $repayment): EmployeeLoanRepayment
    {
        return DB::transaction(function () use ($repayment) {
            $locked = EmployeeLoanRepayment::query()
                ->with('employeeLoan')
                ->whereKey($repayment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === 'posted') {
                return $locked;
            }
            if ($locked->status === 'void') {
                throw ValidationException::withMessages(['status' => 'Angsuran sudah VOID.']);
            }
            if ($locked->employeeLoan->status !== 'posted') {
                throw ValidationException::withMessages(['employee_loan' => 'Pinjaman harus POSTED sebelum angsuran dicatat.']);
            }

            $amount = (float) $locked->amount;
            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Nominal angsuran harus lebih dari 0.']);
            }

            $paid = (float) $locked->employeeLoan->repayments()->where('status', 'posted')->sum('amount');
            if ($amount > ((float) $locked->employeeLoan->principal_amount - $paid + 0.01)) {
                throw ValidationException::withMessages(['amount' => 'Angsuran melebihi sisa piutang karyawan.']);
            }
            $this->validateCashAccount((int) $locked->cash_account_id);

            $journal = $this->journals->post(
                $locked->date->toDateString(),
                JournalService::SRC_EMPLOYEE_LOAN_REPAYMENT,
                $locked->id,
                'Pembayaran pinjaman karyawan'.($locked->reference ? " (#{$locked->reference})" : ''),
                [
                    ['account_id' => $locked->cash_account_id, 'debit' => $amount, 'credit' => 0],
                    ['account_id' => $locked->employeeLoan->receivable_account_id, 'debit' => 0, 'credit' => $amount],
                ],
                ['reference_no' => $locked->reference, 'notes' => $locked->notes, 'created_by' => $locked->created_by ?? auth()->id()],
            );

            $locked->update(['status' => 'posted', 'journal_id' => $journal->id]);
            if ($paid + $amount >= (float) $locked->employeeLoan->principal_amount - 0.01) {
                $locked->employeeLoan->update(['status' => 'settled']);
            }

            return $locked->fresh();
        });
    }

    public function voidRepayment(EmployeeLoanRepayment $repayment, ?string $reason = null): EmployeeLoanRepayment
    {
        return DB::transaction(function () use ($repayment, $reason) {
            $locked = EmployeeLoanRepayment::query()->with(['journal', 'employeeLoan'])->whereKey($repayment->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'void') {
                return $locked;
            }
            if ($locked->source_type === EmployeeLoanRepayment::SOURCE_PAYROLL_DEDUCTION) {
                throw ValidationException::withMessages([
                    'status' => 'Angsuran dari payroll tidak bisa di-VOID terpisah. Void pembayaran payroll secara keseluruhan jika diperlukan.',
                ]);
            }
            if ($locked->status !== 'posted' || ! $locked->journal) {
                throw ValidationException::withMessages(['status' => 'Hanya angsuran POSTED yang bisa di-VOID.']);
            }

            $this->journals->void($locked->journal, $reason);
            $locked->update([
                'status' => 'void',
                'notes' => trim(($locked->notes ?? '').($reason ? "\nVOID: {$reason}" : '')),
            ]);

            $paid = (float) $locked->employeeLoan->repayments()->where('status', 'posted')->sum('amount');
            $locked->employeeLoan->update([
                'status' => $paid >= (float) $locked->employeeLoan->principal_amount - 0.01 ? 'settled' : 'posted',
            ]);

            return $locked->fresh();
        });
    }

    private function validateLoan(EmployeeLoan $loan): void
    {
        if ((float) $loan->principal_amount <= 0) {
            throw ValidationException::withMessages(['principal_amount' => 'Nominal pinjaman harus lebih dari 0.']);
        }
        $this->validateCashAccount((int) $loan->cash_account_id);

        $receivable = Account::query()
            ->whereKey($loan->receivable_account_id)
            ->where('type', 'asset')
            ->where('is_cash', false)
            ->where('is_active', true)
            ->exists();
        if (! $receivable) {
            throw ValidationException::withMessages(['receivable_account_id' => 'Akun piutang karyawan harus akun aset non-kas yang aktif.']);
        }
        if ((int) $loan->cash_account_id === (int) $loan->receivable_account_id) {
            throw ValidationException::withMessages(['account' => 'Akun kas/bank dan piutang harus berbeda.']);
        }
    }

    private function validateCashAccount(int $accountId): void
    {
        if (! Account::query()->whereKey($accountId)->where('is_cash', true)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['cash_account_id' => 'Akun kas/bank harus aktif dan bertipe kas/bank.']);
        }
    }
}
