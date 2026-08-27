<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemBom extends Model
{
    protected $fillable = [
        'item_id',
        'name',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'qty' => 'decimal:4',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ItemBomLine::class)->orderBy('sort_order');
    }

    public function requiredLines(): HasMany
    {
        return $this->hasMany(ItemBomLine::class)
            ->where('is_optional', false)
            ->orderBy('sort_order');
    }

    public function optionalLines(): HasMany
    {
        return $this->hasMany(ItemBomLine::class)
            ->where('is_optional', true)
            ->orderBy('sort_order');
    }

    public function hasComponents(): bool
    {
        if ($this->relationLoaded('lines')) {
            return $this->lines->isNotEmpty();
        }

        return $this->lines()->exists();
    }

    public function componentCount(): int
    {
        if ($this->relationLoaded('lines')) {
            return $this->lines->count();
        }

        return $this->lines()->count();
    }

    public function mainMaterialLines(): HasMany
    {
        return $this->hasMany(ItemBomLine::class)
            ->where('usage_stage', ItemBomLine::STAGE_MAIN_MATERIAL);
    }

    public function sewingSupplyLines(): HasMany
    {
        return $this->hasMany(ItemBomLine::class)
            ->where('usage_stage', ItemBomLine::STAGE_SEWING_SUPPLY);
    }

    public function packingSupplyLines(): HasMany
    {
        return $this->hasMany(ItemBomLine::class)
            ->where('usage_stage', ItemBomLine::STAGE_PACKING_SUPPLY);
    }
}
