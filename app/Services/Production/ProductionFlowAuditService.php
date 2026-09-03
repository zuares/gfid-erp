<?php

namespace App\Services\Production;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-only audit untuk alur cutting sampai QC sewing.
 *
 * Audit ini sengaja tidak memperbaiki data. Ia membandingkan angka dokumen
 * (bundle, pickup, QC, return line) dengan inventory_mutations per bundle,
 * lalu menandai temuan yang bisa ditangani oleh command repair yang sudah ada
 * atau yang perlu investigasi manual.
 */
class ProductionFlowAuditService
{
    private const EPS = 0.0001;

    private const MOVEMENT_TYPES = [
        'cutting_job',
        'cutting_wip',
        'cutting_reject',
        'App\\Models\\SewingPickup',
        'sewing_qc_out',
        'sewing_qc_in',
        'sewing_qc_reject',
        'sewing_reject_rework_ok',
        'sewing_return_void_ok',
        'sewing_return_void_reject',
        'sewing_reject_rework_void',
    ];

    public function audit(?int $bundleId = null, ?string $since = null, int $limit = 200): array
    {
        $bundles = $this->bundles($bundleId, $since);
        $bundleIds = $bundles->pluck('id')->map(fn ($id) => (int) $id)->values();

        if ($bundleIds->isEmpty()) {
            return [
                'scope' => ['bundle_id' => $bundleId, 'since' => $since],
                'summary' => ['bundles' => 0, 'issues' => 0, 'status' => 'PASS'],
                'issues' => [],
                'unassigned_movements' => [],
            ];
        }

        $pickupExpected = $this->pickupExpectedByBundle($bundleIds);
        $reworkKeys = $this->reworkKeys($bundleIds);
        $sewingQc = $this->qcByBundle('sewing', $bundleIds, $reworkKeys);
        $cuttingQc = $this->qcByBundle('cutting', $bundleIds, $reworkKeys);
        $returnLines = $this->returnLinesByBundle($bundleIds);
        $allReturnLines = $this->returnLinesByBundle($bundleIds, false);
        $pickupCounters = $this->pickupCountersByBundle($bundleIds);
        $movements = $this->movementByBundle($bundleIds, $reworkKeys);

        $issues = [];

        foreach ($bundles as $bundle) {
            $id = (int) $bundle->id;
            $code = (string) ($bundle->bundle_code ?: '#' . $id);
            $item = (string) ($bundle->item_code ?: '-');

            $cutOk = $this->number($bundle->qty_qc_ok);
            $cutReject = $this->number($bundle->qty_qc_reject);
            $cutQcOk = $cuttingQc[$id]['ok'] ?? null;
            $cutQcReject = $cuttingQc[$id]['reject'] ?? null;

            if ($cutQcOk !== null && $this->different($cutOk, $cutQcOk)) {
                $issues[] = $this->issue(
                    'CUTTING_MASTER_QC_MISMATCH', 'HIGH', $id, $code, $item,
                    $cutQcOk, $cutOk,
                    'qty_qc_ok bundle berbeda dari qc_results cutting.',
                    'Periksa QC cutting; tidak aman auto-fix tanpa menentukan sumber angka yang benar.'
                );
            }

            if ($cutQcReject !== null && $this->different($cutReject, $cutQcReject)) {
                $issues[] = $this->issue(
                    'CUTTING_MASTER_REJECT_MISMATCH', 'HIGH', $id, $code, $item,
                    $cutQcReject, $cutReject,
                    'qty_qc_reject bundle berbeda dari qc_results cutting.',
                    'Periksa QC cutting; setelah sumber benar, jalankan repair spesifik.'
                );
            }

            $this->compare(
                $issues, 'CUTTING_WIP_QTY_MISMATCH', 'HIGH', $id, $code, $item,
                $cutOk, $this->movementQty($movements, $id, 'cutting_wip', 'WIP-CUT'),
                'Qty OK cutting tidak sama dengan stok masuk WIP-CUT.',
                'inventory:fix-cutwip-reject hanya untuk kasus reject salah masuk WIP-CUT; selain itu perlu koreksi spesifik.'
            );
            $this->compare(
                $issues, 'CUTTING_REJECT_QTY_MISMATCH', 'HIGH', $id, $code, $item,
                $cutReject, $this->movementQty($movements, $id, 'cutting_reject', 'REJ-CUT'),
                'Qty reject cutting tidak sama dengan stok masuk REJ-CUT.',
                'Periksa inventory:fix-cutwip-reject atau buat adjustment setelah verifikasi fisik.'
            );

            $pickupQty = (float) ($pickupExpected[$id] ?? 0);
            $this->compare(
                $issues, 'PICKUP_WIP_CUT_QTY_MISMATCH', 'HIGH', $id, $code, $item,
                $pickupQty, abs($this->movementQty($movements, $id, 'App\\Models\\SewingPickup', 'WIP-CUT')),
                'Qty ambil jahit tidak sama dengan OUT WIP-CUT.',
                'Periksa pickup line dan ledger; jangan edit mutation langsung.'
            );
            $this->compare(
                $issues, 'PICKUP_WIP_SEW_QTY_MISMATCH', 'HIGH', $id, $code, $item,
                $pickupQty, $this->movementQty($movements, $id, 'App\\Models\\SewingPickup', 'WIP-SEW'),
                'Qty ambil jahit tidak sama dengan IN WIP-SEW.',
                'Periksa pickup line dan ledger; jangan edit mutation langsung.'
            );

            $sewOk = (float) ($sewingQc[$id]['ok'] ?? 0);
            $sewReject = (float) ($sewingQc[$id]['reject'] ?? 0);
            $sewProcessed = $sewOk + $sewReject;
            $reworkOk = (float) ($sewingQc[$id]['rework_ok'] ?? 0);
            $reworkReject = (float) ($sewingQc[$id]['rework_reject'] ?? 0);

            // Pickup boleh masih outstanding/parsial. Yang invalid hanya bila
            // QC normal memproses lebih banyak daripada qty pickup aktif.
            if ($sewProcessed > $pickupQty + self::EPS) {
                $issues[] = $this->issue(
                    'SEWING_QC_TOTAL_MISMATCH', 'HIGH', $id, $code, $item,
                    $pickupQty, $sewProcessed,
                    'Qty QC sewing normal melebihi qty pickup aktif.',
                    'production:repair-sewing-qc-rejects hanya memperbaiki counter/line; selisih inventory perlu investigasi.'
                );
            }
            $this->compare(
                $issues, 'SEWING_QC_OUT_QTY_MISMATCH', 'HIGH', $id, $code, $item,
                $sewProcessed, abs($this->movementQty($movements, $id, 'sewing_qc_out', 'WIP-SEW')),
                'Qty QC OUT tidak sama dengan QC OK + reject.',
                'Periksa dokumen QC; nilai QC OUT harus qty × HPP.'
            );
            $this->compare(
                $issues, 'SEWING_QC_OK_QTY_MISMATCH', 'HIGH', $id, $code, $item,
                $sewOk, $this->movementQty($movements, $id, 'sewing_qc_in'),
                'Qty OK QC tidak sama dengan IN ke gudang hasil.',
                'Periksa dokumen QC dan mutasi tujuan.'
            );
            $this->compare(
                $issues, 'SEWING_QC_REJECT_QTY_MISMATCH', 'HIGH', $id, $code, $item,
                $sewReject, $this->movementQty($movements, $id, 'sewing_qc_reject', 'REJ-SEW'),
                'Qty reject QC tidak sama dengan IN REJ-SEW.',
                'production:repair-sewing-qc-rejects untuk counter/line; jurnal gunakan accounting:repair-defect-reject-journals.'
            );

            $this->compare(
                $issues, 'SEWING_REWORK_QTY_MISMATCH', 'HIGH', $id, $code, $item,
                $reworkOk + $reworkReject,
                abs($this->movementQty($movements, $id, 'sewing_reject_rework_ok', 'REJ-SEW'))
                    + abs($this->movementQty($movements, $id, 'sewing_rework_qc_out', 'REJ-SEW')),
                'Qty setor ulang diproses tidak sama dengan OUT dari REJ-SEW.',
                'Periksa dokumen setor ulang dan mutasi sewing_reject_rework_ok.'
            );

            $lineOk = (float) ($returnLines[$id]['ok'] ?? 0);
            $lineReject = (float) ($returnLines[$id]['reject'] ?? 0);
            $this->compare(
                $issues, 'SEWING_RETURN_LINE_QTY_MISMATCH', 'MEDIUM', $id, $code, $item,
                $sewOk + $sewReject, $lineOk + $lineReject,
                'Qty sewing_return_lines berbeda dari qc_results sewing.',
                'production:repair-sewing-qc-rejects --return=<sewing_return_id>.'
            );

            $reworkLineQty = (float) ($returnLines[$id]['rework_ok'] ?? 0)
                + (float) ($returnLines[$id]['rework_reject'] ?? 0);
            $this->compare(
                $issues, 'SEWING_REWORK_LINE_QTY_MISMATCH', 'MEDIUM', $id, $code, $item,
                $reworkOk + $reworkReject, $reworkLineQty,
                'Qty baris setor ulang berbeda dari hasil QC rework.',
                'Periksa source_reject_return_line_id dan dokumen setor ulang.'
            );

            $counterOk = (float) ($pickupCounters[$id]['ok'] ?? 0);
            $counterReject = (float) ($pickupCounters[$id]['reject'] ?? 0);
            $allLineOk = (float) ($allReturnLines[$id]['ok'] ?? 0);
            $allLineReject = (float) ($allReturnLines[$id]['reject'] ?? 0);
            $this->compare(
                $issues, 'PICKUP_RETURN_COUNTER_MISMATCH', 'MEDIUM', $id, $code, $item,
                $allLineOk + $allLineReject, $counterOk + $counterReject,
                'Counter qty_returned pada pickup line berbeda dari total return line.',
                'production:repair-sewing-qc-rejects --return=<sewing_return_id>.'
            );
        }

        foreach ($this->hppIssues($bundleIds, $limit) as $issue) {
            $issues[] = $issue;
        }

        $issues = collect($issues)->take(max($limit, 1))->values()->all();
        $unassigned = $this->unassignedMovements($since);

        return [
            'scope' => [
                'bundle_id' => $bundleId,
                'since' => $since,
                'bundle_count' => $bundles->count(),
            ],
            'summary' => [
                'bundles' => $bundles->count(),
                'issues' => count($issues),
                'unassigned_movements' => count($unassigned),
                'status' => empty($issues) && empty($unassigned) ? 'PASS' : 'REVIEW',
            ],
            'issues' => $issues,
            'unassigned_movements' => $unassigned,
        ];
    }

    private function bundles(?int $bundleId, ?string $since): Collection
    {
        return DB::table('cutting_job_bundles as b')
            ->join('items as i', 'i.id', '=', 'b.finished_item_id')
            ->join('cutting_jobs as cj', 'cj.id', '=', 'b.cutting_job_id')
            ->when($bundleId, fn ($q) => $q->where('b.id', $bundleId))
            ->when($since, fn ($q) => $q->whereDate('cj.date', '>=', $since))
            ->select([
                'b.id', 'b.bundle_code', 'b.qty_pcs', 'b.qty_qc_ok', 'b.qty_qc_reject',
                'i.code as item_code', 'cj.code as cutting_job_code', 'cj.date as cutting_date',
            ])
            ->orderBy('b.id')
            ->get();
    }

    private function pickupExpectedByBundle(Collection $ids): array
    {
        return DB::table('sewing_pickup_lines as pl')
            ->join('sewing_pickups as p', 'p.id', '=', 'pl.sewing_pickup_id')
            ->whereIn('pl.cutting_job_bundle_id', $ids->all())
            ->whereNull('p.voided_at')
            ->selectRaw('pl.cutting_job_bundle_id as bundle_id, SUM(COALESCE(pl.qty_bundle,0)) as qty')
            ->groupBy('pl.cutting_job_bundle_id')
            ->pluck('qty', 'bundle_id')
            ->map(fn ($value) => (float) $value)
            ->all();
    }

    private function reworkKeys(Collection $ids): array
    {
        return DB::table('sewing_return_lines as rl')
            ->join('sewing_pickup_lines as pl', 'pl.id', '=', 'rl.sewing_pickup_line_id')
            ->whereIn('pl.cutting_job_bundle_id', $ids->all())
            ->whereIn('rl.source_type', ['reject_sewing_rework', 'finishing_sewing_rework'])
            ->get(['rl.sewing_return_id', 'pl.cutting_job_bundle_id'])
            ->mapWithKeys(fn ($row) => [((int) $row->sewing_return_id) . ':' . ((int) $row->cutting_job_bundle_id) => true])
            ->all();
    }

    private function qcByBundle(string $stage, Collection $ids, array $reworkKeys = []): array
    {
        $rows = DB::table('qc_results')
            ->when($stage === 'sewing', function ($query) {
                $query->join('sewing_returns as sr', 'sr.id', '=', 'qc_results.sewing_job_id')
                    ->where('sr.status', 'posted');
            })
            ->where('stage', $stage)
            ->whereIn('cutting_job_bundle_id', $ids->all())
            ->get(['cutting_job_bundle_id as bundle_id', 'sewing_job_id', 'qty_ok', 'qty_reject']);

        $out = [];
        foreach ($rows as $row) {
            $id = (int) $row->bundle_id;
            $isRework = $stage === 'sewing'
                && isset($reworkKeys[((int) $row->sewing_job_id) . ':' . $id]);
            $okKey = $isRework ? 'rework_ok' : 'ok';
            $rejectKey = $isRework ? 'rework_reject' : 'reject';
            $out[$id] ??= ['ok' => 0.0, 'reject' => 0.0, 'rework_ok' => 0.0, 'rework_reject' => 0.0];
            $out[$id][$okKey] += (float) ($row->qty_ok ?? 0);
            $out[$id][$rejectKey] += (float) ($row->qty_reject ?? 0);
        }

        return $out;
    }

    private function returnLinesByBundle(Collection $ids, bool $postedOnly = true): array
    {
        return DB::table('sewing_return_lines as rl')
            ->join('sewing_pickup_lines as pl', 'pl.id', '=', 'rl.sewing_pickup_line_id')
            ->join('sewing_returns as sr', 'sr.id', '=', 'rl.sewing_return_id')
            ->whereIn('pl.cutting_job_bundle_id', $ids->all())
            ->when($postedOnly,
                fn ($q) => $q->where('sr.status', 'posted'),
                fn ($q) => $q->where('sr.status', '<>', 'void')
            )
            ->selectRaw('pl.cutting_job_bundle_id as bundle_id, rl.source_type, SUM(COALESCE(rl.qty_ok,0)) as qty_ok, SUM(COALESCE(rl.qty_reject,0)) as qty_reject')
            ->groupBy('pl.cutting_job_bundle_id', 'rl.source_type')
            ->get()
            ->groupBy('bundle_id')
            ->map(function ($rows) {
                $out = ['ok' => 0.0, 'reject' => 0.0, 'rework_ok' => 0.0, 'rework_reject' => 0.0];
                foreach ($rows as $row) {
                    $prefix = in_array($row->source_type, ['reject_sewing_rework', 'finishing_sewing_rework'], true)
                        ? 'rework_' : '';
                    $out[$prefix . 'ok'] += (float) $row->qty_ok;
                    $out[$prefix . 'reject'] += (float) $row->qty_reject;
                }
                return $out;
            })
            ->all();
    }

    private function pickupCountersByBundle(Collection $ids): array
    {
        return DB::table('sewing_pickup_lines')
            ->whereIn('cutting_job_bundle_id', $ids->all())
            ->selectRaw('cutting_job_bundle_id as bundle_id, SUM(COALESCE(qty_returned_ok,0)) as qty_ok, SUM(COALESCE(qty_returned_reject,0)) as qty_reject')
            ->groupBy('cutting_job_bundle_id')
            ->get()
            ->keyBy('bundle_id')
            ->map(fn ($row) => ['ok' => (float) $row->qty_ok, 'reject' => (float) $row->qty_reject])
            ->all();
    }

    private function movementByBundle(Collection $ids, array $reworkKeys = []): Collection
    {
        return DB::table('inventory_mutations as im')
            ->leftJoin('warehouses as w', 'w.id', '=', 'im.warehouse_id')
            ->whereIn('im.cutting_job_bundle_id', $ids->all())
            ->whereIn('im.source_type', self::MOVEMENT_TYPES)
            ->selectRaw('im.cutting_job_bundle_id as bundle_id, im.source_type, im.source_id, w.code as warehouse_code, SUM(im.qty_change) as qty, SUM(COALESCE(im.total_cost,0)) as value')
            ->groupBy('im.cutting_job_bundle_id', 'im.source_type', 'im.source_id', 'w.code')
            ->get()
            ->map(function ($row) use ($reworkKeys) {
                if ($row->source_type === 'sewing_return_void_ok') {
                    $row->source_type = $row->warehouse_code === 'WIP-SEW'
                        ? 'sewing_qc_out' : 'sewing_qc_in';
                } elseif ($row->source_type === 'sewing_return_void_reject') {
                    $row->source_type = $row->warehouse_code === 'REJ-SEW'
                        ? 'sewing_qc_reject' : 'sewing_qc_out';
                } elseif ($row->source_type === 'sewing_reject_rework_void') {
                    $row->source_type = $row->warehouse_code === 'REJ-SEW'
                        ? 'sewing_rework_qc_out' : 'sewing_rework_qc_in';
                }

                if (in_array($row->source_type, ['sewing_qc_out', 'sewing_qc_in', 'sewing_qc_reject'], true)
                    && isset($reworkKeys[((int) $row->source_id) . ':' . ((int) $row->bundle_id)])) {
                    $row->source_type = match ($row->source_type) {
                        'sewing_qc_out' => 'sewing_rework_qc_out',
                        'sewing_qc_in' => 'sewing_rework_qc_in',
                        default => 'sewing_rework_qc_reject',
                    };
                }
                return $row;
            });
    }

    private function movementQty(Collection $rows, int $bundleId, string $sourceType, ?string $warehouse = null): float
    {
        return (float) $rows
            ->where('bundle_id', $bundleId)
            ->where('source_type', $sourceType)
            ->when($warehouse, fn ($c) => $c->where('warehouse_code', $warehouse))
            ->sum('qty');
    }

    private function hppIssues(Collection $ids, int $limit): array
    {
        return DB::table('inventory_mutations as im')
            ->leftJoin('warehouses as w', 'w.id', '=', 'im.warehouse_id')
            ->leftJoin('items as i', 'i.id', '=', 'im.item_id')
            ->whereIn('im.cutting_job_bundle_id', $ids->all())
            ->whereIn('im.source_type', self::MOVEMENT_TYPES)
            ->whereNotNull('im.unit_cost')
            ->whereNotNull('im.total_cost')
            // Literal numeric epsilon keeps SQLite/MySQL behavior consistent;
            // this is a fixed internal threshold, not user input.
            ->whereRaw('ABS(im.total_cost - (im.qty_change * im.unit_cost)) > 0.01')
            ->orderBy('im.id')
            ->limit(max($limit, 1))
            ->get([
                'im.id', 'im.cutting_job_bundle_id as bundle_id', 'im.source_id', 'im.source_type',
                'im.qty_change', 'im.unit_cost', 'im.total_cost', 'w.code as warehouse_code',
                'i.code as item_code',
            ])
            ->map(fn ($row) => [
                'code' => 'HPP_FORMULA_MISMATCH',
                'severity' => 'HIGH',
                'bundle_id' => (int) $row->bundle_id,
                'bundle_code' => null,
                'item_code' => $row->item_code ?: '-',
                'expected' => round((float) $row->qty_change * (float) $row->unit_cost, 2),
                'actual' => round((float) $row->total_cost, 2),
                'variance' => round((float) $row->total_cost - ((float) $row->qty_change * (float) $row->unit_cost), 2),
                'message' => "Mutation #{$row->id} {$row->source_type} tidak sama dengan qty × unit_cost.",
                'repair' => 'Tidak aman update langsung; buat reversal/adjustment setelah verifikasi sumber HPP.',
                'context' => [
                    'mutation_id' => (int) $row->id,
                    'source_id' => $row->source_id !== null ? (int) $row->source_id : null,
                    'warehouse_code' => $row->warehouse_code,
                ],
            ])
            ->all();
    }

    private function unassignedMovements(?string $since): array
    {
        return DB::table('inventory_mutations as im')
            ->leftJoin('warehouses as w', 'w.id', '=', 'im.warehouse_id')
            ->whereNull('im.cutting_job_bundle_id')
            ->whereIn('im.source_type', self::MOVEMENT_TYPES)
            // RM keluar pada tahap cutting sengaja dicatat per job/lot, bukan
            // per bundle; ia bukan kehilangan tag produksi.
            ->where('im.source_type', '<>', 'cutting_job')
            ->when($since, fn ($q) => $q->whereDate('im.date', '>=', $since))
            ->selectRaw('im.source_type, w.code as warehouse_code, COUNT(*) as mutation_count, SUM(im.qty_change) as qty, SUM(COALESCE(im.total_cost,0)) as value')
            ->groupBy('im.source_type', 'w.code')
            ->orderBy('im.source_type')
            ->get()
            ->map(fn ($row) => [
                'source_type' => $row->source_type,
                'warehouse_code' => $row->warehouse_code,
                'mutation_count' => (int) $row->mutation_count,
                'qty' => (float) $row->qty,
                'value' => (float) $row->value,
            ])
            ->all();
    }

    private function compare(
        array &$issues,
        string $code,
        string $severity,
        int $bundleId,
        string $bundleCode,
        string $itemCode,
        float $expected,
        float $actual,
        string $message,
        string $repair
    ): void {
        if (!$this->different($expected, $actual)) {
            return;
        }

        $issues[] = $this->issue($code, $severity, $bundleId, $bundleCode, $itemCode, $expected, $actual, $message, $repair);
    }

    private function issue(
        string $code,
        string $severity,
        int $bundleId,
        string $bundleCode,
        string $itemCode,
        float $expected,
        float $actual,
        string $message,
        string $repair
    ): array {
        return [
            'code' => $code,
            'severity' => $severity,
            'bundle_id' => $bundleId,
            'bundle_code' => $bundleCode,
            'item_code' => $itemCode,
            'expected' => round($expected, 4),
            'actual' => round($actual, 4),
            'variance' => round($actual - $expected, 4),
            'message' => $message,
            'repair' => $repair,
        ];
    }

    private function different(float $expected, float $actual): bool
    {
        return abs($expected - $actual) > self::EPS;
    }

    private function number(mixed $value): float
    {
        return $value === null || $value === '' ? 0.0 : (float) $value;
    }
}
