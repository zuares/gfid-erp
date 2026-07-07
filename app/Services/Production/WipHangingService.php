<?php

namespace App\Services\Production;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * WipHangingService — DETEKSI WIP MENGGANTUNG (READ-ONLY).
 *
 * Service ini HANYA membaca. Tidak menulis stok, tidak menyentuh
 * inventory_mutations / inventory_stocks / cutting_job_bundles.
 * Dipakai halaman preview "WIP Cleanup" sebelum ada aksi apa pun.
 *
 * Sumber WIP bersifat kombinasi (lihat docs/AUDIT_WIP_PRODUCTION.md):
 *   - WIP-CUT  : cutting_job_bundles.cut_wip_qty (level bundle)
 *   - WIP-SEW  : sewing_pickup_lines (ditarik vs disetor) + inventory_stocks
 *   - WIP-*    : inventory_stocks (level item)
 *   - QC       : qc_results status pending
 */
class WipHangingService
{
    /** Ambang umur (hari) untuk menandai WIP "menua". */
    public const AGE_WARN_DAYS = 14;

    /**
     * Bundle cutting yang sudah siap jahit tapi belum ditarik semua.
     * cut_wip_qty > 0 AND sewing_picked_qty < cut_wip_qty
     */
    public function cutBundlesOutstanding(): Collection
    {
        return DB::table('cutting_job_bundles as b')
            ->leftJoin('items as i', 'i.id', '=', 'b.finished_item_id')
            ->leftJoin('employees as e', 'e.id', '=', 'b.operator_id')
            ->leftJoin('cutting_jobs as cj', 'cj.id', '=', 'b.cutting_job_id')
            ->whereRaw('COALESCE(b.cut_wip_qty,0) > 0.01')
            ->whereRaw('COALESCE(b.sewing_picked_qty,0) < COALESCE(b.cut_wip_qty,0) - 0.01')
            ->selectRaw("
                b.id as bundle_id,
                b.bundle_code,
                cj.id as cutting_job_id,
                cj.code as cutting_code,
                i.code as item_code,
                i.name as item_name,
                b.finished_item_id as item_id,
                (COALESCE(b.cut_wip_qty,0) - COALESCE(b.sewing_picked_qty,0)) as qty_outstanding,
                b.cut_wip_qty,
                b.sewing_picked_qty,
                e.name as operator_name,
                b.operator_id,
                b.status,
                b.created_at,
                CAST(julianday('now') - julianday(b.created_at) AS INTEGER) as age_days
            ")
            ->orderByDesc('age_days')
            ->get();
    }

    /**
     * Baris ambil-jahit yang belum lengkap disetor.
     * qty_bundle > qty_returned_ok + qty_returned_reject (dan belum void)
     */
    public function pickupsNotReturned(): Collection
    {
        // qty_closed baru ada setelah migrasi WIP pickup-close → guard.
        $closedExpr = \Illuminate\Support\Facades\Schema::hasColumn('sewing_pickup_lines', 'qty_closed')
            ? 'COALESCE(l.qty_closed,0)'
            : '0';

        return DB::table('sewing_pickup_lines as l')
            ->join('sewing_pickups as p', 'p.id', '=', 'l.sewing_pickup_id')
            ->leftJoin('items as i', 'i.id', '=', 'l.finished_item_id')
            ->leftJoin('employees as e', 'e.id', '=', 'p.operator_id')
            ->leftJoin('cutting_job_bundles as b', 'b.id', '=', 'l.cutting_job_bundle_id')
            ->whereNull('l.voided_at')
            ->whereNull('p.voided_at')
            ->whereRaw("COALESCE(l.qty_bundle,0) > COALESCE(l.qty_returned_ok,0) + COALESCE(l.qty_returned_reject,0) + {$closedExpr} + 0.01")
            ->selectRaw("
                l.id as pickup_line_id,
                p.id as pickup_id,
                p.code as pickup_code,
                p.date as pickup_date,
                i.code as item_code,
                i.name as item_name,
                l.finished_item_id as item_id,
                b.bundle_code,
                l.cutting_job_bundle_id as bundle_id,
                l.qty_bundle,
                (COALESCE(l.qty_returned_ok,0) + COALESCE(l.qty_returned_reject,0) + {$closedExpr}) as qty_returned,
                (COALESCE(l.qty_bundle,0) - COALESCE(l.qty_returned_ok,0) - COALESCE(l.qty_returned_reject,0) - {$closedExpr}) as qty_outstanding,
                e.name as operator_name,
                p.operator_id,
                CAST(julianday('now') - julianday(p.date) AS INTEGER) as age_days
            ")
            ->orderByDesc('age_days')
            ->get();
    }

    /**
     * Residu stok item-level di gudang WIP (WIP-SEW/FIN/PACK/CUT).
     * Tidak punya konteks bundle/operator → kandidat normalisasi.
     */
    public function wipStockResidual(): Collection
    {
        return DB::table('inventory_stocks as s')
            ->join('warehouses as w', 'w.id', '=', 's.warehouse_id')
            ->leftJoin('items as i', 'i.id', '=', 's.item_id')
            ->where('w.code', 'like', 'WIP-%')
            ->whereRaw('COALESCE(s.qty,0) > 0.01')
            ->selectRaw("
                s.id as stock_id,
                w.code as warehouse_code,
                w.name as warehouse_name,
                s.warehouse_id,
                i.code as item_code,
                i.name as item_name,
                s.item_id,
                s.qty,
                CAST(julianday('now') - julianday(s.updated_at) AS INTEGER) as age_days
            ")
            ->orderBy('w.code')
            ->orderByDesc('s.qty')
            ->get();
    }

    /**
     * QC yang masih pending (berpotensi menahan WIP).
     */
    public function qcPending(): Collection
    {
        return DB::table('qc_results as q')
            ->leftJoin('cutting_job_bundles as b', 'b.id', '=', 'q.cutting_job_bundle_id')
            ->leftJoin('items as i', 'i.id', '=', 'b.finished_item_id')
            ->leftJoin('employees as e', 'e.id', '=', 'q.operator_id')
            ->where('q.status', 'pending')
            ->selectRaw("
                q.id as qc_id,
                q.stage,
                b.bundle_code,
                q.cutting_job_bundle_id as bundle_id,
                i.code as item_code,
                i.name as item_name,
                q.qty_ok,
                q.qty_reject,
                e.name as operator_name,
                q.qc_date,
                CAST(julianday('now') - julianday(q.qc_date) AS INTEGER) as age_days
            ")
            ->orderByDesc('age_days')
            ->get();
    }

    /**
     * Ringkasan per kategori untuk header preview.
     *
     * @return array<string,array{label:string,rows:int,qty:float}>
     */
    public function summary(): array
    {
        $cut     = $this->cutBundlesOutstanding();
        $pickup  = $this->pickupsNotReturned();
        $stock   = $this->wipStockResidual();
        $qc      = $this->qcPending();

        return [
            'cut_outstanding' => [
                'label' => 'Cut belum ditarik jahit',
                'rows'  => $cut->count(),
                'qty'   => (float) $cut->sum('qty_outstanding'),
            ],
            'pickup_open' => [
                'label' => 'Jahit belum disetor lengkap',
                'rows'  => $pickup->count(),
                'qty'   => (float) $pickup->sum('qty_outstanding'),
            ],
            'wip_stock' => [
                'label' => 'Residu stok gudang WIP',
                'rows'  => $stock->count(),
                'qty'   => (float) $stock->sum('qty'),
            ],
            'qc_pending' => [
                'label' => 'QC pending',
                'rows'  => $qc->count(),
                'qty'   => (float) ($qc->sum('qty_ok') + $qc->sum('qty_reject')),
            ],
        ];
    }
}
