<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockRequest extends Model
{
    use HasFactory;

    // Kalau mau pakai guarded, boleh juga
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

    /*
    |--------------------------------------------------------------------------
    |  RELATIONSHIPS
    |--------------------------------------------------------------------------
     */

    /**
     * Detail baris item yang diminta.
     */
    public function lines()
    {
        return $this->hasMany(StockRequestLine::class)
            ->orderBy('line_no');
    }

    /**
     * Gudang asal (source).
     */
    public function sourceWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    /**
     * Gudang tujuan (destination).
     */
    public function destinationWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    /**
     * User yang mengajukan request.
     */
    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    /*
    |--------------------------------------------------------------------------
    |  SCOPES
    |--------------------------------------------------------------------------
     */

    /**
     * Scope: hanya stock request dengan purpose tertentu.
     */
    public function scopePurpose($query, string $purpose)
    {
        return $query->where('purpose', $purpose);
    }

    /**
     * Scope: khusus untuk Ready To Sell Replenish (RTS minta ke PRD).
     */
    public function scopeRtsReplenish($query)
    {
        return $query->where('purpose', 'rts_replenish');
    }

    /**
     * Scope: filter berdasarkan status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /*
    |--------------------------------------------------------------------------
    |  HELPERS / ACCESSORS SEDERHANA
    |--------------------------------------------------------------------------
     */

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPartial(): bool
    {
        return $this->status === 'partial';
    }

    /**
     * Hitung total qty_request semua baris.
     */
    public function getTotalRequestedQtyAttribute(): float
    {
        // pastikan lines sudah di-load biar nggak N+1
        return (float) $this->lines->sum('qty_request');
    }

    /**
     * Hitung total qty_issued semua baris.
     */
    public function getTotalIssuedQtyAttribute(): float
    {
        return (float) $this->lines->sum(function (StockRequestLine $line) {
            return $line->qty_issued ?? 0;
        });
    }
}
