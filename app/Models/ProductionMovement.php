<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jurnal mutasi produksi (anotasi di atas inventory_mutations).
 * Lihat ProductionFlowService::move() untuk pembuatannya.
 */
class ProductionMovement extends Model
{
    protected $fillable = [
        'code',
        'date',
        'cutting_job_bundle_id',
        'item_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'from_status',
        'to_status',
        'qty',
        'operator_id',
        'deadline',
        'notes',
        'created_by',
        'inventory_mutation_id',
    ];

    protected $casts = [
        'date' => 'date',
        'deadline' => 'date',
        'qty' => 'decimal:3',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(CuttingJobBundle::class, 'cutting_job_bundle_id');
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'operator_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function inventoryMutation(): BelongsTo
    {
        return $this->belongsTo(InventoryMutation::class, 'inventory_mutation_id');
    }
}
