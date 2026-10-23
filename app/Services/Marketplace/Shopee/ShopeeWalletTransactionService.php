<?php

namespace App\Services\Marketplace\Shopee;

use App\Models\Store;
use App\Services\Marketplace\MarketplaceApiGateway;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use RuntimeException;

/**
 * Service untuk penarikan data mutasi wallet Shopee (v2.payment.get_wallet_transaction_list).
 *
 * Murni bertindak sebagai live reader in-memory tanpa menyentuh atau menulis ke database.
 */
class ShopeeWalletTransactionService
{
    private const SHOPEE_CODES = ['shopee', 'shp', 'SHOPEE'];

    /**
     * Pemetaan tipe transaksi Shopee ke label deskriptif bahasa Indonesia dan default arah dana.
     */
    private const TRANSACTION_TYPE_MAP = [
        '101' => [
            'label' => 'Pelepasan Escrow (Penjualan Selesai)',
            'flow'  => 'MONEY_IN',
            'badge' => 'success',
        ],
        '102' => [
            'label' => 'Penyesuaian Escrow (Minus)',
            'flow'  => 'MONEY_OUT',
            'badge' => 'warning',
        ],
        '201' => [
            'label' => 'Pengajuan Penarikan Saldo ke Bank',
            'flow'  => 'MONEY_OUT',
            'badge' => 'info',
        ],
        '202' => [
            'label' => 'Penarikan Saldo ke Bank Berhasil',
            'flow'  => 'MONEY_OUT',
            'badge' => 'primary',
        ],
        '203' => [
            'label' => 'Penarikan Saldo Dibatalkan (Dikembalikan)',
            'flow'  => 'MONEY_IN',
            'badge' => 'danger',
        ],
        '450' => [
            'label' => 'Biaya Iklan Berbayar Shopee (Paid Ads)',
            'flow'  => 'MONEY_OUT',
            'badge' => 'secondary',
        ],
        '451' => [
            'label' => 'Pengembalian Biaya Iklan Shopee (Refund Ads)',
            'flow'  => 'MONEY_IN',
            'badge' => 'success',
        ],
        'escrow_verified_add' => [
            'label' => 'Pelepasan Escrow (Penjualan Selesai)',
            'flow'  => 'MONEY_IN',
            'badge' => 'success',
        ],
        'escrow_verified_minus' => [
            'label' => 'Penyesuaian Escrow (Minus)',
            'flow'  => 'MONEY_OUT',
            'badge' => 'warning',
        ],
        'withdrawal_created' => [
            'label' => 'Pengajuan Penarikan Saldo ke Bank',
            'flow'  => 'MONEY_OUT',
            'badge' => 'info',
        ],
        'withdrawal_completed' => [
            'label' => 'Penarikan Saldo ke Bank Berhasil',
            'flow'  => 'MONEY_OUT',
            'badge' => 'primary',
        ],
        'withdrawal_cancelled' => [
            'label' => 'Penarikan Saldo Dibatalkan (Dikembalikan)',
            'flow'  => 'MONEY_IN',
            'badge' => 'danger',
        ],
        'paid_ads_charge' => [
            'label' => 'Biaya Iklan Berbayar Shopee (Paid Ads)',
            'flow'  => 'MONEY_OUT',
            'badge' => 'secondary',
        ],
        'paid_ads_refund' => [
            'label' => 'Pengembalian Biaya Iklan Shopee (Refund Ads)',
            'flow'  => 'MONEY_IN',
            'badge' => 'success',
        ],
        'spm_deduct' => [
            'label' => 'Potongan Saldo Iklan / Top Up',
            'flow'  => 'MONEY_OUT',
            'badge' => 'secondary',
        ],
        'fast_escrow_factor_advance' => [
            'label' => 'Pencairan Cepat (Fast Escrow Advance)',
            'flow'  => 'MONEY_IN',
            'badge' => 'success',
        ],
        'fast_escrow_factor_deduct' => [
            'label' => 'Potongan Pencairan Cepat (Fast Escrow Deduct)',
            'flow'  => 'MONEY_OUT',
            'badge' => 'warning',
        ],
        'fbs_adjustment_add' => [
            'label' => 'Penyesuaian Tambah FBS / Shopee Mart',
            'flow'  => 'MONEY_IN',
            'badge' => 'success',
        ],
        'fbs_adjustment_minus' => [
            'label' => 'Penyesuaian Kurang FBS / Shopee Mart',
            'flow'  => 'MONEY_OUT',
            'badge' => 'warning',
        ],
        'manual_adjustment_add' => [
            'label' => 'Penyesuaian Manual Tambah',
            'flow'  => 'MONEY_IN',
            'badge' => 'success',
        ],
        'manual_adjustment_minus' => [
            'label' => 'Penyesuaian Manual Kurang',
            'flow'  => 'MONEY_OUT',
            'badge' => 'warning',
        ],
    ];

    public function __construct(
        protected MarketplaceApiGateway $gateway
    ) {}

    /**
     * Ambil daftar jenis transaksi yang diketahui untuk keperluan filter UI.
     *
     * @return array<int, array{code: string, label: string, flow: string}>
     */
    public function getTransactionTypes(): array
    {
        $types = [];
        $seen = [];

        foreach (self::TRANSACTION_TYPE_MAP as $code => $info) {
            $label = $info['label'];
            if (! isset($seen[$label])) {
                $seen[$label] = true;
                $types[] = [
                    'code'  => (string) $code,
                    'label' => $label,
                    'flow'  => $info['flow'],
                ];
            }
        }

        return $types;
    }

    /**
     * Tarik transaksi wallet dari Shopee secara live.
     * Mendukung otomatisasi pemecahan window (chunking) jika rentang tanggal lebih dari 15 hari.
     *
     * @param Store $store Toko Shopee yang dituju
     * @param Carbon $from Tanggal mulai
     * @param Carbon $to Tanggal selesai
     * @param array{
     *     page_no?: int,
     *     page_size?: int,
     *     money_flow?: ?string,
     *     transaction_type?: ?string,
     *     wallet_type?: ?string,
     *     transaction_tab_type?: ?string,
     *     search?: ?string
     * } $options
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     summary: array{
     *         total_count: int,
     *         total_money_in: float,
     *         total_money_out: float,
     *         net_amount: float,
     *         type_breakdown: array<string, array{label: string, count: int, total_amount: float, badge: string}>
     *     },
     *     pagination: array{
     *         page_no: int,
     *         page_size: int,
     *         more: bool,
     *         next_offset: int
     *     },
     *     raw_response: array
     * }
     */
    public function fetchTransactions(
        Store $store,
        Carbon $from,
        Carbon $to,
        array $options = []
    ): array {
        $this->assertShopee($store);

        $pageNo             = max(0, (int) ($options['page_no'] ?? 0));
        $pageSize           = min(100, max(1, (int) ($options['page_size'] ?? 100)));
        $moneyFlow          = $this->cleanString($options['money_flow'] ?? null);
        $transactionType    = $this->cleanString($options['transaction_type'] ?? null);
        $walletType         = $this->cleanString($options['wallet_type'] ?? null);
        $transactionTabType = $this->cleanString($options['transaction_tab_type'] ?? null);
        $searchKeyword      = $this->cleanString($options['search'] ?? null);

        // Filter money_flow Shopee hanya menerima MONEY_IN atau MONEY_OUT
        if ($moneyFlow !== null && ! in_array(strtoupper($moneyFlow), ['MONEY_IN', 'MONEY_OUT'], true)) {
            $moneyFlow = null;
        } else {
            $moneyFlow = $moneyFlow !== null ? strtoupper($moneyFlow) : null;
        }

        // Cek apakah rentang tanggal melebihi 15 hari kalender (Shopee membatasi 15 hari per pemanggilan)
        $diffSeconds = $from->diffInSeconds($to);
        $isMultiChunk = $diffSeconds > 15 * 86400;

        $rawRows = [];
        $more = false;
        $lastRawResponse = [];

        if (! $isMultiChunk) {
            // Rentang standar <= 15 hari: lakukan single API request langsung
            $response = $this->gateway->getWalletTransactionList(
                $store,
                $pageNo,
                $pageSize,
                $from->timestamp,
                $to->timestamp,
                $moneyFlow,
                $transactionType,
                $walletType,
                $transactionTabType,
            );

            $this->assertSuccessfulResponse($response);
            $root = $this->extractResponseRoot($response);
            $rawRows = $this->extractTransactionList($root);
            $more = (bool) ($root['more'] ?? false);
            $lastRawResponse = $response;
        } else {
            // Rentang > 15 hari: pecah menjadi irisan maksimal 14 hari berturut-turut
            $periodTo = $to->copy()->endOfDay();
            $chunkFrom = $from->copy()->startOfDay();

            while ($chunkFrom->lte($periodTo)) {
                $chunkTo = $chunkFrom->copy()->addDays(14)->endOfDay();
                if ($chunkTo->gt($periodTo)) {
                    $chunkTo = $periodTo->copy();
                }

                $chunkOffset = 0;
                do {
                    $res = $this->gateway->getWalletTransactionList(
                        $store,
                        $chunkOffset,
                        100,
                        $chunkFrom->timestamp,
                        $chunkTo->timestamp,
                        $moneyFlow,
                        $transactionType,
                        $walletType,
                        $transactionTabType,
                    );

                    $this->assertSuccessfulResponse($res);
                    $root = $this->extractResponseRoot($res);
                    $chunkRows = $this->extractTransactionList($root);
                    $rawRows = array_merge($rawRows, $chunkRows);
                    $chunkMore = (bool) ($root['more'] ?? false);
                    $chunkOffset += count($chunkRows);
                    $lastRawResponse = $res;
                } while ($chunkMore && count($chunkRows) > 0);

                $chunkFrom = $chunkTo->copy()->addSecond()->startOfDay();
            }

            // Urutkan seluruh baris transaksi berdasarkan create_time terbaru
            usort($rawRows, function ($a, $b) {
                return (int) data_get($b, 'create_time', 0) <=> (int) data_get($a, 'create_time', 0);
            });
        }

        $items = [];
        $totalIn = 0.0;
        $totalOut = 0.0;
        $typeBreakdown = [];

        foreach ($rawRows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $normalized = $this->normalizeTransactionRow($row, $store);

            // Filter lokal 1: Arah Dana (money_flow)
            if ($moneyFlow !== null && $normalized['money_flow'] !== $moneyFlow) {
                continue;
            }

            // Filter lokal 2: Tipe Transaksi (Shopee API terkadang mengembalikan semua mutasi walau dikirim parameter)
            if ($transactionType !== null) {
                if (! $this->matchesTransactionType($normalized['transaction_type'], $transactionType)) {
                    continue;
                }
            }

            // Filter lokal 3: Kata Kunci Pencarian (Order SN, Transaction ID, Keterangan, Nama Pembeli)
            if ($searchKeyword !== null) {
                if (! $this->matchesSearchKeyword($normalized, $searchKeyword)) {
                    continue;
                }
            }

            $items[] = $normalized;

            $amount = $normalized['amount'];
            if ($normalized['money_flow'] === 'MONEY_IN') {
                $totalIn += $amount;
            } else {
                $totalOut += $amount;
            }

            $typeKey = $normalized['transaction_type'] ?: 'UNKNOWN';
            if (! isset($typeBreakdown[$typeKey])) {
                $typeBreakdown[$typeKey] = [
                    'type'         => $typeKey,
                    'label'        => $normalized['transaction_type_label'],
                    'count'        => 0,
                    'total_amount' => 0.0,
                    'badge'        => $normalized['badge'],
                ];
            }
            $typeBreakdown[$typeKey]['count']++;
            $typeBreakdown[$typeKey]['total_amount'] += $amount;
        }

        // Jika multi-chunk, terapkan paginasi lokal atas hasil gabungan
        if ($isMultiChunk) {
            $totalFiltered = count($items);
            $displayedItems = array_slice($items, $pageNo, $pageSize);
            $more = ($pageNo + $pageSize) < $totalFiltered;
            $nextOffset = $pageNo + count($displayedItems);
        } else {
            $displayedItems = $items;
            $nextOffset = $pageNo + count($items);
        }

        return [
            'items' => $displayedItems,
            'summary' => [
                'total_count'    => count($items),
                'total_money_in' => round($totalIn, 2),
                'total_money_out'=> round($totalOut, 2),
                'net_amount'     => round($totalIn - $totalOut, 2),
                'type_breakdown' => array_values($typeBreakdown),
            ],
            'pagination' => [
                'page_no'     => $pageNo,
                'page_size'   => $pageSize,
                'more'        => $more,
                'next_offset' => $nextOffset,
            ],
            'raw_response' => $this->sanitizeRawResponse($lastRawResponse),
        ];
    }

    /**
     * Cocokkan tipe transaksi antara data item dan target filter.
     */
    protected function matchesTransactionType(string $itemType, string $targetType): bool
    {
        $normalizedItem = strtolower(trim($itemType));
        $normalizedTarget = strtolower(trim($targetType));

        if ($normalizedItem === $normalizedTarget || str_contains($normalizedItem, $normalizedTarget)) {
            return true;
        }

        // Cocokkan juga kode alias angka (seperti 101, 201, 450)
        $aliasMap = [
            '101' => 'escrow_verified_add',
            '102' => 'escrow_verified_minus',
            '201' => 'withdrawal_created',
            '202' => 'withdrawal_completed',
            '203' => 'withdrawal_cancelled',
            '450' => 'paid_ads_charge',
            '451' => 'paid_ads_refund',
            'spm' => 'spm_deduct',
        ];

        if (isset($aliasMap[$normalizedTarget])) {
            return str_contains($normalizedItem, $aliasMap[$normalizedTarget]);
        }

        return false;
    }

    /**
     * Pencarian kata kunci pada Order SN, ID transaksi, alasan, keterangan, dan pembeli.
     *
     * @param array<string, mixed> $item
     */
    protected function matchesSearchKeyword(array $item, string $keyword): bool
    {
        $kw = strtolower($keyword);

        return str_contains(strtolower((string) ($item['order_sn'] ?? '')), $kw)
            || str_contains(strtolower((string) ($item['transaction_id'] ?? '')), $kw)
            || str_contains(strtolower((string) ($item['reason'] ?? '')), $kw)
            || str_contains(strtolower((string) ($item['buyer_name'] ?? '')), $kw)
            || str_contains(strtolower((string) ($item['transaction_type_label'] ?? '')), $kw)
            || str_contains(strtolower((string) ($item['transaction_type'] ?? '')), $kw);
    }

    /**
     * Normalisasi baris transaksi tunggal ke format standar.
     *
     * @param array<string, mixed> $row
     * @param Store $store
     * @return array<string, mixed>
     */
    public function normalizeTransactionRow(array $row, Store $store): array
    {
        $rawId = data_get($row, 'transaction_id')
            ?? data_get($row, 'withdraw_id')
            ?? data_get($row, 'id')
            ?? '';
        $transactionId = (string) $rawId;

        $createdTime = (int) data_get($row, 'create_time', 0);
        $date = $createdTime > 0
            ? Carbon::createFromTimestamp($createdTime, config('app.timezone'))
            : null;

        $rawAmount = data_get($row, 'amount')
            ?? data_get($row, 'transaction_amount')
            ?? data_get($row, 'withdrawal_amount')
            ?? 0;
        $amount = $this->parseAmount($rawAmount);

        $rawBalance = data_get($row, 'current_balance')
            ?? data_get($row, 'balance')
            ?? null;
        $currentBalance = $rawBalance !== null ? $this->parseAmount($rawBalance) : null;

        $rawType = (string) (data_get($row, 'transaction_type') ?? data_get($row, 'type') ?? '');
        $typeMeta = $this->resolveTypeMetadata($rawType, $rawAmount);

        // Tentukan arah aliran dana: dahulukan field Shopee jika tersedia, fallback ke metadata
        $flow = (string) data_get($row, 'money_flow', '');
        if ($flow === '' || ! in_array(strtoupper($flow), ['MONEY_IN', 'MONEY_OUT'], true)) {
            $flow = (float) $rawAmount < 0 ? 'MONEY_OUT' : $typeMeta['flow'];
        } else {
            $flow = strtoupper($flow);
        }

        $orderSn = trim((string) data_get($row, 'order_sn', ''));

        // Prioritaskan keterangan deskriptif Shopee
        $desc = trim((string) data_get($row, 'description', ''));
        $reasonRaw = trim((string) data_get($row, 'reason', ''));
        $reason = $desc !== '' ? $desc : ($reasonRaw !== '' ? $reasonRaw : '');

        $buyerName = trim((string) data_get($row, 'buyer_name', ''));
        $status = strtoupper(trim((string) data_get($row, 'status', 'COMPLETED')));
        $walletType = (string) (data_get($row, 'wallet_type') ?? 'seller_balance');

        return [
            'transaction_id'         => $transactionId,
            'store_id'               => $store->id,
            'store_name'             => $store->name,
            'create_time'            => $createdTime,
            'date_formatted'         => $date?->format('Y-m-d H:i:s') ?? '—',
            'date_human'             => $date?->translatedFormat('d M Y, H:i') ?? '—',
            'amount'                 => $amount,
            'formatted_amount'       => 'Rp ' . number_format($amount, 0, ',', '.'),
            'signed_amount'          => ($flow === 'MONEY_OUT' ? -1 : 1) * $amount,
            'signed_formatted_amount'=> ($flow === 'MONEY_OUT' ? '- Rp ' : '+ Rp ') . number_format($amount, 0, ',', '.'),
            'current_balance'        => $currentBalance,
            'formatted_balance'      => $currentBalance !== null ? 'Rp ' . number_format($currentBalance, 0, ',', '.') : '—',
            'money_flow'             => $flow,
            'transaction_type'       => $rawType,
            'transaction_type_label' => $typeMeta['label'],
            'badge'                  => $typeMeta['badge'],
            'order_sn'               => $orderSn !== '' ? $orderSn : null,
            'buyer_name'             => $buyerName !== '' ? $buyerName : null,
            'reason'                 => $reason !== '' ? $reason : null,
            'status'                 => $status,
            'wallet_type'            => $walletType,
            'raw_payload'            => $row,
        ];
    }

    /**
     * Memastikan toko adalah toko Shopee.
     */
    protected function assertShopee(Store $store): void
    {
        $code = strtolower((string) ($store->channel?->code ?? ''));
        if (! in_array($code, self::SHOPEE_CODES, true)) {
            throw new RuntimeException("Toko #{$store->id} ({$store->name}) bukan channel Shopee.");
        }
    }

    /**
     * Memastikan response API Shopee tidak error.
     */
    protected function assertSuccessfulResponse(array $response): void
    {
        $error = (string) ($response['error'] ?? '');
        if ($error !== '') {
            $msg = (string) ($response['message'] ?? $error);
            throw new RuntimeException("Shopee API Error: {$msg} ({$error})");
        }
    }

    /**
     * Ekstrak root response data dari Shopee.
     */
    protected function extractResponseRoot(array $response): array
    {
        if (isset($response['response']) && is_array($response['response'])) {
            return $response['response'];
        }

        return $response;
    }

    /**
     * Ekstrak daftar transaksi dari root payload Shopee.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function extractTransactionList(array $root): array
    {
        $list = data_get($root, 'transaction_list');
        if (is_array($list)) {
            if (! array_is_list($list) && (isset($list['transaction_id']) || isset($list['amount']))) {
                return [$list];
            }
            return array_values($list);
        }

        return [];
    }

    /**
     * Resolusi metadata tipe transaksi (label bahasa Indonesia dan warna badge).
     *
     * @return array{label: string, flow: string, badge: string}
     */
    protected function resolveTypeMetadata(string $type, mixed $rawAmount): array
    {
        $normalizedType = strtolower(trim($type));

        if (isset(self::TRANSACTION_TYPE_MAP[$normalizedType])) {
            return self::TRANSACTION_TYPE_MAP[$normalizedType];
        }

        // Fallback dinamis jika ada kode baru dari Shopee
        $defaultFlow = (float) $rawAmount < 0 ? 'MONEY_OUT' : 'MONEY_IN';
        $humanLabel = ucwords(str_replace(['_', '-'], ' ', $normalizedType));

        return [
            'label' => $humanLabel !== '' ? $humanLabel : 'Transaksi Lainnya',
            'flow'  => $defaultFlow,
            'badge' => $defaultFlow === 'MONEY_IN' ? 'success' : 'secondary',
        ];
    }

    /**
     * Konversi nilai amount ke nilai absolut positif numerik float.
     */
    protected function parseAmount(mixed $value): float
    {
        if (is_numeric($value)) {
            return abs((float) $value);
        }

        $cleaned = str_replace([',', ' '], '', trim((string) $value));
        return is_numeric($cleaned) ? abs((float) $cleaned) : 0.0;
    }

    /**
     * Bersihkan string kosong atau null.
     */
    protected function cleanString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        return $trimmed !== '' ? $trimmed : null;
    }

    /**
     * Hilangkan internal meta data dari raw response untuk keamanan dan kebersihan output.
     */
    protected function sanitizeRawResponse(array $response): array
    {
        return Arr::except($response, ['_meta']);
    }
}
