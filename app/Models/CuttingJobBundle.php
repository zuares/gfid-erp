<?php

namespace App\Models;

use App\Models\ItemCategory;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// ✅ yang benar

class CuttingJobBundle extends Model
{
    protected $fillable = [
        'cutting_job_id',
        'bundle_code',
        'bundle_no',
        'lot_id',
        'finished_item_id',
        'item_category_id', // ⬅️ TAMBAH INI
        'qty_pcs',
        'qty_used_fabric',
        'operator_id',
        'status',
        'notes',
        'qty_qc_ok',
        'qty_qc_reject',
        'wip_warehouse_id',
        'wip_qty',
    ];

    protected $casts = [
        'qty_pcs' => 'float',
        'qty_used_fabric' => 'float',
        'qty_qc_ok' => 'float',
        'qty_qc_reject' => 'float',
        'wip_qty' => 'float',
        'sewing_picked_qty' => 'float',
        'date' => 'date', // ← ini kuncinya
    ];

    public function cuttingJob()
    {
        return $this->belongsTo(CuttingJob::class, 'cutting_job_id');
    }

    public function finishedItem()
    {
        return $this->belongsTo(Item::class, 'finished_item_id');
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    public function operator()
    {
        return $this->belongsTo(Employee::class, 'operator_id');
    }

    public function qcResults()
    {
        return $this->hasMany(QcResult::class, 'cutting_job_bundle_id');
    }
    public function wipWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'wip_warehouse_id');
    }

    // <<< scope readyForSewing kita benerin di step 4

    public function latestCuttingQc()
    {
        return $this->qcResults()
            ->where('stage', 'cutting')
            ->orderByDesc('qc_date')
            ->limit(1);
    }

    // ====== ACCESSOR: saldo WIP-FIN (buat Finishing) ======

    public function getWipFinBalanceAttribute(): float
    {
        return (float) ($this->wip_qty ?? 0);
    }

// nilai OK hasil cutting untuk bundle ini
    public function getQtyCuttingOkAttribute(): float
    {
        $qc = $this->relationLoaded('latestCuttingQc')
        ? $this->latestCuttingQc->first()
        : $this->latestCuttingQc()->first();

        if ($qc && $qc->qty_ok !== null) {
            return (float) $qc->qty_ok;
        }

        return (float) $this->qty_pcs;
    }

// sisa yg masih boleh di-pick ke sewing
    public function getQtyRemainingForSewingAttribute(): float
    {
        $maxOk = $this->qty_cutting_ok; // accessor di atas
        $picked = (float) ($this->sewing_picked_qty ?? 0);

        return max($maxOk - $picked, 0);
    }

// scope: hanya bundle yg masih punya sisa > 0
    public function scopeReadyForSewing($query)
    {
        return $query->whereHas('qcResults', function ($q) {
            $q->where('stage', 'cutting'); // bisa tambah status OK kalau ada
        })
            ->whereRaw('(COALESCE(qty_pcs, 0) - COALESCE(sewing_picked_qty, 0)) > 0.0001');
    }

    // ====== SCOPE: bundle yg siap Finishing (punya WIP > 0) ======

    public function scopeReadyForFinishing($query, ?int $warehouseId = null)
    {
        if ($warehouseId) {
            $query->where('wip_warehouse_id', $warehouseId);
        }

        return $query->where('wip_qty', '>', 0.0001);
    }

// Kalau mau ambil khusus QC Cutting:
    public function qcCutting()
    {
        return $this->qcResults()->cutting();
    }

    public function itemCategory()
    {
        return $this->belongsTo(ItemCategory::class, 'item_category_id');
    }

    public function item(): BelongsTo
    {
        // SESUAIKAN nama kolom FK-nya:
        // kalau di tabel kamu kolomnya `item_id`:
        return $this->belongsTo(Item::class);

        // kalau ternyata kolomnya `finished_item_id`, pakai ini:
        // return $this->belongsTo(Item::class, 'finished_item_id');
    }

}
