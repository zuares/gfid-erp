<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ItemCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'kind',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /* ==========================
     *  RELATIONSHIPS
     * ==========================
     */

    /**
     * Satu kategori memiliki banyak item.
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function pieceRates(): HasMany
    {
        return $this->hasMany(PieceRate::class, 'item_category_id');
    }

    public function pieceworkPayrollLines(): HasMany
    {
        return $this->hasMany(PieceworkPayrollLine::class, 'item_category_id');
    }

    public function cuttingJobBundles(): HasMany
    {
        return $this->hasMany(CuttingJobBundle::class, 'item_category_id');
    }

    public function supplierCategoryMapping(): HasOne
    {
        return $this->hasOne(SupplierCategoryMapping::class, 'item_category_id');
    }

    /* ==========================
     *  SCOPE & HELPER
     * ==========================
     */

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeKind($query, string $kind)
    {
        return $query->where('kind', $kind);
    }

    public static function kindLabels(): array
    {
        return [
            'product' => 'Kategori Produk Jadi',
            'material' => 'Bahan Baku',
            'support' => 'Bahan Pendukung',
            'accessory' => 'Accessories',
            'packaging' => 'Packaging & Shipping',
            'operational' => 'ATK & Operasional',
            'other' => 'Lainnya',
        ];
    }

    public function getKindLabelAttribute(): string
    {
        return self::kindLabels()[$this->kind] ?? $this->kind ?? 'Lainnya';
    }

    public function isActive(): bool
    {
        return $this->active === true;
    }
}
