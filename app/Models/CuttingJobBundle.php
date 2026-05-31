<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuttingJobBundle extends Model
{
    /**
     * GUARD (proteksi ganda) untuk kolom cutting-WIP.
     *
     * Invarian: cut_wip_warehouse_id HANYA boleh null atau menunjuk ke gudang
     * berkode WIP-CUT. Kolom ini milik tahap cutting; tahap hilir (retur jahit,
     * finishing) TIDAK boleh mengarahkannya ke gudang lain (mis. WH-PRD) —
     * itulah yang dulu membuat sisa cutting hilang dari halaman Ambil Jahit.
     *
     * Kalau ada kode (sekarang / di masa depan) mencoba mengeset kolom ini ke
     * gudang non-WIP-CUT, simpan akan ditolak dengan pesan jelas.
     */
    protected static ?int $wipCutWarehouseIdCache = null;

    protected static function booted(): void
    {
        static::saving(function (self $bundle) {
            // hanya cek bila kolom ini berubah & tidak null
            if (!$bundle->isDirty('cut_wip_warehouse_id')) {
                return;
            }

            $value = $bundle->cut_wip_warehouse_id;
            if ($value === null) {
                return; // null = belum/diset-ulang (reset cutting) → boleh
            }

            $wipCutId = static::$wipCutWarehouseIdCache
                ??= (int) (Warehouse::where('code', 'WIP-CUT')->value('id') ?? 0);

            if ($wipCutId > 0 && (int) $value !== $wipCutId) {
                throw new \RuntimeException(
                    "cut_wip_warehouse_id bundle #{$bundle->id} hanya boleh gudang WIP-CUT "
                    . "(id {$wipCutId}), bukan id {$value}. Kolom cutting-WIP tidak boleh "
                    . 'ditimpa oleh tahap jahit/finishing.'
                );
            }
        });
    }

    // ------------------------------------------------------------------
    // BASIC SETUP
    // ------------------------------------------------------------------

    protected $fillable = [
        'cutting_job_id',
        'bundle_code',
        'bundle_no',
        'lot_id',
        'finished_item_id',
        'item_category_id',
        'qty_pcs',
        'qty_used_fabric',
        'operator_id',
        'status',
        'notes',
        'qty_qc_ok',
        'qty_qc_reject',
        'wip_warehouse_id',
        'wip_qty',
        'cut_wip_warehouse_id',
        'cut_wip_qty',
        'sewing_picked_qty',
    ];

    protected $casts = [
        'qty_pcs' => 'float',
        'qty_used_fabric' => 'float',
        'qty_qc_ok' => 'float',
        'qty_qc_reject' => 'float',
        'wip_qty' => 'float',
        'cut_wip_qty' => 'float',
        'sewing_picked_qty' => 'float',

        // kalau di tabel memang ada kolom tanggal di bundle,
        // ini akan otomatis di-cast ke Carbon.
        // kalau tidak ada, tidak masalah, hanya tidak kepakai.
        'date' => 'date',
    ];

    // ------------------------------------------------------------------
    // RELATIONSHIPS
    // ------------------------------------------------------------------

    /**
     * Header Cutting Job.
     */
    public function cuttingJob(): BelongsTo
    {
        return $this->belongsTo(CuttingJob::class, 'cutting_job_id');
    }

    /**
     * Item jadi hasil cutting (FG).
     */
    public function finishedItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'finished_item_id');
    }

    /**
     * Alias umum ke Item (kalau suatu saat butuh).
     */
    public function item(): BelongsTo
    {
        // Bisa kamu ganti ke finished_item_id kalau mau konsisten.
        return $this->belongsTo(Item::class, 'finished_item_id');
    }

    /**
     * LOT kain sumber bundle ini.
     */
    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    /**
     * Operator cutting.
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'operator_id');
    }

    /**
     * Warehouse tempat WIP bundle ini berada (WIP-CUT / WIP-FIN).
     */
    public function wipWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'wip_warehouse_id');
    }

    /**
     * Warehouse WIP hasil cutting (normalnya WIP-CUT).
     *
     * Berbeda dengan wipWarehouse(): kolom ini DI-SET SEKALI saat QC Cutting dan
     * TIDAK pernah ditimpa oleh tahap jahit/finishing — jadi sisa cutting tidak
     * pernah "hilang" dari halaman Ambil Jahit.
     */
    public function cutWipWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'cut_wip_warehouse_id');
    }

    /**
     * Kategori item (misal: TSHIRT, HOODIE, dst).
     */
    public function itemCategory(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'item_category_id');
    }

    /**
     * Semua hasil QC (cutting / sewing / finishing) untuk bundle ini.
     */
    public function qcResults()
    {
        return $this->hasMany(QcResult::class, 'cutting_job_bundle_id');
    }

    /**
     * QC Cutting terakhir untuk bundle ini.
     */
    public function latestCuttingQc()
    {
        return $this->qcResults()
            ->where('stage', 'cutting')
            ->orderByDesc('qc_date')
            ->orderByDesc('id')
            ->limit(1);
    }

    /**
     * Kalau mau ambil khusus QC Cutting via relasi.
     * (Scope cutting() biasanya ada di model QcResult.)
     */
    public function qcCutting()
    {
        return $this->qcResults()->cutting();
    }

    // ------------------------------------------------------------------
    // ACCESSORS (ATTRIBUTE LOGIC)
    // ------------------------------------------------------------------

    /**
     * Saldo WIP untuk finishing (dipakai di modul Finishing).
     */
    public function getWipFinBalanceAttribute(): float
    {
        return (float) ($this->wip_qty ?? 0);
    }

    /**
     * Qty OK hasil Cutting untuk bundle ini.
     *
     * Urutan prioritas:
     * 1. qty_qc_ok (kolom di bundle, kalau sudah di-sync dari QC)
     * 2. QC Cutting terakhir (qc_results.stage = cutting)
     * 3. qty_pcs (fallback kalau belum ada QC sama sekali)
     */
    public function getQtyCuttingOkAttribute(): float
    {
        // 1. kalau sudah disimpan di kolom → pakai itu
        if (!is_null($this->attributes['qty_qc_ok'] ?? null)) {
            return (float) $this->attributes['qty_qc_ok'];
        }

        // 2. coba ambil dari latestCuttingQc
        if ($this->relationLoaded('latestCuttingQc')) {
            $qc = $this->latestCuttingQc->first();
        } else {
            $qc = $this->latestCuttingQc()->first();
        }

        if ($qc && $qc->qty_ok !== null) {
            return (float) $qc->qty_ok;
        }

        // 3. fallback: qty_pcs
        return (float) ($this->attributes['qty_pcs'] ?? 0);
    }

    /**
     * Sisa bundle yang masih boleh dipick ke sewing
     * TANPA memperhatikan WIP (versi lama).
     *
     * Dipakai kalau kamu masih butuh hitung "logis" dari sisi QC saja:
     * max(qty_cutting_ok - sewing_picked_qty, 0)
     */
    public function getQtyRemainingForSewingAttribute(): float
    {
        $maxOk = $this->qty_cutting_ok;
        $picked = (float) ($this->sewing_picked_qty ?? 0);

        return max($maxOk - $picked, 0);
    }

    /**
     * Qty READY untuk sewing dengan memperhitungkan WIP-CUT.
     *
     * Rumus:
     *   ready = max(0, min(qty_cutting_ok, cut_wip_qty) - sewing_picked_qty)
     *
     * Artinya:
     * - Tidak boleh melebihi hasil QC (qty_cutting_ok).
     * - Tidak boleh melebihi stok WIP cutting (cut_wip_qty di gudang WIP-CUT).
     * - Dikurangi qty yang sudah pernah dipick ke sewing.
     *
     * CATATAN: sengaja pakai cut_wip_qty (bukan wip_qty) karena wip_qty bisa
     * ditimpa tahap hilir (retur jahit / finishing). cut_wip_qty kebal dari itu.
     */
    public function getQtyReadyForSewingAttribute(): float
    {
        // FASE 3: sumber ledger → kesiapan = saldo fisik WIP-CUT per bundle
        // (sudah net dari pickup), dibatasi hasil QC. Bebas stok hantu.
        if (config('inventory.readiness_source') === 'ledger') {
            $qtyOk = $this->qty_cutting_ok;
            $bal = $this->ledgerBalanceAt(config('inventory.warehouses.wip_cut', 'WIP-CUT'));

            return max(0.0, min($qtyOk, $bal));
        }

        $qtyOk = $this->qty_cutting_ok; // hasil QC (atau fallback)
        $cutWip = (float) ($this->cut_wip_qty ?? 0); // stok WIP cutting (WIP-CUT)
        $picked = (float) ($this->sewing_picked_qty ?? 0);

        return max(0, min($qtyOk, $cutWip) - $picked);
    }

    /**
     * FASE 3 — saldo LEDGER per-bundle di sebuah gudang (kode), dihitung dari
     * inventory_mutations.cutting_job_bundle_id. Ini sumber kebenaran fisik
     * (bebas hantu). Hanya membaca; tidak menyentuh costing.
     *
     * Memakai kolom hasil eager-subquery (scopeWithLedgerBalances) bila tersedia
     * agar tidak N+1; kalau tidak, query langsung.
     */
    public function ledgerBalanceAt(string $warehouseCode): float
    {
        $alias = 'ledger_' . str_replace('-', '_', strtolower($warehouseCode)) . '_qty';
        if (array_key_exists($alias, $this->attributes)) {
            return (float) $this->attributes[$alias];
        }

        if (!$this->exists || !$this->getKey()) {
            return 0.0;
        }

        return (float) \DB::table('inventory_mutations as im')
            ->join('warehouses as w', 'w.id', '=', 'im.warehouse_id')
            ->where('im.cutting_job_bundle_id', $this->getKey())
            ->where('w.code', $warehouseCode)
            ->sum('im.qty_change');
    }

    /**
     * FASE 3 — eager-load saldo ledger per-bundle sebagai kolom subquery
     * (mis. ledger_wip_cut_qty, ledger_wip_fin_qty) untuk daftar tanpa N+1.
     */
    public function scopeWithLedgerBalances($query, array $warehouseCodes = ['WIP-CUT', 'WIP-FIN'])
    {
        $table = $this->getTable();

        if (empty($query->getQuery()->columns)) {
            $query->addSelect($table . '.*');
        }

        foreach ($warehouseCodes as $code) {
            $alias = 'ledger_' . str_replace('-', '_', strtolower($code)) . '_qty';
            $query->selectSub(function ($q) use ($code, $table) {
                $q->from('inventory_mutations as im')
                    ->join('warehouses as w', 'w.id', '=', 'im.warehouse_id')
                    ->whereColumn('im.cutting_job_bundle_id', $table . '.id')
                    ->where('w.code', $code)
                    ->selectRaw('COALESCE(SUM(im.qty_change), 0)');
            }, $alias);
        }

        return $query;
    }

    // ------------------------------------------------------------------
    // SCOPES
    // ------------------------------------------------------------------

    /**
     * Scope: bundle yang masih punya sisa untuk sewing
     * (versi lama, tidak melihat WIP).
     *
     * Di beberapa tempat lama masih pakai ini:
     *   qty_pcs - sewing_picked_qty > 0
     *
     * Kalau sudah full migrasi ke WIP, sebaiknya hindari scope ini.
     */
    public function scopeReadyForSewingLegacy($query)
    {
        return $query
            ->whereHas('qcResults', function ($q) {
                $q->where('stage', 'cutting');
            })
            ->whereRaw('(COALESCE(qty_pcs, 0) - COALESCE(sewing_picked_qty, 0)) > 0.0001');
    }

    /**
     * Scope: bundle yang siap dijahit
     * dengan mempertimbangkan WIP-CUT & qty pick.
     *
     * - cut_wip_qty > 0
     * - sewing_picked_qty < cut_wip_qty
     * - punya hasil cutting (qty_qc_ok atau QC Cutting > 0 atau qty_pcs > 0)
     *
     * Opsional: filter per gudang WIP-CUT via $wipCutWarehouseId.
     *
     * CATATAN: memakai kolom cut_wip_* (bukan wip_*) supaya bundle yang sudah
     * pernah retur jahit / pindah ke WH-PRD tidak menyembunyikan sisa cutting-nya.
     */
    public function scopeReadyForSewing($query, ?int $wipCutWarehouseId = null)
    {
        // FASE 3: turunkan dari saldo ledger WIP-CUT per bundle (bebas hantu).
        if (config('inventory.readiness_source') === 'ledger') {
            $code = config('inventory.warehouses.wip_cut', 'WIP-CUT');
            $table = $this->getTable();

            return $query->whereRaw(
                'COALESCE((SELECT SUM(im.qty_change) FROM inventory_mutations im '
                . 'INNER JOIN warehouses w ON w.id = im.warehouse_id '
                . 'WHERE im.cutting_job_bundle_id = ' . $table . '.id AND w.code = ?), 0) > 0.0001',
                [$code]
            );
        }

        if ($wipCutWarehouseId) {
            $query->where('cut_wip_warehouse_id', $wipCutWarehouseId);
        }

        return $query
            ->where('cut_wip_qty', '>', 0)
            ->whereColumn('sewing_picked_qty', '<', 'cut_wip_qty')
            ->where(function ($q) {
                $q->where('qty_qc_ok', '>', 0)
                    ->orWhereHas('qcResults', function ($qq) {
                        $qq->where('stage', 'cutting')
                            ->where('qty_ok', '>', 0);
                    })
                    ->orWhere('qty_pcs', '>', 0);
            });
    }

    /**
     * Scope: bundle yang siap Finishing (punya WIP di warehouse tertentu).
     *
     * Biasanya dipakai di modul finishing:
     * - warehouse = WIP-FIN
     * - wip_qty > 0
     */
    public function scopeReadyForFinishing($query, ?int $warehouseId = null)
    {
        // FASE 3b: turunkan dari saldo ledger WIP-FIN per bundle (bebas hantu).
        // Penjaga fisik sebenarnya tetap di ledger (lock inventory_stocks +
        // stockOut allowNegative=false saat posting finishing); cache wip_qty
        // hanya cermin. Toggle via config inventory.readiness_source.
        if (config('inventory.readiness_source') === 'ledger') {
            $code = config('inventory.warehouses.wip_fin', 'WIP-FIN');
            $table = $this->getTable();

            return $query->whereRaw(
                'COALESCE((SELECT SUM(im.qty_change) FROM inventory_mutations im '
                . 'INNER JOIN warehouses w ON w.id = im.warehouse_id '
                . 'WHERE im.cutting_job_bundle_id = ' . $table . '.id AND w.code = ?), 0) > 0.0001',
                [$code]
            );
        }

        if ($warehouseId) {
            $query->where('wip_warehouse_id', $warehouseId);
        }

        return $query->where('wip_qty', '>', 0.0001);
    }

}
