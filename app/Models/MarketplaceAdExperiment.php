<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class MarketplaceAdExperiment extends Model
{
    public const CHANGE_PRICE = 'price';
    public const CHANGE_TARGET_ROAS = 'target_roas';
    public const CHANGE_PRICE_AND_TARGET_ROAS = 'price_and_target_roas';

    public const STATUS_CONFOUNDED = 'CONFOUNDED';
    public const STATUS_INSUFFICIENT_DATA = 'INSUFFICIENT_DATA';
    public const STATUS_LEARNING = 'LEARNING';
    public const STATUS_EARLY_SIGNAL = 'EARLY_SIGNAL';
    public const STATUS_READY_TO_EVALUATE = 'READY_TO_EVALUATE';
    public const STATUS_COMPLETED = 'COMPLETED';

    protected $table = 'marketplace_ad_experiments';

    protected $fillable = [
        'store_id',
        'change_event_id',
        'channel_campaign_id',
        'channel_item_id',
        'internal_item_id',
        'change_type',
        'old_price',
        'new_price',
        'old_target_roas',
        'new_target_roas',
        'changed_at',
        'effective_date',
        'lifecycle_status',
        'verdict',
        'profit_basis',
        'source_granularity',
        'mapping_status',
        'confounded',
        'data_quality_flags',
        'conflict_reason',
        'calculation_snapshot',
        'baseline_days',
        'observation_days',
        'calculation_version',
        'evaluated_at',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'old_price' => 'decimal:2',
        'new_price' => 'decimal:2',
        'old_target_roas' => 'decimal:4',
        'new_target_roas' => 'decimal:4',
        'changed_at' => 'datetime',
        'effective_date' => 'date:Y-m-d',
        'confounded' => 'boolean',
        'data_quality_flags' => 'array',
        'conflict_reason' => 'array',
        'calculation_snapshot' => 'array',
        'evaluated_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function internalItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'internal_item_id');
    }

    public function scopeForStore(Builder $query, int $storeId): Builder
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('lifecycle_status', [self::STATUS_COMPLETED]);
    }
}
