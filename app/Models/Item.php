<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'unit',
        'type', // material / finished_good
        'item_category_id', // sementara masih dipakai (keluarga produk / legacy)
        'product_category_id', // kalau kamu sudah tambah kolom ini (opsional)
        'item_role', // legacy string (raw_material/production_supply/...)
        'item_role_id', // FK ke item_roles

        'last_purchase_price',
        'hpp',
        'base_unit_cost',

        'active',
        'affects_hpp',
        'default_allocation', // hpp / expense
        'default_expense_account_id',

        'is_stocked',
        'hpp_behavior',

        // optional fields yang kamu sempat pakai di model lama
        'consumption_cutting',
        'consumption_cutting_basis_qty',
    ];

    protected $casts = [
        'last_purchase_price' => 'decimal:2',
        'hpp' => 'decimal:2',
        'base_unit_cost' => 'decimal:2',

        'active' => 'boolean',
        'affects_hpp' => 'boolean',
        'is_stocked' => 'boolean',

        'consumption_cutting' => 'decimal:4',
        'consumption_cutting_basis_qty' => 'decimal:4',
    ];

    /* ============================================================
     * RELATIONSHIPS
     * ============================================================
     */

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'item_category_id');
    }

    // Kalau kamu sudah punya product_category_id
    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'product_category_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(ItemRole::class, 'item_role_id');
    }

    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class);
    }

    public function inventoryStocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function purchaseOrderLines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    public function supplierPrices(): HasMany
    {
        return $this->hasMany(SupplierPrice::class);
    }

    public function shipmentLines(): HasMany
    {
        return $this->hasMany(ShipmentLine::class);
    }

    public function finishingLines(): HasMany
    {
        return $this->hasMany(FinishingJobLine::class, 'item_id');
    }

    public function productionBatchMaterials(): HasMany
    {
        return $this->hasMany(ProductionBatchMaterial::class);
    }

    public function barcodes(): HasMany
    {
        return $this->hasMany(ItemBarcode::class);
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'supplier_items')
            ->withPivot(['last_price'])
            ->withTimestamps();
    }

    public function costSnapshots(): HasMany
    {
        return $this->hasMany(ItemCostSnapshot::class);
    }

    public function activeCostSnapshot()
    {
        return $this->hasOne(ItemCostSnapshot::class, 'item_id')
            ->where('is_active', true)
            ->orderByDesc('snapshot_date');
    }

    /* ============================================================
     * SCOPES
     * ============================================================
     */

    public function scopeInStockAtWarehouse($query, int $warehouseId)
    {
        return $query->whereHas('inventoryStocks', function ($q) use ($warehouseId) {
            $q->where('warehouse_id', $warehouseId)->where('qty', '>', 0);
        });
    }

    /* ============================================================
     * ROLE HELPERS (Tahap 0: source of truth)
     * ============================================================
     */

    /**
     * Kode role yang dipakai sistem:
     * - prioritas: FK item_role_id -> item_roles.code
     * - fallback: item_role string legacy
     */
    public function getRoleCodeAttribute(): ?string
    {
        if ($this->relationLoaded('role') && $this->role) {
            return $this->role->code;
        }

        if (!empty($this->item_role_id)) {
            // lazy load ringan (tanpa eager)
            $code = ItemRole::query()->whereKey((int) $this->item_role_id)->value('code');
            if ($code) {
                return (string) $code;
            }
        }

        // fallback legacy mapping
        $legacy = strtolower((string) ($this->item_role ?? ''));
        return match ($legacy) {
            'raw_material' => ItemRole::RM,
            'production_supply' => ItemRole::SUP,
            'shipping_supply' => ItemRole::PKG,
            'finished_good' => ItemRole::FG,
            default => null,
        };
    }

    public function isRm(): bool
    {
        return $this->role_code === ItemRole::RM;
    }

    public function isSupply(): bool
    {
        return $this->role_code === ItemRole::SUP;
    }

    public function isPackaging(): bool
    {
        return $this->role_code === ItemRole::PKG;
    }

    public function isFinishedGood(): bool
    {
        // FG bisa dari role_code atau type finished_good (fallback legacy)
        return $this->role_code === ItemRole::FG || $this->type === 'finished_good';
    }

    /* ============================================================
     * TYPE HELPERS (legacy)
     * ============================================================
     */

    public function isMaterial(): bool
    {
        return $this->type === 'material';
    }

    public function isFinished(): bool
    {
        return $this->type === 'finished_good';
    }

    /* ============================================================
     * COST HELPERS
     * ============================================================
     */

    /**
     * HPP global “efektif”.
     * Urutan:
     * 1) Snapshot aktif global
     * 2) base_unit_cost
     * 3) hpp (legacy)
     */
    public function getEffectiveUnitCostAttribute(): float
    {
        $snapshot = $this->costSnapshots()
            ->where('is_active', true)
            ->orderByDesc('snapshot_date')
            ->orderByDesc('id')
            ->first();

        if ($snapshot && (float) $snapshot->unit_cost > 0) {
            return (float) $snapshot->unit_cost;
        }

        if ((float) $this->base_unit_cost > 0) {
            return (float) $this->base_unit_cost;
        }

        if ((float) $this->hpp > 0) {
            return (float) $this->hpp;
        }

        return 0.0;
    }

    /**
     * HPP aktif (unit_cost) untuk item ini (global).
     */
    public function getActiveUnitCostAttribute(): float
    {
        return $this->getActiveUnitCostForWarehouse(null);
    }

    public function getActiveUnitCostForWarehouse(?int $warehouseId = null): float
    {
        $snapshot = ItemCostSnapshot::getActiveForItem($this->id, $warehouseId);

        if ($snapshot && (float) $snapshot->unit_cost > 0) {
            return (float) $snapshot->unit_cost;
        }

        if ((float) $this->base_unit_cost > 0) {
            return (float) $this->base_unit_cost;
        }

        if ((float) $this->hpp > 0) {
            return (float) $this->hpp;
        }

        return 0.0;
    }
}
