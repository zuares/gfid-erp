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

    /**
     * Hitung status berdasarkan sisa qty di semua line.
     * Status:
     * - completed: semua line remaining = 0
     * - partial: ada progress (return ok/reject/direct pick/adjust) tapi belum selesai
     * - draft: belum ada progress sama sekali
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

            return max($qty - ($ok + $rj + $dp + $adj), 0);
        });

        if ($totalRemaining <= 0.000001) {
            return 'completed';
        }

        $progress = $lines->sum(function ($l) {
            return
            (float) ($l->qty_returned_ok ?? 0) +
            (float) ($l->qty_returned_reject ?? 0) +
            (float) ($l->qty_direct_picked ?? 0) +
            (float) ($l->qty_progress_adjusted ?? 0); // ✅ ikut adjustment
        });

        return ($progress > 0.000001) ? 'partial' : 'draft';
    }
}
