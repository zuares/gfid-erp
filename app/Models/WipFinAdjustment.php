<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WipFinAdjustment extends Model
{
    protected $fillable = [
        'code', 'date', 'type', 'reason', 'notes', 'status',
        'posted_at', 'posted_by', 'voided_at', 'voided_by', 'void_reason',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'posted_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(WipFinAdjustmentLine::class);
    }
}
