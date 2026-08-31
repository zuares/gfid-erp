<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShipmentWave extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_POSTED = 'posted';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'shipment_id',
        'sequence',
        'code',
        'label',
        'status',
        'total_qty',
        'opened_at',
        'posted_at',
        'posted_by',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'posted_at' => 'datetime',
        'total_qty' => 'integer',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ShipmentLine::class, 'shipment_wave_id');
    }

    public function orderScans(): HasMany
    {
        return $this->hasMany(ShipmentOrderScan::class, 'shipment_wave_id');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN && empty($this->posted_at);
    }

    public function isPosted(): bool
    {
        return $this->status === self::STATUS_POSTED && !empty($this->posted_at);
    }
}
