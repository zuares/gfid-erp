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
    private const COMPLETED_WITHDRAWAL = '202';
    private const COMPLETED_WITHDRAWAL_LABELS = [
        '202',
        'withdrawal_completed',
        'withdrawal-completed',
    ];
    private const KNOWN_NON_WITHDRAWAL_LABELS = [
        '101', '102', '201', '203',
        'escrow_verified_add', 'escrow_verified_minus',
        'withdrawal_created', 'withdrawal_cancelled',
        'withdrawal-created', 'withdrawal-cancelled',
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
     * WITHDRAWAL_COMPLETED (202) agar order income, ads charge, dan adjustment
     * tidak ikut tercatat sebagai penerimaan bank.
     */
    public function import(
        Store $store,
        Carbon $from,
        Carbon $to,
        int $bankAccountId,
        ?int $createdBy = null
    ): array {
        $created = 0;
        $skipped = 0;
        $skippedExisting = 0;
        $skippedInvalid = 0;
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
                self::COMPLETED_WITHDRAWAL,
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

            foreach ($rows as $row) {
                $transactionId = (string) data_get($row, 'transaction_id', '');
                $amount = abs((float) data_get($row, 'amount', 0));
                $transactionType = (string) data_get($row, 'transaction_type', '');
                $createdTime = (int) data_get($row, 'create_time', 0);

                // Tanpa ID dan nominal, record tidak aman untuk dijadikan
                // dokumen accounting dan harus dibiarkan untuk investigasi API.
                // Guard tipe lokal tetap dipakai walaupun filter sudah dikirim
                // ke Shopee, karena fixture/cache/API proxy bisa mengembalikan
                // record tambahan.
                if ($transactionId === '' || $amount <= 0
                    || ! $this->isCompletedWithdrawalType($transactionType)) {
                    $skipped++;
                    $skippedInvalid++;
                    continue;
                }

                $exists = MarketplacePayout::query()
                    ->where('store_id', $store->id)
                    ->where('external_transaction_id', $transactionId)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    $skippedExisting++;
                    continue;
                }

                $date = $createdTime > 0
                    ? Carbon::createFromTimestamp($createdTime, config('app.timezone'))
                    : $from->copy();

                $wasCreated = DB::transaction(function () use (
                    $store,
                    $bankAccountId,
                    $createdBy,
                    $row,
                    $transactionId,
                    $amount,
                    $date
                ): bool {
                    // Cek ulang di dalam transaksi untuk mengurangi risiko
                    // duplikasi jika dua user menekan import bersamaan.
                    $alreadyImported = MarketplacePayout::query()
                        ->where('store_id', $store->id)
                        ->where('external_transaction_id', $transactionId)
                        ->lockForUpdate()
                        ->exists();

                    if ($alreadyImported) {
                        return false;
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
                        'transaction_type'       => (string) data_get($row, 'transaction_type', self::COMPLETED_WITHDRAWAL),
                        'transaction_created_at'  => $date,
                        'source_payload'          => $row,
                        'description'             => 'Pencairan saldo Shopee'
                            . ($reason !== '' ? " ({$reason})" : ''),
                        'status'                  => 'draft',
                        'created_by'              => $createdBy,
                        'notes'                   => "Diimpor dari wallet Shopee; status {$status}.",
                    ]);

                    return true;
                });

                if ($wasCreated) {
                    $created++;
                } else {
                    $skipped++;
                    $skippedExisting++;
                }
            }

            $pageNo += count($rows);
            $more = (bool) data_get($result, 'response.more', data_get($result, 'more', false));
        } while ($more && count($rows) > 0);

        return compact('created', 'skipped', 'skippedExisting', 'skippedInvalid');
    }

    private function isCompletedWithdrawalType(string $transactionType): bool
    {
        $normalized = strtolower(trim($transactionType));

        if ($normalized === '' || in_array($normalized, self::COMPLETED_WITHDRAWAL_LABELS, true)) {
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
