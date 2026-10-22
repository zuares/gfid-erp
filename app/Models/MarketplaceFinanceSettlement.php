<?php

namespace App\Models;

use App\Domain\Marketplace\Finance\Enums\SettlementStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceFinanceSettlement extends Model
{
    protected $fillable = [
        'store_id',
        'channel',
        'external_settlement_id',
        'settlement_date',
        'amount',
        'currency',
        'bank_account_id',
        'status',
        'journal_id',
        'received_at',
        'raw_payload',
    ];

    protected $casts = [
        'settlement_date' => 'date',
        'amount' => 'decimal:2',
        'status' => SettlementStatus::class,
        'received_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(MarketplaceFinanceSettlementAllocation::class, 'settlement_id');
    }

    public function isWalletWithdrawal(): bool
    {
        return self::isWalletWithdrawalPayload($this->raw_payload ?? []);
    }

    public function isProviderPayout(): bool
    {
        return self::isProviderPayoutPayload($this->raw_payload ?? []);
    }

    public function requiresBankVerification(): bool
    {
        return in_array($this->status, [SettlementStatus::WITHDRAWN, SettlementStatus::PAYOUT_COMPLETED], true)
            || $this->isWalletWithdrawal()
            || $this->isProviderPayout();
    }

    public static function isProviderPayoutPayload(array $payload): bool
    {
        if (($payload['source_kind'] ?? null) === 'marketplace_payout') {
            return true;
        }

        return is_array($payload['payout_info'] ?? null)
            || array_key_exists('encrypted_payout_id', $payload)
            || array_key_exists('payout_id', $payload)
            || array_key_exists('payout_sn', $payload)
            || array_key_exists('payout_reference', $payload);
    }

    public static function isWalletWithdrawalPayload(array $payload): bool
    {
        if (($payload['source_kind'] ?? null) === 'wallet_withdrawal') {
            return true;
        }

        $type = strtolower(str_replace('-', '_', trim((string) (data_get($payload, 'transaction_type') ?? data_get($payload, 'type') ?? data_get($payload, 'raw.transaction_type') ?? ''))));

        return in_array($type, ['201', '202', 'withdrawal', 'withdrawal_created', 'withdrawal_completed'], true);
    }
}
