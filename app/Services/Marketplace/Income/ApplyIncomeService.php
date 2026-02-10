<?php

namespace App\Services\Marketplace\Income;

use App\Models\MpIncome;
use App\Models\MpShipment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ApplyIncomeService
{
    /**
     * Apply dari parsed rows (rowByOrder) ke primary mp_shipment per order.
     *
     * RULE SIMPLE:
     * - net_payout_actual numeric (termasuk 0 / negatif) => APPLY
     * - APPLY => update angka + raw_payload.income + status_norm='delivered'
     * - net_payout_actual kosong / non-numeric => SKIP
     */
    public function applyFromParsedRows(
        int $storeId,
        string $channel,
        array $orderIds,
        array $rowByOrder,
        string $batchId,
        string $sourceFile,
        string $tz = 'Asia/Jakarta'
    ): array {
        $channel = strtolower(trim($channel));
        $now = now()->format('Y-m-d H:i:s');

        if ($storeId <= 0 || $channel === '' || empty($orderIds)) {
            return ['matched_orders' => 0, 'updated_shipments' => 0, 'multi_ship_orders' => 0];
        }

        // primary shipment per order (anti N+1)
        $agg = MpShipment::query()
            ->where('store_id', $storeId)
            ->where('channel', $channel)
            ->whereIn('platform_order_id', $orderIds)
            ->selectRaw('platform_order_id, MIN(id) as primary_id, COUNT(*) as cnt')
            ->groupBy('platform_order_id')
            ->get();

        if ($agg->isEmpty()) {
            return ['matched_orders' => 0, 'updated_shipments' => 0, 'multi_ship_orders' => 0];
        }

        $primaryIdByOrder = [];
        $multi = 0;

        foreach ($agg as $r) {
            $oid = (string) ($r->platform_order_id ?? '');
            $pid = (int) ($r->primary_id ?? 0);
            $cnt = (int) ($r->cnt ?? 0);

            if ($oid === '' || $pid <= 0) {
                continue;
            }

            $primaryIdByOrder[$oid] = $pid;
            if ($cnt > 1) {
                $multi++;
            }

        }

        if (!$primaryIdByOrder) {
            return ['matched_orders' => 0, 'updated_shipments' => 0, 'multi_ship_orders' => 0];
        }

        $updates = [];
        foreach ($primaryIdByOrder as $orderId => $shipId) {
            $r = $rowByOrder[$orderId] ?? null;
            if (!is_array($r)) {
                continue;
            }

            $netRaw = $r['net_payout_actual'] ?? null;
            if ($netRaw === null || $netRaw === '' || !is_numeric($netRaw)) {
                continue; // ✅ simple skip
            }

            $fee = (float) ($r['platform_fee_total'] ?? 0);
            $refund = $this->normalizeRefundTotal($r['refund_total'] ?? null, (float) $netRaw);
            $net = (float) $netRaw;

            $releasedAtStr = $this->dtToString($r['released_at'] ?? null);
            $releasedDateStr = $releasedAtStr
            ? Carbon::parse($releasedAtStr, $tz)->toDateString()
            : null;

            $hint = $r['hint'] ?? [];
            if (!is_array($hint)) {
                $hint = [];
            }

            $incomeSnapshot = [
                'status' => 'applied',
                'batch' => $batchId,
                'source_file' => $sourceFile,
                'platform_fee_total' => $fee,
                'refund_total' => $refund,
                'net_payout_actual' => $net,
                'released_at' => $releasedAtStr,
                'released_date' => $releasedDateStr,
                'hint' => $hint,
                'raw' => $r['raw'] ?? null,
            ];

            $updates[] = [
                'id' => (int) $shipId,

                'platform_fee_total' => $fee,
                'refund_total' => $refund,
                'net_payout_actual' => $net,
                'released_at' => $releasedAtStr,

                'import_batch_id' => $batchId,
                'source_file' => $sourceFile,
                'imported_at' => $now,

                'income_snapshot' => $incomeSnapshot,
            ];
        }

        if (!$updates) {
            return [
                'matched_orders' => count($primaryIdByOrder),
                'updated_shipments' => 0,
                'multi_ship_orders' => $multi,
            ];
        }

        $affected = $this->bulkUpdateShipments($updates, $now);

        return [
            'matched_orders' => count($primaryIdByOrder),
            'updated_shipments' => $affected,
            'multi_ship_orders' => $multi,
        ];
    }

    /**
     * Apply berdasarkan mp_incomes.import_batch_id (kalau kamu butuh tombol "Apply ulang").
     * RULE sama: net_payout_actual numeric => delivered
     */
    public function applyBatch(
        string $batchId,
        ?string $channel = null,
        ?int $storeId = null,
        string $tz = 'Asia/Jakarta'
    ): array {
        $channel = $channel ? strtolower(trim($channel)) : null;

        $incomeBase = MpIncome::query()
            ->where('import_batch_id', $batchId)
            ->when($channel, fn($q) => $q->where('channel', $channel))
            ->when($storeId && $storeId > 0, fn($q) => $q->where('store_id', $storeId));

        $ordersInBatch = (clone $incomeBase)->distinct()->count('platform_order_id');

        if ($ordersInBatch <= 0) {
            return [
                'orders_in_batch' => 0,
                'orders_matched' => 0,
                'orders_with_multi_shipments' => 0,
                'shipments_updated' => 0,
            ];
        }

        // primary shipment per order
        $shipAgg = MpShipment::query()
            ->from('mp_shipments as s')
            ->join('mp_incomes as i', function ($j) {
                $j->on('i.store_id', '=', 's.store_id')
                    ->on('i.channel', '=', 's.channel')
                    ->on('i.platform_order_id', '=', 's.platform_order_id');
            })
            ->where('i.import_batch_id', $batchId)
            ->when($channel, fn($q) => $q->where('i.channel', $channel))
            ->when($storeId && $storeId > 0, fn($q) => $q->where('i.store_id', $storeId))
            ->selectRaw('i.platform_order_id as oid, MIN(s.id) as primary_id, COUNT(*) as cnt')
            ->groupBy('i.platform_order_id')
            ->get();

        $primaryIdByOrder = [];
        $shipCountByOrder = [];

        foreach ($shipAgg as $r) {
            $oid = (string) ($r->oid ?? '');
            if ($oid === '') {
                continue;
            }

            $primaryIdByOrder[$oid] = (int) ($r->primary_id ?? 0);
            $shipCountByOrder[$oid] = (int) ($r->cnt ?? 0);
        }

        $ordersMatched = 0;
        foreach ($primaryIdByOrder as $pid) {
            if ((int) $pid > 0) {
                $ordersMatched++;
            }
        }

        $multi = 0;
        foreach ($shipCountByOrder as $c) {
            if ((int) $c > 1) {
                $multi++;
            }
        }

        $now = now()->format('Y-m-d H:i:s');
        $updated = 0;

        (clone $incomeBase)
            ->select([
                'platform_order_id',
                'platform_fee_total',
                'refund_total',
                'net_payout_actual',
                'released_at',
                'released_date',
                'source_file',
                'raw_payload',
            ])
            ->orderBy('platform_order_id')
            ->chunk(800, function ($incomes) use ($primaryIdByOrder, $batchId, $tz, $now, &$updated) {
                $updates = [];

                foreach ($incomes as $i) {
                    $oid = (string) $i->platform_order_id;
                    $shipId = (int) ($primaryIdByOrder[$oid] ?? 0);
                    if ($shipId <= 0) {
                        continue;
                    }

                    $netRaw = $i->net_payout_actual;
                    if ($netRaw === null || $netRaw === '' || !is_numeric($netRaw)) {
                        continue;
                    }

                    $fee = (float) $i->platform_fee_total;
                    $net = (float) $netRaw;
                    $refund = $this->normalizeRefundTotal($i->refund_total, $net);

                    $releasedAtStr = $this->dtToString($i->released_at);
                    $releasedDateStr = $this->dateToString($i->released_date);
                    if (!$releasedDateStr && $releasedAtStr) {
                        $releasedDateStr = Carbon::parse($releasedAtStr, $tz)->toDateString();
                    }

                    $rawIncome = $this->jsonToArray($i->raw_payload);
                    $incomePayload = is_array($rawIncome['income'] ?? null) ? $rawIncome['income'] : [];
                    $hint = is_array($incomePayload['hint'] ?? null) ? $incomePayload['hint'] : [];

                    $incomeSnapshot = [
                        'status' => 'applied',
                        'batch' => $batchId,
                        'source_file' => (string) ($i->source_file ?? ''),
                        'platform_fee_total' => $fee,
                        'refund_total' => $refund,
                        'net_payout_actual' => $net,
                        'released_at' => $releasedAtStr,
                        'released_date' => $releasedDateStr,
                        'hint' => $hint,
                    ];

                    $updates[] = [
                        'id' => $shipId,
                        'platform_fee_total' => $fee,
                        'refund_total' => $refund,
                        'net_payout_actual' => $net,
                        'released_at' => $releasedAtStr,

                        'import_batch_id' => $batchId,
                        'source_file' => (string) ($i->source_file ?? ''),
                        'imported_at' => $now,

                        'income_snapshot' => $incomeSnapshot,
                    ];
                }

                if (!$updates) {
                    return;
                }

                $updated += $this->bulkUpdateShipments($updates, $now);
            });

        return [
            'orders_in_batch' => $ordersInBatch,
            'orders_matched' => $ordersMatched,
            'orders_with_multi_shipments' => $multi,
            'shipments_updated' => $updated,
        ];
    }

    // =========================================================
    // Bulk updater (SQLite-safe, no N+1)
    // =========================================================

    private function bulkUpdateShipments(array $updates, string $now): int
    {
        $ids = array_values(array_unique(array_filter(array_map(
            fn($r) => (int) ($r['id'] ?? 0),
            $updates
        ))));

        if (!$ids) {
            return 0;
        }

        // Prefetch raw_payload sekali
        $ships = MpShipment::query()
            ->whereIn('id', $ids)
            ->get(['id', 'raw_payload']);

        $rawById = [];
        foreach ($ships as $s) {
            $rawById[(int) $s->id] = $this->jsonToArray($s->raw_payload);
        }

        // Build final rows
        $rows = [];
        foreach ($updates as $u) {
            $id = (int) ($u['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $raw = $rawById[$id] ?? [];
            $raw['income'] = is_array($u['income_snapshot'] ?? null) ? $u['income_snapshot'] : [];
            $rawJson = json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';

            $rows[] = [
                'id' => $id,
                'platform_fee_total' => (float) ($u['platform_fee_total'] ?? 0),
                'refund_total' => (float) ($u['refund_total'] ?? 0),
                'net_payout_actual' => (float) ($u['net_payout_actual'] ?? 0),
                'released_at' => $this->dtToString($u['released_at'] ?? null),

                'import_batch_id' => (string) ($u['import_batch_id'] ?? ''),
                'source_file' => (string) ($u['source_file'] ?? ''),
                'imported_at' => (string) ($u['imported_at'] ?? $now),

                'raw_payload_json' => $rawJson,
            ];
        }

        if (!$rows) {
            return 0;
        }

        $caseFee = $this->caseWhenNumberOptional('id', $rows, 'platform_fee_total', 'platform_fee_total');
        $caseRefund = $this->caseWhenNumberOptional('id', $rows, 'refund_total', 'refund_total');
        $caseNet = $this->caseWhenNumberOptional('id', $rows, 'net_payout_actual', 'net_payout_actual');

        $caseReleased = $this->caseWhenNullableTextOptional('id', $rows, 'released_at', 'released_at');

        $caseBatch = $this->caseWhenTextOptional('id', $rows, 'import_batch_id', 'import_batch_id');
        $caseSource = $this->caseWhenTextOptional('id', $rows, 'source_file', 'source_file');
        $caseImported = $this->caseWhenTextOptional('id', $rows, 'imported_at', 'imported_at');

        $caseRaw = $this->caseWhenTextFromKeyOptional('id', $rows, 'raw_payload_json', 'raw_payload');

        return (int) MpShipment::query()
            ->whereIn('id', $ids)
            ->update([
                'platform_fee_total' => DB::raw($caseFee),
                'refund_total' => DB::raw($caseRefund),
                'net_payout_actual' => DB::raw($caseNet),
                'released_at' => DB::raw($caseReleased),

                'import_batch_id' => DB::raw($caseBatch),
                'source_file' => DB::raw($caseSource),
                'imported_at' => DB::raw($caseImported),

                'raw_payload' => DB::raw($caseRaw),

                // ✅ rule paling simple sesuai request kamu
                'status_norm' => 'delivered',
                'updated_at' => $now,
            ]);
    }

    // =========================================================
    // Helpers
    // =========================================================

    private function normalizeRefundTotal($refundRaw, float $net): float
    {
        // refundRaw numeric => pakai itu (>=0)
        if ($refundRaw !== null && $refundRaw !== '' && is_numeric($refundRaw)) {
            return max(0.0, (float) $refundRaw);
        }

        // refundRaw kosong, tapi net negatif => anggap refund sebesar abs(net)
        if ($net < 0) {
            return abs($net);
        }

        return 0.0;
    }

    private function jsonToArray($val): array
    {
        if (is_array($val)) {
            return $val;
        }

        if (is_string($val)) {
            $v = trim($val);
            if ($v === '') {
                return [];
            }

            $decoded = json_decode($v, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function dtToString($val): ?string
    {
        if ($val instanceof \DateTimeInterface) {
            return $val->format('Y-m-d H:i:s');
        }

        if (is_string($val)) {
            $v = trim($val);
            return $v !== '' ? $v : null;
        }
        return null;
    }

    private function dateToString($val): ?string
    {
        if ($val instanceof \DateTimeInterface) {
            return $val->format('Y-m-d');
        }

        if (is_string($val)) {
            $v = trim($val);
            return $v !== '' ? $v : null;
        }
        return null;
    }

    // =========================================================
    // CASE WHEN builders (SQLite-safe OPTIONAL)
    // =========================================================

    private function caseWhenNumberOptional(string $idCol, array $rows, string $valueKey, string $elseColumn): string
    {
        $parts = ["CASE {$idCol}"];
        $hasWhen = false;

        foreach ($rows as $r) {
            $id = (int) ($r['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $hasWhen = true;
            $val = (float) ($r[$valueKey] ?? 0);
            $parts[] = "WHEN {$id} THEN {$val}";
        }

        if (!$hasWhen) {
            return $elseColumn;
        }

        $parts[] = "ELSE {$elseColumn} END";
        return implode(' ', $parts);
    }

    private function caseWhenTextOptional(string $idCol, array $rows, string $valueKey, string $elseColumn): string
    {
        $parts = ["CASE {$idCol}"];
        $hasWhen = false;

        foreach ($rows as $r) {
            $id = (int) ($r['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $hasWhen = true;
            $val = (string) ($r[$valueKey] ?? '');
            $valSql = "'" . str_replace("'", "''", $val) . "'";
            $parts[] = "WHEN {$id} THEN {$valSql}";
        }

        if (!$hasWhen) {
            return $elseColumn;
        }

        $parts[] = "ELSE {$elseColumn} END";
        return implode(' ', $parts);
    }

    private function caseWhenNullableTextOptional(string $idCol, array $rows, string $valueKey, string $elseColumn): string
    {
        $parts = ["CASE {$idCol}"];
        $hasWhen = false;

        foreach ($rows as $r) {
            $id = (int) ($r['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $hasWhen = true;
            $val = $r[$valueKey] ?? null;

            if ($val === null || $val === '') {
                $parts[] = "WHEN {$id} THEN NULL";
            } else {
                $valSql = "'" . str_replace("'", "''", (string) $val) . "'";
                $parts[] = "WHEN {$id} THEN {$valSql}";
            }
        }

        if (!$hasWhen) {
            return $elseColumn;
        }

        $parts[] = "ELSE {$elseColumn} END";
        return implode(' ', $parts);
    }

    private function caseWhenTextFromKeyOptional(string $idCol, array $rows, string $valueKey, string $elseColumn): string
    {
        $parts = ["CASE {$idCol}"];
        $hasWhen = false;

        foreach ($rows as $r) {
            $id = (int) ($r['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $hasWhen = true;
            $val = (string) ($r[$valueKey] ?? '{}');
            $valSql = "'" . str_replace("'", "''", $val) . "'";
            $parts[] = "WHEN {$id} THEN {$valSql}";
        }

        if (!$hasWhen) {
            return $elseColumn;
        }

        $parts[] = "ELSE {$elseColumn} END";
        return implode(' ', $parts);
    }
}
