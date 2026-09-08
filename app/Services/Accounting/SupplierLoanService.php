<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\SupplierLoan;
use App\Models\SupplierLoanRepayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierLoanService
{
    public function __construct(private JournalService $journals) {}

    public function post(SupplierLoan $loan): SupplierLoan
    {
        return DB::transaction(function () use ($loan) {
            $locked = SupplierLoan::query()->whereKey($loan->id)->lockForUpdate()->firstOrFail();

            if (in_array($locked->status, ['posted', 'settled'], true)) {
                return $locked;
            }
            if ($locked->status === 'void') {
                throw ValidationException::withMessages(['status' => 'Dana supplier sudah VOID, tidak bisa diposting.']);
            }

            $this->validateBeforePost($locked);

            $journal = $this->journals->post(
                $locked->date->toDateString(),
                JournalService::SRC_SUPPLIER_LOAN,
                $locked->id,
                trim(($locked->description ?: 'Dana pinjaman ke supplier').($locked->reference ? " (#{$locked->reference})" : '')),
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

    public function void(SupplierLoan $loan, ?string $reason = null): SupplierLoan
    {
        return DB::transaction(function () use ($loan, $reason) {
            $locked = SupplierLoan::query()->with('journal')->whereKey($loan->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 'void') {
                return $locked;
            }
            if ($locked->status !== 'posted' || ! $locked->journal) {
                throw ValidationException::withMessages(['status' => 'Hanya dana supplier POSTED yang bisa di-VOID.']);
            }
            if ($locked->repayments()->where('status', 'posted')->exists()) {
                throw ValidationException::withMessages(['status' => 'Dana supplier yang sudah memiliki pengembalian POSTED tidak boleh di-VOID.']);
            }

            $this->journals->void($locked->journal, $reason);
            $locked->update([
                'status' => 'void',
                'notes' => trim(($locked->notes ?? '').($reason ? "\nVOID: {$reason}" : '')),
            ]);

            return $locked->fresh();
        });
    }

    public function postRepayment(SupplierLoanRepayment $repayment): SupplierLoanRepayment
    {
        return DB::transaction(function () use ($repayment) {
            $locked = SupplierLoanRepayment::query()
                ->with('supplierLoan')
                ->whereKey($repayment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === 'posted') {
                return $locked;
            }
            if ($locked->status === 'void') {
                throw ValidationException::withMessages(['status' => 'Pengembalian sudah VOID.']);
            }
            if (! in_array($locked->supplierLoan->status, ['posted', 'settled'], true)) {
                throw ValidationException::withMessages(['supplier_loan' => 'Dana supplier harus POSTED sebelum pengembalian dicatat.']);
            }

            $amount = (float) $locked->amount;
            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Nominal pengembalian harus lebih dari 0.']);
            }

            $paid = (float) $locked->supplierLoan->repayments()->where('status', 'posted')->sum('amount');
            $remaining = (float) $locked->supplierLoan->principal_amount - $paid;
            if ($amount > $remaining + 0.01) {
                throw ValidationException::withMessages(['amount' => 'Pengembalian melebihi sisa piutang supplier.']);
            }

            $this->validateCashAccount((int) $locked->cash_account_id);

            $journal = $this->journals->post(
                $locked->date->toDateString(),
                JournalService::SRC_SUPPLIER_LOAN_REPAYMENT,
                $locked->id,
                'Pengembalian dana dari supplier'.($locked->reference ? " (#{$locked->reference})" : ''),
                [
                    ['account_id' => $locked->cash_account_id, 'debit' => $amount, 'credit' => 0],
                    ['account_id' => $locked->supplierLoan->receivable_account_id, 'debit' => 0, 'credit' => $amount],
                ],
                ['reference_no' => $locked->reference, 'notes' => $locked->notes, 'created_by' => $locked->created_by ?? auth()->id()],
            );

            $locked->update(['status' => 'posted', 'journal_id' => $journal->id]);

            $newPaid = $paid + $amount;
            if ($newPaid >= (float) $locked->supplierLoan->principal_amount - 0.01) {
                $locked->supplierLoan->update(['status' => 'settled']);
            }

            return $locked->fresh();
        });
    }

    public function voidRepayment(SupplierLoanRepayment $repayment, ?string $reason = null): SupplierLoanRepayment
    {
        return DB::transaction(function () use ($repayment, $reason) {
            $locked = SupplierLoanRepayment::query()
                ->with(['journal', 'supplierLoan'])
                ->whereKey($repayment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === 'void') {
                return $locked;
            }
            if ($locked->status !== 'posted' || ! $locked->journal) {
                throw ValidationException::withMessages(['status' => 'Hanya pengembalian POSTED yang bisa di-VOID.']);
            }

            $this->journals->void($locked->journal, $reason);
            $locked->update([
                'status' => 'void',
                'notes' => trim(($locked->notes ?? '').($reason ? "\nVOID: {$reason}" : '')),
            ]);

            $paid = (float) $locked->supplierLoan->repayments()->where('status', 'posted')->sum('amount');
            $locked->supplierLoan->update([
                'status' => $paid >= (float) $locked->supplierLoan->principal_amount - 0.01 ? 'settled' : 'posted',
            ]);

            return $locked->fresh();
        });
    }

    private function validateBeforePost(SupplierLoan $loan): void
    {
        if ((float) $loan->principal_amount <= 0) {
            throw ValidationException::withMessages(['principal_amount' => 'Nominal dana supplier harus lebih dari 0.']);
        }

        $this->validateCashAccount((int) $loan->cash_account_id);

        $receivable = Account::query()
            ->whereKey($loan->receivable_account_id)
            ->where('type', 'asset')
            ->where('is_cash', false)
            ->where('is_active', true)
            ->exists();

        if (! $receivable) {
            throw ValidationException::withMessages(['receivable_account_id' => 'Akun piutang harus akun aset non-kas yang aktif.']);
        }
        if ((int) $loan->cash_account_id === (int) $loan->receivable_account_id) {
            throw ValidationException::withMessages(['account' => 'Akun kas/bank dan piutang harus berbeda.']);
        }
    }

    private function validateCashAccount(int $accountId): void
    {
        $valid = Account::query()
            ->whereKey($accountId)
            ->where('is_cash', true)
            ->where('is_active', true)
            ->exists();

        if (! $valid) {
            throw ValidationException::withMessages(['cash_account_id' => 'Akun kas/bank harus aktif dan bertipe kas/bank.']);
        }
    }
}
