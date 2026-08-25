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
                self::COMPLETED_WITHDRAWAL
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

                // Tanpa ID dan nominal, record tidak aman untuk dijadikan
                // dokumen accounting dan harus dibiarkan untuk investigasi API.
                if ($transactionId === '' || $amount <= 0) {
                    $skipped++;
                    continue;
                }

                $exists = MarketplacePayout::query()
                    ->where('store_id', $store->id)
                    ->where('external_transaction_id', $transactionId)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                $createdTime = (int) data_get($row, 'create_time', 0);
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
                }
            }

            $pageNo += count($rows);
            $more = (bool) data_get($result, 'response.more', data_get($result, 'more', false));
        } while ($more && count($rows) > 0);

        return compact('created', 'skipped');
    }
}
