<?php

namespace App\Services\Accounting;

use App\Models\MarketplacePayout;
use App\Models\Store;
use App\Services\Channels\Shopee\ShopeeChannel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ShopeeWalletPayoutImportService
{
    // Nominal pencairan biasanya berada pada event withdrawal_created (201).
    // withdrawal_completed (202) dapat hadir sebagai marker dengan amount 0.
    private const PAYOUT_WITHDRAWAL_REQUEST = '201';
    private const PAYOUT_WITHDRAWAL_LABELS = [
        '201',
        '202',
        'withdrawal_created',
        'withdrawal_completed',
        'withdrawal-created',
        'withdrawal-completed',
    ];
    private const KNOWN_NON_WITHDRAWAL_LABELS = [
        '101', '102', '203',
        'escrow_verified_add', 'escrow_verified_minus',
        'withdrawal_cancelled', 'withdrawal-cancelled',
        '450', 'paid_ads_charge', 'paid-ads-charge',
    ];

    public function __construct(private readonly ShopeeChannel $shopee)
    {
    }

    /**
     * Import pencairan wallet Shopee sebagai dokumen DRAFT.
     *
     * get_wallet_transaction_list mengembalikan semua mutasi wallet. Karena
     * tabel ini adalah jurnal penerimaan bank, request dibatasi ke
     * WITHDRAWAL_CREATED (201) karena event ini membawa nominal yang dipotong
     * dari wallet. Event WITHDRAWAL_COMPLETED (202) hanya dipakai jika Shopee
     * mengembalikan nominal non-zero pada event tersebut.
     */
    public function import(
        Store $store,
        Carbon $from,
        Carbon $to,
        int $bankAccountId,
        ?int $createdBy = null
    ): array {
        $totals = [
            'created'               => 0,
            'skipped'               => 0,
            'skippedExisting'       => 0,
            'bankConflicts'         => 0,
            'skippedInvalid'        => 0,
            'skippedInvalidReasons' => [],
        ];
        $periodTo = $to->copy()->endOfDay();
        $chunkFrom = $from->copy()->startOfDay();

        // Shopee hanya menerima maksimal 15 tanggal kalender per request.
        // Pecah periode yang lebih panjang agar pengguna tetap bisa import
        // satu bulan tanpa terkena error dari API.
        while ($chunkFrom->lte($periodTo)) {
            $chunkTo = $chunkFrom->copy()->addDays(14)->endOfDay();
            if ($chunkTo->gt($periodTo)) {
                $chunkTo = $periodTo->copy();
            }

            $result = $this->importWindow(
                $store,
                $chunkFrom,
                $chunkTo,
                $bankAccountId,
                $createdBy
            );

            foreach (['created', 'skipped', 'skippedExisting', 'bankConflicts', 'skippedInvalid'] as $key) {
                $totals[$key] += $result[$key] ?? 0;
            }
            foreach ($result['skippedInvalidReasons'] ?? [] as $reason => $count) {
                $totals['skippedInvalidReasons'][$reason] =
                    ($totals['skippedInvalidReasons'][$reason] ?? 0) + $count;
            }

            $chunkFrom = $chunkTo->copy()->addSecond()->startOfDay();
        }

        return $totals;
    }

    private function importWindow(
        Store $store,
        Carbon $from,
        Carbon $to,
        int $bankAccountId,
        ?int $createdBy = null
    ): array {
        $created = 0;
        $skipped = 0;
        $skippedExisting = 0;
        $bankConflicts = 0;
        $skippedInvalid = 0;
        $skippedInvalidReasons = [];
        $pageNo = 0;
        $pageSize = 100;

        do {
            $result = $this->shopee->getWalletTransactionList(
                $store,
                $pageNo,
                $pageSize,
                $from->timestamp,
                $to->timestamp,
                'MONEY_OUT',
                self::PAYOUT_WITHDRAWAL_REQUEST,
                null,
                'wallet_withdrawals'
            );

            if (! empty($result['error'])) {
                throw new RuntimeException((string) ($result['message'] ?? $result['error']));
            }

            $rows = data_get($result, 'response.transaction_list');
            if (! is_array($rows)) {
                $rows = data_get($result, 'transaction_list', []);
            }
            if (! is_array($rows)) {
                $rows = [];
            }
            $rows = $this->normalizeTransactionRows($rows);

            foreach ($rows as $row) {
                $transactionId = (string) (
                    data_get($row, 'transaction_id')
                    ?? data_get($row, 'withdraw_id')
                    ?? data_get($row, 'withdrawal_id')
                    ?? data_get($row, 'root_withdrawal_id', '')
                );
                $amountRaw = data_get($row, 'amount')
                    ?? data_get($row, 'transaction_amount')
                    ?? data_get($row, 'withdrawal_amount')
                    ?? data_get($row, 'net_amount', 0);
                $amount = abs((float) str_replace(',', '', (string) $amountRaw));
                $transactionType = (string) (
                    data_get($row, 'transaction_type')
                    ?? data_get($row, 'type', '')
                );
                $createdTime = (int) data_get($row, 'create_time', 0);

                // Tanpa ID dan nominal, record tidak aman untuk dijadikan
                // dokumen accounting dan harus dibiarkan untuk investigasi API.
                // Guard tipe lokal tetap dipakai walaupun filter sudah dikirim
                // ke Shopee, karena fixture/cache/API proxy bisa mengembalikan
                // record tambahan.
                $invalidReason = match (true) {
                    ! is_array($row) => 'invalid_shape',
                    $transactionId === '' => 'missing_transaction_id',
                    $amount <= 0 => 'missing_or_zero_amount',
                    ! $this->isCompletedWithdrawalType($transactionType) => 'non_withdrawal_type',
                    default => null,
                };

                if ($invalidReason !== null) {
                    $skipped++;
                    $skippedInvalid++;
                    $skippedInvalidReasons[$invalidReason] = ($skippedInvalidReasons[$invalidReason] ?? 0) + 1;
                    continue;
                }

                $existing = MarketplacePayout::query()
                    ->where('store_id', $store->id)
                    ->where('external_transaction_id', $transactionId)
                    ->first(['id', 'bank_account_id']);

                if ($existing) {
                    $skipped++;
                    $skippedExisting++;
                    if ((int) $existing->bank_account_id !== $bankAccountId) {
                        $bankConflicts++;
                    }
                    continue;
                }

                $date = $createdTime > 0
                    ? Carbon::createFromTimestamp($createdTime, config('app.timezone'))
                    : $from->copy();

                $importResult = DB::transaction(function () use (
                    $store,
                    $bankAccountId,
                    $createdBy,
                    $row,
                    $transactionId,
                    $amount,
                    $date
                ): array {
                    // Cek ulang di dalam transaksi untuk mengurangi risiko
                    // duplikasi jika dua user menekan import bersamaan.
                    $alreadyImported = MarketplacePayout::query()
                        ->where('store_id', $store->id)
                        ->where('external_transaction_id', $transactionId)
                        ->lockForUpdate()
                        ->first(['id', 'bank_account_id']);

                    if ($alreadyImported) {
                        return [
                            'created'       => false,
                            'bank_conflict' => (int) $alreadyImported->bank_account_id !== $bankAccountId,
                        ];
                    }

                    $reason = trim((string) data_get($row, 'reason', ''));
                    $status = trim((string) data_get($row, 'status', 'COMPLETED'));

                    MarketplacePayout::create([
                        'date'                    => $date->toDateString(),
                        'marketplace_name'        => 'Shopee',
                        'store_id'                => $store->id,
                        'source'                  => 'shopee_wallet',
                        'amount'                  => $amount,
                        'bank_account_id'         => $bankAccountId,
                        'reference'               => $transactionId,
                        'external_transaction_id' => $transactionId,
                        'transaction_type'       => (string) data_get($row, 'transaction_type', self::PAYOUT_WITHDRAWAL_REQUEST),
                        'transaction_created_at'  => $date,
                        'source_payload'          => $row,
                        'description'             => 'Pencairan saldo Shopee'
                            . ($reason !== '' ? " ({$reason})" : ''),
                        'status'                  => 'draft',
                        'created_by'              => $createdBy,
                        'notes'                   => "Diimpor dari wallet Shopee; status {$status}.",
                    ]);

                    return ['created' => true, 'bank_conflict' => false];
                });

                if ($importResult['created']) {
                    $created++;
                } else {
                    $skipped++;
                    $skippedExisting++;
                    if ($importResult['bank_conflict']) {
                        $bankConflicts++;
                    }
                }
            }

            $pageNo += count($rows);
            $more = (bool) data_get($result, 'response.more', data_get($result, 'more', false));
        } while ($more && count($rows) > 0);

        return compact(
            'created',
            'skipped',
            'skippedExisting',
            'bankConflicts',
            'skippedInvalid',
            'skippedInvalidReasons'
        );
    }

    private function normalizeTransactionRows(array $rows): array
    {
        if (isset($rows['transaction_list']) && is_array($rows['transaction_list'])) {
            return $rows['transaction_list'];
        }

        // Be tolerant if Shopee returns one transaction object instead of an
        // array, which can happen in mocked/proxied responses.
        if (! array_is_list($rows) && (
            array_key_exists('transaction_id', $rows)
            || array_key_exists('withdraw_id', $rows)
            || array_key_exists('amount', $rows)
        )) {
            return [$rows];
        }

        return $rows;
    }

    private function isCompletedWithdrawalType(string $transactionType): bool
    {
        $normalized = strtolower(trim($transactionType));

        if ($normalized === '' || in_array($normalized, self::PAYOUT_WITHDRAWAL_LABELS, true)) {
            return true;
        }

        if (in_array($normalized, self::KNOWN_NON_WITHDRAWAL_LABELS, true)) {
            return false;
        }

        // Shopee sudah menerima filter transaction_type + transaction_tab_type.
        // Untuk variasi label response baru, percayakan filter server daripada
        // membuang payout yang valid hanya karena enum belum dikenal aplikasi.
        return true;
    }
}
