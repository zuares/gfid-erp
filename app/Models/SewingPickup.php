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
        'date' => 'date', // atau 'immutable_date' kalau mau
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

    public function recalcStatus(): string
    {
        $lines = $this->lines ?? $this->lines()->get();

        $totalRemaining = $lines->sum(function ($l) {
            $qty = (float) ($l->qty_bundle ?? 0);
            $ok = (float) ($l->qty_returned_ok ?? 0);
            $rj = (float) ($l->qty_returned_reject ?? 0);
            $dp = (float) ($l->qty_direct_picked ?? 0);
            return max($qty - ($ok + $rj + $dp), 0);
        });

        // mapping status (sesuaikan istilah kamu)
        if ($totalRemaining <= 0.000001) {
            return 'completed';
        }

        if ($totalRemaining > 0.000001) {
            // kalau ada progress (ok/rj/dp) -> partial, else draft
            $progress = $lines->sum(fn($l) =>
                (float) ($l->qty_returned_ok ?? 0) +
                (float) ($l->qty_returned_reject ?? 0) +
                (float) ($l->qty_direct_picked ?? 0)
            );
            return ($progress > 0.000001) ? 'partial' : 'draft';
        }

        return 'draft';
    }

}
