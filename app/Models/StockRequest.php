<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'date',
        'purpose',
        'source_warehouse_id',
        'destination_warehouse_id',
        'status',
        'requested_by_user_id',
        'notes',
        'received_by_user_id',
        'received_at',
    ];

    protected $casts = [
        'date' => 'date',
        'received_at' => 'datetime',
    ];

    public function lines()
    {
        return $this->hasMany(StockRequestLine::class)->orderBy('line_no');
    }

    public function sourceWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function destinationWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function scopePurpose($q, string $purpose)
    {
        return $q->where('purpose', $purpose);
    }

    public function scopeRtsReplenish($q)
    {
        return $q->where('purpose', 'rts_replenish');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function totalRequested(): float
    {
        return (float) $this->lines->sum('qty_request');
    }

    public function totalDispatched(): float
    {
        return (float) $this->lines->sum('qty_dispatched');
    }

    public function totalReceived(): float
    {
        return (float) $this->lines->sum('qty_received');
    }

    public function totalPicked(): float
    {
        return (float) $this->lines->sum('qty_picked');
    }

    public function totalFulfilledToRts(): float
    {
        return (float) $this->lines->sum(fn($l) => (float) $l->qty_received + (float) $l->qty_picked);
    }

    public function outstandingToRts(): float
    {
        return (float) $this->lines->sum(fn($l) => $l->outstandingQty());
    }
}
