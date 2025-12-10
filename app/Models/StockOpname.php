<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOpname extends Model
{
    protected $fillable = [
        'code',
        'date',
        'warehouse_id',
        'type',
        'status',
        'notes',
        'created_by',
        'reviewed_by',
        'finalized_by',
        'reviewed_at',
        'finalized_at',
    ];

    protected $casts = [
        'date' => 'date',
        'reviewed_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    // 🔗 Relasi

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockOpnameLine::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    // 🔍 Scope helper

    public function scopeForWarehouse(Builder $query, int $warehouseId): Builder
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeFinalized(Builder $query): Builder
    {
        return $query->where('status', 'finalized');
    }
}
