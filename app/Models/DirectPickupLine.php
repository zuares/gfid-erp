<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectPickupLine extends Model
{
    protected $table = 'direct_pickup_lines';

    protected $fillable = [
        'direct_pickup_id',
        'item_id',
        'qty',
        'source_sewing_return_line_id',
        'sewer_employee_id',
        'sewing_return_id',
        'unit_cost',
        'notes',
    ];

    protected $casts = [
        'qty' => 'float',
        'unit_cost' => 'float',
    ];

    // =========================
    // RELATIONS
    // =========================

    public function directPickup(): BelongsTo
    {
        return $this->belongsTo(DirectPickup::class, 'direct_pickup_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function sewingReturn(): BelongsTo
    {
        return $this->belongsTo(SewingReturn::class, 'sewing_return_id');
    }

    public function sourceSewingReturnLine(): BelongsTo
    {
        return $this->belongsTo(SewingReturnLine::class, 'source_sewing_return_line_id');
    }

    public function sewerEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'sewer_employee_id');
    }
}
