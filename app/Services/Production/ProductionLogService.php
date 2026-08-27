<?php

namespace App\Services\Production;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ProductionLogService — timeline READ-ONLY "Log Produksi".
 *
 * Menggabungkan sumber kebenaran yang sudah ada menjadi satu daftar berwaktu:
 *   - inventory_mutations (event stok produksi: cutting/sewing/QC/finishing/setor dadakan/…)
 *   - production_logs (event non-ledger: bersihkan data, dll.)
 *
 * Tidak menulis apa pun. Tidak menduplikasi data. Kalau butuh, journals &
 * production_movements bisa ditambahkan dengan pola sama.
 */
class ProductionLogService
{
    /** Source type mutasi milik alur produksi + label manusiawi. */
    public const SOURCE_LABELS = [
        'cutting_job' => 'Cutting bahan',
        'cutting_job_void' => 'Batal cutting',
        'cutting_job_sisa' => 'Cutting — Sisa kain',
        'cutting_wip' => 'Masuk WIP-CUT',
        'cutting_reject' => 'Reject cutting',
        'cutting_qc_void' => 'Batal QC cutting',
        'cutting_qc_adjust_in' => 'Penyesuaian masuk',
        'cutting_qc_adjust_out' => 'Penyesuaian keluar',
        'App\\Models\\SewingPickup' => 'Ambil jahit',
        'sewing_pickup_supply' => 'Kelengkapan jahit',
        'sewing_pickup_supply_followup' => 'Kelengkapan menyusul',
        'sewing_pickup_supply_void_line' => 'Batal kelengkapan',
        'sewing_qc_in' => 'Setor jahit',
        'sewing_qc_reject' => 'Reject jahit',
        'sewing_return_ok' => 'Setor jahit',
        'sewing_return_reject' => 'Setor jahit reject',
        'sewing_return_void_ok' => 'Batal setor jahit',
        'sewing_return_void_reject' => 'Batal setor reject',
        'sewing_reject_rework_ok' => 'Setor ulang jahit',
        'sewing_reject_rework_void' => 'Batal setor ulang',
        'finishing_bom' => 'Pakai bahan finishing',
        'finishing_job' => 'Finishing jadi',
        'finishing_qc_in_fg' => 'Masuk barang jadi',
        'finishing_qc_reject' => 'Reject finishing',
        'finishing_reject_convert' => 'Ubah reject',
        'finishing_repair' => 'Perbaikan finishing',
        'stock_request' => 'Setor dadakan',
        'stock_request_void' => 'Batal setor dadakan',
        'production_movement' => 'Pindah proses',
        'bundle_assembly' => 'Assembly bundle',
        'bundle_assembly_void' => 'Batal assembly bundle',
        'wip_fin_adjustment' => 'Sesuaikan WIP-FIN',
        'wip_normalization' => 'Rapikan WIP',
        'wip_cleanup' => 'Bersihkan WIP',
        'App\\Models\\CuttingJob' => 'Cutting',
        'App\\Models\\SewingReturn' => 'Setor jahit',
        'App\\Models\\FinishingJob' => 'Finishing',
        'App\\Models\\PackingJob' => 'Packing',
    ];

    /** Event non-ledger (production_logs). */
    public const EVENT_LABELS = [
        'clean_production' => 'Bersihkan Data Produksi',
        'qc_cancelled' => 'QC Dibatalkan',
    ];

    public function sourceTypes(): array
    {
        return array_keys(self::SOURCE_LABELS);
    }

    /**
     * Timeline gabungan, terbaru dulu.
     *
     * @param  array{date_from?:string,date_to?:string,source?:string,q?:string}  $f
     */
    public function timeline(array $f = [], int $limit = 200): Collection
    {
        $rows = collect();

        // created_by baru ada setelah migrasi → guard.
        $hasActor = Schema::hasColumn('inventory_mutations', 'created_by');

        // 1) Event stok dari inventory_mutations (source produksi).
        $mut = DB::table('inventory_mutations as m')
            ->leftJoin('items as i', 'i.id', '=', 'm.item_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'm.warehouse_id')
            ->whereIn('m.source_type', $this->sourceTypes());

        if ($hasActor) {
            $mut->leftJoin('users as mu', 'mu.id', '=', 'm.created_by');
        }

        if (! empty($f['source'])) {
            $mut->where('m.source_type', $f['source']);
        }
        if (! empty($f['date_from'])) {
            $mut->whereDate('m.date', '>=', $f['date_from']);
        }
        if (! empty($f['date_to'])) {
            $mut->whereDate('m.date', '<=', $f['date_to']);
        }
        if (! empty($f['q'])) {
            $q = trim($f['q']);
            $mut->where(function ($x) use ($q) {
                $x->where('i.code', 'like', "%{$q}%")
                    ->orWhere('i.name', 'like', "%{$q}%")
                    ->orWhere('m.notes', 'like', "%{$q}%");
            });
        }

        $mut->selectRaw("
                'stock' as kind,
                m.id as id,
                m.date as date,
                m.created_at as ts,
                m.source_type as source_type,
                m.source_id as source_id,
                i.code as item_code,
                i.name as item_name,
                w.code as warehouse_code,
                m.qty_change as qty,
                m.direction as direction,
                m.total_cost as value,
                m.notes as notes,
                ".($hasActor ? 'mu.name' : 'NULL').' as actor
            ')
            ->orderByDesc('m.id')
            ->limit($limit)
            ->get()
            ->each(fn ($r) => $rows->push($r));

        // 2) Event non-ledger dari production_logs.
        if (Schema::hasTable('production_logs') && empty($f['source'])) {
            $logs = DB::table('production_logs as p')
                ->leftJoin('users as u', 'u.id', '=', 'p.actor_id');
            if (! empty($f['date_from'])) {
                $logs->whereDate('p.created_at', '>=', $f['date_from']);
            }
            if (! empty($f['date_to'])) {
                $logs->whereDate('p.created_at', '<=', $f['date_to']);
            }
            if (! empty($f['q'])) {
                $logs->where('p.summary', 'like', '%'.trim($f['q']).'%');
            }
            $logs->selectRaw("
                    'event' as kind,
                    p.id as id,
                    p.created_at as date,
                    p.created_at as ts,
                    p.event as source_type,
                    p.source_id as source_id,
                    NULL as item_code,
                    NULL as item_name,
                    NULL as warehouse_code,
                    NULL as qty,
                    NULL as direction,
                    NULL as value,
                    p.summary as notes,
                    u.name as actor
                ")
                ->orderByDesc('p.id')
                ->limit($limit)
                ->get()
                ->each(fn ($r) => $rows->push($r));
        }

        return $rows
            ->sortByDesc(fn ($r) => (string) ($r->ts ?? $r->date))
            ->take($limit)
            ->values();
    }

    public function label(string $key): string
    {
        return self::SOURCE_LABELS[$key] ?? self::EVENT_LABELS[$key] ?? $key;
    }
}
