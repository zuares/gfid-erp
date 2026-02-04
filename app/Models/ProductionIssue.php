<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionIssue extends Model
{
    protected $fillable = [
        'code', 'date', 'production_order_id',
        'from_warehouse_id', 'to_warehouse_id',
        'status', 'posted_at', 'posted_by', 'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'posted_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {return $this->belongsTo(ProductionOrder::class, 'production_order_id');}
    public function lines(): HasMany
    {return $this->hasMany(ProductionIssueLine::class);}

    public function fromWarehouse(): BelongsTo
    {return $this->belongsTo(Warehouse::class, 'from_warehouse_id');}
    public function toWarehouse(): BelongsTo
    {return $this->belongsTo(Warehouse::class, 'to_warehouse_id');}
}
