<?php

namespace App\Services\Marketplace\Finance;

use App\Domain\Marketplace\Finance\Enums\ComponentDirection;
use App\Domain\Marketplace\Finance\Enums\EscrowStatus;
use App\Domain\Marketplace\Finance\Enums\SettlementStatus;
use App\Models\Account;
use App\Models\Journal;
use App\Models\MarketplaceFinanceSettlement;
use App\Models\MarketplaceFinancialClosing;
use App\Models\MarketplaceFinancialTransaction;
use App\Services\Accounting\JournalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarketplaceFinancePostingService
{
    public function __construct(private JournalService $journalService) {}

    public function postSale(MarketplaceFinancialTransaction $transaction, ?int $userId = null): MarketplaceFinancialTransaction
    {
        return DB::transaction(function () use ($transaction, $userId): MarketplaceFinancialTransaction {
            $locked = MarketplaceFinancialTransaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            $existing = $this->activeLinkedJournal($locked->sale_journal_id);
            if ($existing) {
                return $locked;
            }
            $this->assertPeriodOpen($this->date($locked->synced_at ?? $locked->created_at), $locked->store_id);

            $amount = $this->positiveAmount($locked->gross_amount, 'gross_amount', 'SALE_POSTED');
            $receivable = $this->mappedAccount('marketplace_receivable', 'Piutang Shopee');
            $sales = $this->mappedAccount('sales', 'Penjualan');
            $journal = $this->journalService->post(
                $this->date($locked->synced_at ?? $locked->created_at),
                JournalService::SRC_MARKETPLACE_SALE,
                $locked->id,
                "Marketplace sale {$locked->order_sn}",
                [
                    $this->line($receivable, $amount, 0),
                    $this->line($sales, 0, $amount),
                ],
                $this->meta("SALE-{$locked->id}", 'SALE_POSTED', $userId),
            );

            $locked->forceFill(['sale_journal_id' => $journal->id])->save();

            return $locked->fresh();
        });
    }

    public function postEscrow(MarketplaceFinancialTransaction $transaction, ?int $userId = null): MarketplaceFinancialTransaction
    {
        return DB::transaction(function () use ($transaction, $userId): MarketplaceFinancialTransaction {
            $locked = MarketplaceFinancialTransaction::query()->with('components')->whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            $existing = $this->activeLinkedJournal($locked->escrow_journal_id);
            if ($existing) {
                return $locked;
            }
            $this->assertPeriodOpen($this->date($locked->released_at ?? $locked->synced_at ?? $locked->created_at), $locked->store_id);
            if (! in_array($locked->escrow_status, [EscrowStatus::SYNCED, EscrowStatus::FINALIZED], true)) {
                throw ValidationException::withMessages(['escrow' => 'Escrow belum tersedia untuk difinalisasi.']);
            }
            if ($locked->components->isEmpty()) {
                throw ValidationException::withMessages(['escrow' => 'Escrow tidak memiliki component fee/adjustment untuk diposting.']);
            }

            $receivable = $this->mappedAccount('marketplace_receivable', 'Piutang Shopee');
            $lines = [];
            foreach ($locked->components as $component) {
                $amount = abs(round((float) $component->amount, 2));
                if ($amount <= 0) {
                    continue;
                }
                $account = $component->account_id
                    ? Account::query()->whereKey($component->account_id)->where('is_active', true)->first()
                    : $this->mappedAccount($this->componentAccountKey($component->component_code), $component->component_name);
                if (! $account) {
                    throw ValidationException::withMessages(['account' => "Akun component {$component->component_code} tidak ditemukan atau tidak aktif."]);
                }

                if ($component->direction === ComponentDirection::CREDIT) {
                    $lines[] = $this->line($receivable, $amount, 0);
                    $lines[] = $this->line($account, 0, $amount);
                } else {
                    $lines[] = $this->line($account, $amount, 0);
                    $lines[] = $this->line($receivable, 0, $amount);
                }
            }
            if ($lines === []) {
                throw ValidationException::withMessages(['escrow' => 'Escrow component tidak memiliki nominal positif untuk diposting.']);
            }

            $journal = $this->journalService->post(
                $this->date($locked->released_at ?? $locked->synced_at ?? $locked->created_at),
                JournalService::SRC_MARKETPLACE_ESCROW,
                $locked->id,
                "Marketplace escrow {$locked->order_sn}",
                $lines,
                $this->meta("ESCROW-{$locked->id}", 'ESCROW_FINALIZED', $userId),
            );

            $locked->forceFill([
                'escrow_status' => EscrowStatus::FINALIZED,
                'escrow_journal_id' => $journal->id,
            ])->save();

            return $locked->fresh();
        });
    }

    public function postSettlement(MarketplaceFinanceSettlement $settlement, ?int $userId = null): MarketplaceFinanceSettlement
    {
        return DB::transaction(function () use ($settlement, $userId): MarketplaceFinanceSettlement {
            $locked = MarketplaceFinanceSettlement::query()->whereKey($settlement->id)->lockForUpdate()->firstOrFail();
            $existing = $this->activeLinkedJournal($locked->journal_id);
            if ($existing) {
                return $locked;
            }
            $this->assertPeriodOpen($locked->settlement_date?->toDateString() ?? now()->toDateString(), $locked->store_id);
            if ($locked->status !== SettlementStatus::RECEIVED) {
                throw ValidationException::withMessages(['settlement' => 'Settlement belum berstatus received dengan bukti payout yang valid.']);
            }
            $amount = $this->positiveAmount($locked->amount, 'amount', 'SETTLEMENT_RECEIVED');
            if (! $locked->bank_account_id) {
                throw ValidationException::withMessages(['account' => 'Settlement belum memiliki akun bank tujuan.']);
            }
            $bank = Account::query()
                ->whereKey($locked->bank_account_id)
                ->where('is_active', true)
                ->where('is_cash', true)
                ->first();
            if (! $bank) {
                throw ValidationException::withMessages(['account' => 'Akun bank settlement tidak ditemukan atau tidak aktif.']);
            }
            $receivable = $this->mappedAccount('marketplace_receivable', 'Piutang Shopee');
            $journal = $this->journalService->post(
                $locked->settlement_date?->toDateString() ?? now()->toDateString(),
                JournalService::SRC_MARKETPLACE_SETTLEMENT,
                $locked->id,
                "Marketplace settlement {$locked->external_settlement_id}",
                [
                    $this->line($bank, $amount, 0),
                    $this->line($receivable, 0, $amount),
                ],
                $this->meta("SETTLEMENT-{$locked->id}", 'SETTLEMENT_RECEIVED', $userId),
            );

            $locked->forceFill(['journal_id' => $journal->id])->save();

            return $locked->fresh();
        });
    }

    public function reverseSale(MarketplaceFinancialTransaction $transaction, ?string $reason = null): ?Journal
    {
        $this->assertPeriodOpen($this->date($transaction->synced_at ?? $transaction->created_at), $transaction->store_id);

        return $this->reverseSource(JournalService::SRC_MARKETPLACE_SALE, $transaction->id, $reason);
    }

    public function reverseEscrow(MarketplaceFinancialTransaction $transaction, ?string $reason = null): ?Journal
    {
        $this->assertPeriodOpen($this->date($transaction->released_at ?? $transaction->synced_at ?? $transaction->created_at), $transaction->store_id);

        return $this->reverseSource(JournalService::SRC_MARKETPLACE_ESCROW, $transaction->id, $reason);
    }

    public function reverseSettlement(MarketplaceFinanceSettlement $settlement, ?string $reason = null): ?Journal
    {
        $this->assertPeriodOpen($settlement->settlement_date?->toDateString() ?? now()->toDateString(), $settlement->store_id);
        $reversal = $this->reverseSource(JournalService::SRC_MARKETPLACE_SETTLEMENT, $settlement->id, $reason);
        if ($reversal) {
            $settlement->forceFill(['status' => SettlementStatus::VOID])->save();
        }

        return $reversal;
    }

    private function reverseSource(string $sourceType, int $sourceId, ?string $reason): ?Journal
    {
        return $this->journalService->voidBySource($sourceType, $sourceId, $reason);
    }

    private function activeLinkedJournal(?int $journalId): ?Journal
    {
        return $journalId ? Journal::query()->whereKey($journalId)->whereNull('voided_at')->first() : null;
    }

    private function mappedAccount(string $key, string $label): Account
    {
        $code = (string) config("marketplace.accounting_accounts.{$key}", '');
        $account = Account::query()->where('code', $code)->where('is_active', true)->first();
        if (! $account) {
            throw ValidationException::withMessages(['account' => "Akun {$code} {$label} tidak ditemukan atau tidak aktif."]);
        }

        return $account;
    }

    private function componentAccountKey(string $code): string
    {
        return match ($code) {
            'admin_fee' => 'commission_fee',
            'service_fee' => 'service_fee',
            'transaction_fee' => 'transaction_fee',
            'affiliate_fee' => 'affiliate_fee',
            'shipping_insurance' => 'shipping_insurance',
            'voucher', 'rebate', 'refund' => 'sales_return',
            default => 'other_fee',
        };
    }

    private function positiveAmount(mixed $value, string $field, string $event): float
    {
        $amount = round((float) $value, 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages([$field => "{$event} membutuhkan {$field} lebih besar dari 0."]);
        }

        return $amount;
    }

    private function line(Account $account, float $debit, float $credit): array
    {
        return ['account_id' => $account->id, 'debit' => round($debit, 2), 'credit' => round($credit, 2)];
    }

    private function date(mixed $date): string
    {
        return $date ? $date->toDateString() : now()->toDateString();
    }

    private function assertPeriodOpen(string $date, ?int $storeId): void
    {
        $closed = MarketplaceFinancialClosing::query()
            ->where('status', 'closed')
            ->whereDate('date_from', '<=', $date)
            ->whereDate('date_to', '>=', $date)
            ->where(function ($query) use ($storeId): void {
                $query->whereNull('store_id');
                if ($storeId !== null) {
                    $query->orWhere('store_id', $storeId);
                }
            })
            ->exists();

        if ($closed) {
            throw ValidationException::withMessages([
                'posting' => 'Periode marketplace sudah closed. Reopen periode terlebih dahulu sebelum posting atau reversal.',
            ]);
        }
    }

    private function meta(string $reference, string $event, ?int $userId): array
    {
        return ['reference_no' => $reference, 'notes' => "Marketplace Finance event {$event}", 'created_by' => $userId ?? auth()->id()];
    }
}
