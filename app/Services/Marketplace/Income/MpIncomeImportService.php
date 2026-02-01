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

    public function __construct()
    {
        $this->register(app(\App\Services\Marketplace\Income\Adapters\ShopeeIncomeAdapter::class));
        $this->register(app(\App\Services\Marketplace\Income\Adapters\TiktokIncomeAdapter::class));
    }

    public function register(MpIncomeAdapterInterface $adapter): void
    {
        $this->adapters[$adapter->channel()] = $adapter;
    }

    /**
     * Import income/payout report.
     * - Save per-order payout into mp_incomes (source of truth, avoids double count on split shipments)
     * - Optionally apply payout snapshot into mp_shipments (primary shipment only)
     */
    public function import(string $channel, string $path, string $sourceFile, int $storeId, bool $dryRun = false): array
    {
        $channel = strtolower(trim($channel));
        if (!isset($this->adapters[$channel])) {
            throw new \InvalidArgumentException("Income adapter '{$channel}' belum ada.");
        }
        if ($storeId <= 0) {
            throw new \InvalidArgumentException("store_id wajib dan harus > 0.");
        }

        $adapter = $this->adapters[$channel];
        $batchId = (string) Str::uuid();

        $rows = $adapter->parse($path, $sourceFile);

        // Unique per order id (avoid double counting)
        $orderIds = [];
        $rowByOrder = [];
        foreach ($rows as $r) {
            $oid = trim((string) ($r['platform_order_id'] ?? ''));
            if ($oid === '') {
                continue;
            }

            $orderIds[$oid] = true;
            $rowByOrder[$oid] = $r; // last wins
        }
        $uniqueOrderIds = array_keys($orderIds);

        // Pre-check matching to shipments (for stats & optional apply)
        $matchedSet = [];
        if (!empty($uniqueOrderIds)) {
            $matchedSet = MpShipment::query()
                ->where('store_id', $storeId)
                ->where('channel', $channel)
                ->whereIn('platform_order_id', $uniqueOrderIds)
                ->select('platform_order_id')
                ->distinct()
                ->pluck('platform_order_id')
                ->all();
        }
        $matchedLookup = array_fill_keys($matchedSet, true);

        $stats = [
            'channel' => $channel,
            'store_id' => $storeId,
            'source_file' => $sourceFile,
            'batch' => $batchId,

            'rows_parsed' => count($rows),
            'orders_parsed' => count($uniqueOrderIds),

            // mp_incomes
            'incomes_upserted' => 0,

            // shipments matching (informational)
            'orders_matched_shipments' => count($matchedSet),
            'orders_unmatched_shipments' => max(0, count($uniqueOrderIds) - count($matchedSet)),

            // apply-to-shipment (primary only)
            'shipments_updated' => 0,
            'orders_with_multi_shipments' => 0,

            'rows_skipped' => max(0, count($rows) - count($uniqueOrderIds)),
            'dry_run' => $dryRun,
        ];

        if (empty($uniqueOrderIds)) {
            $sample = [];
            return compact('stats', 'sample');
        }

        if ($dryRun) {
            $sample = [];
            foreach (array_slice($uniqueOrderIds, 0, 5) as $oid) {
                $sample[] = $rowByOrder[$oid];
            }
            return compact('stats', 'sample');
        }

        DB::transaction(function () use (
            $uniqueOrderIds,
            $rowByOrder,
            $matchedLookup,
            $channel,
            $storeId,
            $sourceFile,
            $batchId,
            &$stats
        ) {
            foreach ($uniqueOrderIds as $orderId) {
                $r = $rowByOrder[$orderId];

                $fee = (float) ($r['platform_fee_total'] ?? 0);
                $refund = (float) ($r['refund_total'] ?? 0);
                $net = (float) ($r['net_payout_actual'] ?? 0);
                $releasedAt = $r['released_at'] ?? null;

                // released_date (WIB) for reconciliation/reporting
                $releasedDate = null;
                if (!empty($releasedAt)) {
                    $releasedDate = Carbon::parse($releasedAt)
                        ->timezone('Asia/Jakarta')
                        ->toDateString();
                }

                // 1) UPSERT per-order income (source of truth)
                MpIncome::updateOrCreate(
                    [
                        'store_id' => $storeId,
                        'channel' => $channel,
                        'platform_order_id' => $orderId,
                    ],
                    [
                        'released_at' => $releasedAt,
                        'released_date' => $releasedDate, // <-- IMPORTANT
                        'platform_fee_total' => $fee,
                        'refund_total' => $refund,
                        'net_payout_actual' => $net,
                        'currency' => 'IDR',
                        'source_file' => $sourceFile,
                        'import_batch_id' => $batchId,
                        'raw_payload' => [
                            'income' => [
                                'batch' => $batchId,
                                'source_file' => $sourceFile,
                                'platform_fee_total' => $fee,
                                'refund_total' => $refund,
                                'net_payout_actual' => $net,
                                'released_at' => $releasedAt,
                                'released_date' => $releasedDate,
                                'raw' => $r['raw'] ?? null,
                            ],
                        ],
                    ]
                );

                $stats['incomes_upserted']++;

                // 2) OPTIONAL apply to mp_shipments (primary shipment only)
                if (!isset($matchedLookup[$orderId])) {
                    continue;
                }

                // Count shipments under this order (split packages)
                $shipCount = (int) MpShipment::query()
                    ->where('store_id', $storeId)
                    ->where('channel', $channel)
                    ->where('platform_order_id', $orderId)
                    ->count();

                if ($shipCount > 1) {
                    $stats['orders_with_multi_shipments']++;
                }

                // Choose primary shipment: smallest id
                $primary = MpShipment::query()
                    ->where('store_id', $storeId)
                    ->where('channel', $channel)
                    ->where('platform_order_id', $orderId)
                    ->orderBy('id', 'asc')
                    ->first();

                if (!$primary) {
                    continue;
                }

                $updated = MpShipment::query()
                    ->whereKey($primary->id)
                    ->update([
                        'platform_fee_total' => $fee,
                        'refund_total' => $refund,
                        'net_payout_actual' => $net,
                        'released_at' => $releasedAt,
                        'imported_at' => now(),
                        'source_file' => $sourceFile,
                        'import_batch_id' => $batchId,
                    ]);

                $stats['shipments_updated'] += (int) $updated;

                // Merge raw income snapshot into primary raw_payload only
                $raw = $primary->raw_payload ?? [];
                $raw['income'] = [
                    'batch' => $batchId,
                    'source_file' => $sourceFile,
                    'platform_fee_total' => $fee,
                    'refund_total' => $refund,
                    'net_payout_actual' => $net,
                    'released_at' => $releasedAt,
                    'released_date' => $releasedDate,
                    'raw' => $r['raw'] ?? null,
                ];
                $primary->raw_payload = $raw;
                $primary->save();
            }
        });

        return compact('stats');
    }
}
