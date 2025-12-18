<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuttingJobBundle extends Model
{
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
        'sewing_picked_qty',
    ];

    protected $casts = [
        'qty_pcs' => 'float',
        'qty_used_fabric' => 'float',
        'qty_qc_ok' => 'float',
        'qty_qc_reject' => 'float',
        'wip_qty' => 'float',
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
     *   ready = max(0, min(qty_cutting_ok, wip_qty) - sewing_picked_qty)
     *
     * Artinya:
     * - Tidak boleh melebihi hasil QC (qty_cutting_ok).
     * - Tidak boleh melebihi stok WIP yang ada di gudang WIP-CUT.
     * - Dikurangi qty yang sudah pernah dipick ke sewing.
     */
    public function getQtyReadyForSewingAttribute(): float
    {
        $qtyOk = $this->qty_cutting_ok; // hasil QC (atau fallback)
        $wipQty = (float) ($this->wip_qty ?? 0); // stok WIP dari gudang WIP-CUT
        $picked = (float) ($this->sewing_picked_qty ?? 0);

        return max(0, min($qtyOk, $wipQty) - $picked);
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
     * - wip_qty > 0
     * - sewing_picked_qty < wip_qty
     * - punya hasil cutting (qty_qc_ok atau QC Cutting > 0 atau qty_pcs > 0)
     *
     * Opsional: filter per gudang WIP-CUT via $wipCutWarehouseId.
     */
    public function scopeReadyForSewing($query, ?int $wipCutWarehouseId = null)
    {
        if ($wipCutWarehouseId) {
            $query->where('wip_warehouse_id', $wipCutWarehouseId);
        }

        return $query
            ->where('wip_qty', '>', 0)
            ->whereColumn('sewing_picked_qty', '<', 'wip_qty')
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
        if ($warehouseId) {
            $query->where('wip_warehouse_id', $warehouseId);
        }

        return $query->where('wip_qty', '>', 0.0001);
    }

}
