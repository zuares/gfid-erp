<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class StorefrontProduct extends Model
{
    protected $fillable = [
        'slug', 'name', 'description', 'product_type', 'base_price',
        'label', 'image_url', 'is_published', 'sort_order', 'item_id', 'category_id', 'audience',
        // Stock
        'stock',
        // Ranking overrides (owner)
        'is_pinned', 'pin_position', 'manual_boost', 'featured_until',
        // Computed ranking
        'rank_score', 'rank_position', 'rank_updated_at', 'rank_debug',
    ];

    protected $casts = [
        'base_price'     => 'integer',
        'is_published'   => 'boolean',
        'stock'          => 'integer',
        'is_pinned'      => 'boolean',
        'pin_position'   => 'integer',
        'manual_boost'   => 'float',
        'featured_until' => 'datetime',
        'rank_score'     => 'float',
        'rank_position'  => 'integer',
        'rank_updated_at'=> 'datetime',
        'rank_debug'     => 'array',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function variants(): HasMany
    {
        return $this->hasMany(StorefrontProductVariant::class, 'product_id')
                    ->orderBy('sort_order')
                    ->orderBy('id');
    }

    public function sizes(): HasMany
    {
        return $this->hasMany(StorefrontProductSize::class, 'product_id')
                    ->orderBy('sort_order')
                    ->orderBy('id');
    }

    public function variantItemMappings(): HasMany
    {
        return $this->hasMany(StorefrontVariantItemMapping::class, 'product_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(StorefrontProductCategory::class, 'category_id');
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    /** Resolve image_url ke src yang bisa dipakai di <img> */
    public function getImageSrc(): string
    {
        if (!$this->image_url) return '';
        return str_starts_with($this->image_url, 'http')
            ? $this->image_url
            : Storage::url($this->image_url);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->product_type) {
            'jumbo'   => 'Jumbo',
            default   => 'Regular',
        };
    }

    public function getTypeBadgeColorAttribute(): string
    {
        return $this->product_type === 'jumbo' ? '#7c3aed' : '#0284c7';
    }

    public function getAudienceLabelAttribute(): string
    {
        return match ($this->audience) {
            'pria'     => 'Pria',
            'wanita'   => 'Wanita',
            'anak'     => 'Anak',
            'olahraga' => 'Olahraga',
            'unisex'   => 'Unisex',
            default    => '',
        };
    }

    public function getAudienceBadgeColorAttribute(): string
    {
        return match ($this->audience) {
            'pria'     => '#1d4ed8',
            'wanita'   => '#be185d',
            'anak'     => '#d97706',
            'olahraga' => '#15803d',
            'unisex'   => '#6b7280',
            default    => '#94a3b8',
        };
    }
}
