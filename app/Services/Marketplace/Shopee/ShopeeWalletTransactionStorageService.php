<?php

namespace App\Services\Marketplace\Shopee;

use App\Models\MarketplaceShopeeWalletTransaction;
use App\Models\Store;
use App\Services\Marketplace\MarketplaceApiGateway;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use RuntimeException;

class ShopeeWalletTransactionStorageService
{
    public function __construct(
        private MarketplaceApiGateway $gateway,
        private ShopeeWalletTransactionService $walletService,
    ) {}

    /** @return array<string, mixed> */
    public function storedTransactions(Store $store, Carbon $from, Carbon $to, array $options = []): array
    {
        $this->assertShopee($store);

        $moneyFlow = strtoupper(trim((string) ($options['money_flow'] ?? '')));
        $moneyFlow = in_array($moneyFlow, ['MONEY_IN', 'MONEY_OUT'], true) ? $moneyFlow : '';
        $transactionType = trim((string) ($options['transaction_type'] ?? ''));
        $walletType = trim((string) ($options['wallet_type'] ?? ''));
        $search = trim((string) ($options['search'] ?? ''));
        $pageNo = max(0, (int) ($options['page_no'] ?? 0));
        $pageSize = min(100, max(1, (int) ($options['page_size'] ?? 100)));

        $baseQuery = MarketplaceShopeeWalletTransaction::query()
            ->where('store_id', $store->id)
            ->whereBetween('transaction_created_at', [$from, $to]);

        $query = (clone $baseQuery)
            ->when($moneyFlow !== '', fn ($query) => $query->where('money_flow', $moneyFlow))
            ->when($transactionType !== '', function ($query) use ($transactionType): void {
                $query->where(function ($query) use ($transactionType): void {
                    $query
                        ->where('transaction_type', $transactionType)
                        ->orWhere('transaction_type', 'like', '%'.$transactionType.'%');

                    $aliases = [
                        '101' => 'escrow_verified_add',
                        '102' => 'escrow_verified_minus',
                        '201' => 'withdrawal_created',
                        '202' => 'withdrawal_completed',
                        '203' => 'withdrawal_cancelled',
                        '450' => 'paid_ads_charge',
                        '451' => 'paid_ads_refund',
                        'spm' => 'spm_deduct',
                    ];
                    $alias = $aliases[strtolower($transactionType)] ?? null;
                    if ($alias !== null) {
                        $query->orWhere('transaction_type', 'like', '%'.$alias.'%');
                    }
                });
            })
            ->when($walletType !== '', fn ($query) => $query->where('wallet_type', $walletType))
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.$search.'%';
                $query->where(function ($query) use ($like): void {
                    $query
                        ->where('external_transaction_id', 'like', $like)
                        ->orWhere('order_sn', 'like', $like)
                        ->orWhere('buyer_name', 'like', $like)
                        ->orWhere('reason', 'like', $like)
                        ->orWhere('transaction_type', 'like', $like);
                });
            });

        $summary = $this->summary(clone $query);
        $lastSyncedAt = $store->shopee_wallet_last_synced_at
            ?? (clone $baseQuery)->max('synced_at');

        if (($options['all'] ?? false) === true) {
            $rows = $query
                ->orderByDesc('transaction_created_at')
                ->orderByDesc('id')
                ->get();

            $items = $rows
                ->map(fn (MarketplaceShopeeWalletTransaction $row): array => $this->present($row, $store->name))
                ->all();

            return [
                'items' => $items,
                'summary' => $summary,
                'pagination' => [
                    'page_no' => 0,
                    'page_size' => count($items),
                    'more' => false,
                    'next_offset' => count($items),
                    'total' => count($items),
                    'current_page' => 1,
                    'last_page' => 1,
                ],
                'last_synced_at' => $lastSyncedAt ? Carbon::parse($lastSyncedAt)->toISOString() : null,
                'storage' => 'local_database',
            ];
        }

        $paginator = $query
            ->orderByDesc('transaction_created_at')
            ->orderByDesc('id')
            ->paginate($pageSize, ['*'], 'page', intdiv($pageNo, $pageSize) + 1);

        return [
            'items' => collect($paginator->items())
                ->map(fn (MarketplaceShopeeWalletTransaction $row): array => $this->present($row, $store->name))
                ->all(),
            'summary' => $summary,
            'pagination' => [
                'page_no' => $pageNo,
                'page_size' => $paginator->perPage(),
                'more' => $paginator->hasMorePages(),
                'next_offset' => $pageNo + $paginator->count(),
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
            'last_synced_at' => $lastSyncedAt ? Carbon::parse($lastSyncedAt)->toISOString() : null,
            'storage' => 'local_database',
        ];
    }

    /** @return array<string, mixed> */
    public function sync(Store $store, Carbon $from, Carbon $to): array
    {
        $this->assertShopee($store);

        $fetched = 0;
        $stored = 0;
        $pages = 0;
        $chunks = 0;
        $truncated = false;
        $periodTo = $to->copy()->endOfDay();
        $chunkFrom = $from->copy()->startOfDay();

        while ($chunkFrom->lte($periodTo)) {
            $chunks++;
            $chunkTo = $chunkFrom->copy()->addDays(14)->endOfDay();
            if ($chunkTo->gt($periodTo)) {
                $chunkTo = $periodTo->copy();
            }

            $pageNo = 0;
            $chunkPages = 0;
            do {
                $chunkPages++;
                $pages++;
                $response = $this->gateway->getWalletTransactionList(
                    $store,
                    $pageNo,
                    100,
                    $chunkFrom->timestamp,
                    $chunkTo->timestamp,
                );
                $this->assertSuccessfulResponse($response);

                $root = $this->responseRoot($response);
                $rows = $this->transactionRows($root);
                $fetched += count($rows);
                $now = now();
                $payloads = [];

                foreach ($rows as $row) {
                    if (! is_array($row)) {
                        continue;
                    }

                    $normalized = $this->walletService->normalizeTransactionRow($row, $store);
                    if ($normalized['transaction_id'] === '') {
                        continue;
                    }

                    $payloads[] = [
                        'store_id' => $store->id,
                        'external_transaction_id' => $normalized['transaction_id'],
                        'transaction_type' => $normalized['transaction_type'] ?: null,
                        'amount' => $normalized['amount'],
                        'currency' => (string) data_get($row, 'currency', 'IDR'),
                        'money_flow' => $normalized['money_flow'],
                        'wallet_type' => $normalized['wallet_type'],
                        'current_balance' => $normalized['current_balance'],
                        'order_sn' => $normalized['order_sn'],
                        'buyer_name' => $normalized['buyer_name'],
                        'status' => $normalized['status'],
                        'reason' => $normalized['reason'],
                        'transaction_created_at' => $normalized['create_time'] > 0
                            ? Carbon::createFromTimestamp($normalized['create_time'], config('app.timezone'))
                            : null,
                        'source_payload' => json_encode(
                            Arr::except($normalized['raw_payload'], ['_meta']),
                            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                        ),
                        'synced_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($payloads !== []) {
                    MarketplaceShopeeWalletTransaction::upsert(
                        $payloads,
                        ['store_id', 'external_transaction_id'],
                        [
                            'transaction_type', 'amount', 'currency', 'money_flow', 'wallet_type',
                            'current_balance', 'order_sn', 'buyer_name', 'status', 'reason',
                            'transaction_created_at', 'source_payload', 'synced_at', 'updated_at',
                        ],
                    );
                    $stored += count($payloads);
                }

                $more = (bool) ($root['more'] ?? false);
                $rowCount = count($rows);
                $pageNo += $rowCount;
                if ($more && ($rowCount === 0 || $chunkPages >= 100)) {
                    $truncated = true;
                    break;
                }
            } while ($more && $pageNo < 10000);

            $chunkFrom = $chunkTo->copy()->addSecond()->startOfDay();
        }

        $syncedAt = now();
        $store->forceFill(['shopee_wallet_last_synced_at' => $syncedAt])->saveQuietly();

        return [
            'fetched' => $fetched,
            'stored' => $stored,
            'pages' => $pages,
            'chunks' => $chunks,
            'truncated' => $truncated,
            'last_synced_at' => $syncedAt->toISOString(),
        ];
    }

    /** @return array<string, mixed> */
    private function present(MarketplaceShopeeWalletTransaction $row, string $storeName): array
    {
        $date = $row->transaction_created_at;
        $amount = (float) $row->amount;
        $meta = $this->typeMeta((string) $row->transaction_type, $amount);

        return [
            'transaction_id' => $row->external_transaction_id,
            'store_id' => $row->store_id,
            'store_name' => $storeName,
            'create_time' => $date?->timestamp ?? 0,
            'date_formatted' => $date?->format('Y-m-d H:i:s') ?? '—',
            'date_human' => $date?->translatedFormat('d M Y, H:i') ?? '—',
            'amount' => $amount,
            'formatted_amount' => 'Rp '.number_format($amount, 0, ',', '.'),
            'signed_amount' => $row->money_flow === 'MONEY_OUT' ? -$amount : $amount,
            'signed_formatted_amount' => ($row->money_flow === 'MONEY_OUT' ? '- Rp ' : '+ Rp ').number_format($amount, 0, ',', '.'),
            'current_balance' => $row->current_balance !== null ? (float) $row->current_balance : null,
            'formatted_balance' => $row->current_balance !== null
                ? 'Rp '.number_format((float) $row->current_balance, 0, ',', '.')
                : '—',
            'money_flow' => $row->money_flow,
            'transaction_type' => $row->transaction_type ?? '',
            'transaction_type_label' => $meta['label'],
            'badge' => $meta['badge'],
            'order_sn' => $row->order_sn,
            'buyer_name' => $row->buyer_name,
            'reason' => $row->reason,
            'status' => $row->status,
            'wallet_type' => $row->wallet_type,
            'raw_payload' => $row->source_payload ?? [],
        ];
    }

    /** @return array<string, mixed> */
    private function summary($query): array
    {
        $moneyIn = (float) (clone $query)->where('money_flow', 'MONEY_IN')->sum('amount');
        $moneyOut = (float) (clone $query)->where('money_flow', 'MONEY_OUT')->sum('amount');
        $breakdown = (clone $query)
            ->select('transaction_type')
            ->selectRaw('COUNT(*) as count, SUM(amount) as total_amount')
            ->groupBy('transaction_type')
            ->orderByDesc('count')
            ->get()
            ->map(function ($row): array {
                $meta = $this->typeMeta((string) $row->transaction_type, (float) $row->total_amount);

                return [
                    'type' => (string) $row->transaction_type,
                    'label' => $meta['label'],
                    'count' => (int) $row->count,
                    'total_amount' => round((float) $row->total_amount, 2),
                    'badge' => $meta['badge'],
                ];
            })
            ->values()
            ->all();

        return [
            'total_count' => (int) (clone $query)->count(),
            'total_money_in' => round($moneyIn, 2),
            'total_money_out' => round($moneyOut, 2),
            'net_amount' => round($moneyIn - $moneyOut, 2),
            'type_breakdown' => $breakdown,
        ];
    }

    /** @return array<string, mixed> */
    private function responseRoot(array $response): array
    {
        return isset($response['response']) && is_array($response['response'])
            ? $response['response']
            : $response;
    }

    /** @return array<int, array<string, mixed>> */
    private function transactionRows(array $root): array
    {
        $rows = data_get($root, 'transaction_list', []);
        if (! is_array($rows)) {
            return [];
        }

        if (! array_is_list($rows) && (isset($rows['transaction_id']) || isset($rows['amount']))) {
            return [$rows];
        }

        return array_values(array_filter($rows, 'is_array'));
    }

    private function assertShopee(Store $store): void
    {
        $code = strtolower((string) ($store->channel?->code ?? ''));
        if (! in_array($code, ['shopee', 'shp'], true)) {
            throw new RuntimeException("Toko #{$store->id} ({$store->name}) bukan channel Shopee.");
        }
    }

    private function assertSuccessfulResponse(array $response): void
    {
        $error = (string) ($response['error'] ?? '');
        if ($error !== '') {
            throw new RuntimeException((string) ($response['message'] ?? $error));
        }
    }

    /** @return array{label: string, badge: string} */
    private function typeMeta(string $type, float $amount): array
    {
        $aliases = [
            '101' => 'escrow_verified_add',
            '102' => 'escrow_verified_minus',
            '201' => 'withdrawal_created',
            '202' => 'withdrawal_completed',
            '203' => 'withdrawal_cancelled',
            '450' => 'paid_ads_charge',
            '451' => 'paid_ads_refund',
            'spm' => 'spm_deduct',
        ];
        $normalized = strtolower(trim($type));
        $lookup = $aliases[$normalized] ?? $normalized;
        $labels = [
            'escrow_verified_add' => ['Pelepasan Escrow (Penjualan Selesai)', 'success'],
            'escrow_verified_minus' => ['Penyesuaian Escrow (Minus)', 'warning'],
            'withdrawal_created' => ['Pengajuan Penarikan Saldo ke Bank', 'info'],
            'withdrawal_completed' => ['Penarikan Saldo ke Bank Berhasil', 'primary'],
            'withdrawal_cancelled' => ['Penarikan Saldo Dibatalkan (Dikembalikan)', 'danger'],
            'paid_ads_charge' => ['Biaya Iklan Berbayar Shopee (Paid Ads)', 'secondary'],
            'paid_ads_refund' => ['Pengembalian Biaya Iklan Shopee (Refund Ads)', 'success'],
            'spm_deduct' => ['Potongan Saldo Iklan / Top Up', 'secondary'],
            'fast_escrow_factor_advance' => ['Pencairan Cepat (Fast Escrow Advance)', 'success'],
            'fast_escrow_factor_deduct' => ['Potongan Pencairan Cepat (Fast Escrow Deduct)', 'warning'],
            'fbs_adjustment_add' => ['Penyesuaian Tambah FBS / Shopee Mart', 'success'],
            'fbs_adjustment_minus' => ['Penyesuaian Kurang FBS / Shopee Mart', 'warning'],
            'manual_adjustment_add' => ['Penyesuaian Manual Tambah', 'success'],
            'manual_adjustment_minus' => ['Penyesuaian Manual Kurang', 'warning'],
        ];
        [$label, $badge] = $labels[$lookup] ?? [ucwords(str_replace(['_', '-'], ' ', $normalized)) ?: 'Transaksi Lainnya', ((float) $amount < 0 ? 'danger' : 'secondary')];

        return ['label' => $label, 'badge' => $badge];
    }
}
