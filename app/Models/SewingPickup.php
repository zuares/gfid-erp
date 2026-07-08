<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SewingPickup extends Model
{
    protected $fillable = [
        'code',
        'date',
        'warehouse_id',
        'operator_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'voided_at' => 'datetime',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function operator()
    {
        return $this->belongsTo(Employee::class, 'operator_id');
    }

    public function lines()
    {
        return $this->hasMany(SewingPickupLine::class);
    }

    public function supplyLines()
    {
        return $this->hasMany(SewingPickupSupplyLine::class, 'sewing_pickup_id');
    }

    public function lineSupplyLines()
    {
        return $this->hasMany(SewingPickupLineSupplyLine::class, 'sewing_pickup_id');
    }

    public function physicalAdjustment()
    {
        return $this->hasOne(InventoryAdjustment::class, 'reference_id')
            ->where('reference_type', self::class)
            ->latestOfMany();
    }

    /**
     * Hitung status berdasarkan sisa qty di semua line.
     * Status:
     * - completed: semua line remaining = 0 dan ada setoran nyata (return ok/reject)
     * - closed   : semua line remaining = 0 tapi diselesaikan lewat cancel/settle/write-off
     *              (qty_closed) tanpa setoran — barang tidak jadi disetor dari jahit
     * - partial  : ada progress tapi belum selesai
     * - draft    : belum ada progress sama sekali
     *
     * Catatan: qty_closed = qty yang sudah di-cancel/settle/write-off via WIP cleanup,
     * dianggap "tuntas" (tidak lagi menggantung di WIP-SEW).
     */
    public function recalcStatus(): string
    {
        $lines = $this->relationLoaded('lines') ? $this->lines : $this->lines()->get();

        $totalRemaining = $lines->sum(function ($l) {
            $qty = (float) ($l->qty_bundle ?? 0);
            $ok = (float) ($l->qty_returned_ok ?? 0);
            $rj = (float) ($l->qty_returned_reject ?? 0);
            $dp = (float) ($l->qty_direct_picked ?? 0);
            $adj = (float) ($l->qty_progress_adjusted ?? 0); // ✅ ikut adjustment
            $closed = (float) ($l->qty_closed ?? 0);         // ✅ ikut cancel/settle/write-off

            return max($qty - ($ok + $rj + $dp + $adj + $closed), 0);
        });

        $returns = $lines->sum(fn($l) => (float) ($l->qty_returned_ok ?? 0) + (float) ($l->qty_returned_reject ?? 0));
        $closedTotal = $lines->sum(fn($l) => (float) ($l->qty_closed ?? 0));

        if ($totalRemaining <= 0.000001) {
            // Tuntas tanpa setoran nyata & murni ditutup → 'closed'; selain itu 'completed'.
            return ($returns <= 0.000001 && $closedTotal > 0.000001) ? 'closed' : 'completed';
        }

        $progress = $lines->sum(function ($l) {
            return
            (float) ($l->qty_returned_ok ?? 0) +
            (float) ($l->qty_returned_reject ?? 0) +
            (float) ($l->qty_direct_picked ?? 0) +
            (float) ($l->qty_progress_adjusted ?? 0) + // ✅ ikut adjustment
            (float) ($l->qty_closed ?? 0);             // ✅ ikut close
        });

        return ($progress > 0.000001) ? 'partial' : 'draft';
    }
}
