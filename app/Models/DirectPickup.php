<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DirectPickup extends Model
{
    protected $table = 'direct_pickups';

    protected $fillable = [
        'code',
        'picked_at',
        'source_warehouse_id',
        'destination_warehouse_id',
        'picked_by_user_id',
        'stock_request_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'picked_at' => 'datetime',
    ];

    // =========================
    // RELATIONS
    // =========================

    public function sourceWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function pickedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'picked_by_user_id');
    }

    public function stockRequest(): BelongsTo
    {
        return $this->belongsTo(StockRequest::class, 'stock_request_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(DirectPickupLine::class, 'direct_pickup_id');
    }
}
