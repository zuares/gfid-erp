<?php

namespace App\Services\Marketplace\Income;

use App\Models\MpIncome;
use App\Models\MpShipment;
use App\Services\Marketplace\Income\Adapters\MpIncomeAdapterInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MpIncomeImportService
{
    /** @var array<string, MpIncomeAdapterInterface> */
    protected array $adapters = [];

    public function __construct(
        protected ApplyIncomeService $applyIncome
    ) {
        $this->register(app(\App\Services\Marketplace\Income\Adapters\ShopeeIncomeAdapter::class));
        $this->register(app(\App\Services\Marketplace\Income\Adapters\TiktokIncomeAdapter::class));
    }

    public function register(MpIncomeAdapterInterface $adapter): void
    {
        $this->adapters[$adapter->channel()] = $adapter;
    }

    /**
     * Import income:
     * - parse file
     * - dedupe per platform_order_id (last wins)
     * - upsert ke mp_incomes
     * - langsung apply ke mp_shipments (primary) via ApplyIncomeService
     */
    public function import(
        string $channel,
        string $path,
        string $sourceFile,
        int $storeId,
        bool $dryRun = false,
        string $tz = 'Asia/Jakarta'
    ): array {
        $channel = strtolower(trim($channel));
        if (!isset($this->adapters[$channel])) {
            throw new \InvalidArgumentException("Income adapter '{$channel}' belum ada.");
        }
        if ($storeId <= 0) {
            throw new \InvalidArgumentException("store_id wajib dan harus > 0.");
        }

        $adapter = $this->adapters[$channel];
        $batchId = (string) Str::uuid();

        // 1) Parse
        $rows = $adapter->parse($path, $sourceFile);
        $adapterStats = method_exists($adapter, 'lastStats') ? $adapter->lastStats() : [];

        // 2) Dedupe by platform_order_id (last wins)
        [$uniqueOrderIds, $rowByOrder, $skippedNoOrderId, $skippedDedupe] = $this->dedupeRowsByOrderId($rows);

        $stats = [
            'channel' => $channel,
            'store_id' => $storeId,
            'source_file' => $sourceFile,
            'batch' => $batchId,

            'rows_parsed' => count($rows),
            'orders_parsed' => count($uniqueOrderIds),

            'incomes_upserted' => 0,

            'orders_matched_shipments' => 0,
            'orders_unmatched_shipments' => 0,
            'shipments_updated' => 0,
            'orders_with_multi_shipments' => 0,

            'rows_skipped_no_order_id' => $skippedNoOrderId,
            'rows_skipped_dedupe' => $skippedDedupe,
            'rows_skipped_unlinked_adjustment' => (int) ($adapterStats['rows_skipped_unlinked_adjustment'] ?? 0),
            'rows_skipped' => (int) ($adapterStats['rows_skipped'] ?? $skippedNoOrderId),
            'sheet_name' => (string) ($adapterStats['sheet_name'] ?? ''),
            'header_row' => (int) ($adapterStats['header_row'] ?? 0),

            'dry_run' => $dryRun,
        ];

        if (empty($uniqueOrderIds)) {
            return ['stats' => $stats, 'sample' => []];
        }

        if ($dryRun) {
            $matchedOrderIds = MpShipment::query()
                ->where('store_id', $storeId)
                ->where('channel', $channel)
                ->whereIn('platform_order_id', $uniqueOrderIds)
                ->distinct()
                ->pluck('platform_order_id')
                ->map(fn ($id): string => (string) $id)
                ->all();

            $stats['orders_matched_shipments'] = count($matchedOrderIds);
            $stats['orders_unmatched_shipments'] = max(
                0,
                $stats['orders_parsed'] - $stats['orders_matched_shipments']
            );
            $stats['shipments_updated'] = $stats['orders_matched_shipments'];

            $sample = [];
            foreach (array_slice($uniqueOrderIds, 0, 5) as $oid) {
                $sample[] = $rowByOrder[$oid] ?? [];
            }
            return ['stats' => $stats, 'sample' => $sample];
        }

        $now = now();
        $chunkSize = 800;

        DB::transaction(function () use (
            $uniqueOrderIds,
            $rowByOrder,
            $channel,
            $storeId,
            $sourceFile,
            $batchId,
            $chunkSize,
            $now,
            $tz,
            &$stats
        ) {
            foreach (array_chunk($uniqueOrderIds, $chunkSize) as $orderChunk) {
                // ---------- UPSERT mp_incomes ----------
                $incomeRows = [];

                foreach ($orderChunk as $orderId) {
                    $r = $rowByOrder[$orderId] ?? [];

                    $fee = (float) ($r['platform_fee_total'] ?? 0);
                    $netRaw = $r['net_payout_actual'] ?? null;
                    $net = (is_numeric($netRaw) ? (float) $netRaw : 0.0);

                    // refund: kalau kosong tapi net negatif => abs(net)
                    $refundRaw = $r['refund_total'] ?? null;
                    $refund = 0.0;
                    if ($refundRaw !== null && $refundRaw !== '' && is_numeric($refundRaw)) {
                        $refund = max(0.0, (float) $refundRaw);
                    } elseif ($net < 0) {
                        $refund = abs($net);
                    }

                    $releasedAt = $r['released_at'] ?? null;
                    $releasedDate = null;
                    if (!empty($releasedAt)) {
                        $releasedDate = Carbon::parse($releasedAt, $tz)->toDateString();
                    }

                    $payload = [
                        'income' => [
                            'batch' => $batchId,
                            'source_file' => $sourceFile,
                            'platform_fee_total' => $fee,
                            'refund_total' => $refund,
                            'net_payout_actual' => $netRaw, // simpan mentah juga (biar trace)
                            'released_at' => $releasedAt,
                            'released_date' => $releasedDate,
                            'raw' => $r['raw'] ?? null,
                        ],
                    ];

                    $incomeRows[] = [
                        'store_id' => $storeId,
                        'channel' => $channel,
                        'platform_order_id' => $orderId,

                        'released_at' => $releasedAt,
                        'released_date' => $releasedDate,

                        'platform_fee_total' => $fee,
                        'refund_total' => $refund,
                        'net_payout_actual' => (is_numeric($netRaw) ? (float) $netRaw : 0.0),

                        'currency' => 'IDR',
                        'source_file' => $sourceFile,
                        'import_batch_id' => $batchId,

                        'raw_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),

                        'updated_at' => $now,
                        'created_at' => $now,
                    ];
                }

                MpIncome::query()->upsert(
                    $incomeRows,
                    ['store_id', 'channel', 'platform_order_id'],
                    [
                        'released_at',
                        'released_date',
                        'platform_fee_total',
                        'refund_total',
                        'net_payout_actual',
                        'currency',
                        'source_file',
                        'import_batch_id',
                        'raw_payload',
                        'updated_at',
                    ]
                );

                $stats['incomes_upserted'] += count($incomeRows);

                // ---------- APPLY KE SHIPMENTS (langsung) ----------
                $applyRes = $this->applyIncome->applyFromParsedRows(
                    $storeId,
                    $channel,
                    $orderChunk,
                    $rowByOrder,
                    $batchId,
                    $sourceFile,
                    $tz
                );

                $stats['orders_matched_shipments'] += (int) ($applyRes['matched_orders'] ?? 0);
                $stats['shipments_updated'] += (int) ($applyRes['updated_shipments'] ?? 0);
                $stats['orders_with_multi_shipments'] += (int) ($applyRes['multi_ship_orders'] ?? 0);
            }
        });

        $stats['orders_unmatched_shipments'] = max(0, $stats['orders_parsed'] - $stats['orders_matched_shipments']);

        return ['stats' => $stats];
    }

    private function dedupeRowsByOrderId(array $rows): array
    {
        $orderIds = [];
        $rowByOrder = [];
        $skippedNoOrderId = 0;
        $skippedDedupe = 0;

        foreach ($rows as $r) {
            $oid = trim((string) ($r['platform_order_id'] ?? ''));
            if ($oid === '') {
                $skippedNoOrderId++;
                continue;
            }

            if (isset($orderIds[$oid])) {
                $skippedDedupe++; // ada duplikat, last wins
            }

            $orderIds[$oid] = true;
            $rowByOrder[$oid] = $r;
        }

        return [array_keys($orderIds), $rowByOrder, $skippedNoOrderId, $skippedDedupe];
    }
}
